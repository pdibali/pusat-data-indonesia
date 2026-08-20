<?php

namespace App\Http\Controllers;

use App\Models\DataReport;
use Illuminate\Http\Request;

class AdminDataReportController extends Controller
{
    public function index(Request $request)
    {
        $dataReports = DataReport::with(['user', 'location'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn($q) => $q->where('nama_data', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(15);

        return view('pages.admin.data-reports.index', compact('dataReports'));
    }

    public function show(DataReport $dataReport)
    {
        $dataReport->load(['user', 'location', 'reviewer']);
        return view('pages.admin.data-reports.show', compact('dataReport'));
    }

    public function review(Request $request, DataReport $dataReport)
    {
        abort_if(
            in_array($dataReport->status, ['diterima', 'ditolak'], true),
            422,
            'Laporan ini sudah final dan tidak bisa ditinjau ulang.'
        );

        $validated = $request->validate([
            'status'      => ['required', 'in:diterima,ditolak'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $dataReport->update([
            'status'      => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_by' => $request->user()->getAuthIdentifier(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Laporan berhasil ' . ($validated['status'] === 'diterima' ? 'diterima' : 'ditolak') . '.');
    }
}