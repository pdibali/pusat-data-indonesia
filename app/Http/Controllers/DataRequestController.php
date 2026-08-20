<?php

namespace App\Http\Controllers;

use App\Models\DataRequest;
use App\Models\User;
use App\Services\MailNotifier;
use Illuminate\Http\Request;

class DataRequestController extends Controller
{
    public function index(Request $request)
    {
        $dataRequests = DataRequest::where('user_id', $request->user()->getAuthIdentifier())
            ->when($request->filled('search'), fn ($q) => $q->where('nama_data', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10);

        return view('pages.data-requests.index', compact('dataRequests'));
    }

    public function create()
    {
        return view('pages.data-requests.create');
    }

    public function store(Request $request, MailNotifier $mailNotifier)
    {
        $validated = $request->validate([
            'nama_data'          => ['required', 'string', 'max:255'],
            'location_id'        => ['required', 'integer', 'exists:location,location_id'],
            'deskripsi'          => ['required', 'string', 'max:2000'],
            'instansi_perkiraan' => ['required', 'string', 'max:255'],
        ]);

        $dataRequest = DataRequest::create([
            ...$validated,
            'user_id' => $request->user()->getAuthIdentifier(),
            'status'  => 'diajukan',
        ]);

        $reviewers = User::whereIn('group_id', [1, 2])->get();
        foreach ($reviewers as $reviewer) {
            $mailNotifier->kirimDataRequest($dataRequest, $reviewer->email);
        }

        return redirect()
            ->route('data_requests.index')
            ->with('success', 'Usulan data berhasil diajukan. Tim kami akan meninjau usulan Anda.');
    }

    public function show(Request $request, DataRequest $dataRequest)
    {
        abort_unless(
            $dataRequest->user_id === $request->user()->getAuthIdentifier()
                || in_array($request->user()->group_id, [1, 2], true),
            403
        );

        return view('pages.data-requests.show', compact('dataRequest'));
    }
}