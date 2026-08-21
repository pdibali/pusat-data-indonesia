<?php

namespace App\Http\Controllers;

use App\Models\DataReport;
use App\Models\User;
use App\Services\MailNotifier;
use Illuminate\Http\Request;

class DataReportController extends Controller
{
    public function index(Request $request)
    {
        $dataReports = DataReport::where('user_id', $request->user()->getAuthIdentifier())
            ->when($request->filled('search'), fn ($q) => $q->where('nama_data', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10);

        return view('pages.data-reports.index', compact('dataReports'));
    }

    public function create()
    {
        return view('pages.data-reports.create');
    }

    public function store(Request $request, MailNotifier $mailNotifier)
    {
        $validated = $request->validate([
            'nama_data'           => ['required', 'string', 'max:255'],
            'location_id'         => ['required', 'integer', 'exists:location,location_id'],
            'produsen_data'       => ['required', 'string', 'max:255'],
            'deskripsi_kesalahan' => ['required', 'string', 'max:2000'],
        ]);

        $dataReport = DataReport::create([
            ...$validated,
            'user_id' => $request->user()->getAuthIdentifier(),
            'status'  => 'diajukan',
        ]);

        $reviewers = User::whereIn('group_id', [1, 2])->get();
        foreach ($reviewers as $reviewer) {
            $mailNotifier->kirimDataReport($dataReport, $reviewer->email);
        }

        return redirect()
            ->route('data_reports.index')
            ->with('success', 'Laporan berhasil dikirim. Tim kami akan meninjau laporan Anda.');
    }

    public function show(Request $request, DataReport $dataReport)
    {
        abort_unless(
            $dataReport->user_id === $request->user()->getAuthIdentifier()
                || in_array($request->user()->group_id, [1, 2], true),
            403
        );

        return view('pages.data-reports.show', compact('dataReport'));
    }
}