<?php

namespace App\Http\Controllers;

use App\Models\DataRequest;
use Illuminate\Http\Request;

class AdminDataRequestController extends Controller
{
    public function index(Request $request)
    {
        $dataRequests = DataRequest::with(['user', 'location'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn($q) => $q->where('nama_data', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(15);

        return view('pages.admin.data-requests.index', compact('dataRequests'));
    }

    public function show(DataRequest $dataRequest)
    {
        $dataRequest->load(['user', 'location', 'reviewer']);
        return view('pages.admin.data-requests.show', compact('dataRequest'));
    }

    public function review(Request $request, DataRequest $dataRequest)
    {
        abort_if(
            in_array($dataRequest->status, ['diterima', 'ditolak'], true),
            422,
            'Usulan ini sudah final dan tidak bisa ditinjau ulang.'
        );

        $validated = $request->validate([
            'status'      => ['required', 'in:diterima,ditolak'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $dataRequest->update([
            'status'      => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_by' => $request->user()->getAuthIdentifier(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Usulan berhasil ' . ($validated['status'] === 'diterima' ? 'diterima' : 'ditolak') . '.');
    }
}