<?php

namespace App\Http\Controllers;

use App\Exports\ProdusenExport;
use App\Exports\ProdusenImportTemplate;
use App\Imports\ProdusenBulkImport;
use App\Models\ProdusenData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;
use Illuminate\Support\Str;

class ProdusenController extends Controller
{
    public function index(Request $request)
    {
        $produsen = $this->produsenQuery($request)
            ->paginate(10)
            ->withQueryString();

        return view('pages.produsen.index', compact('produsen'));
    }

    public function export(Request $request)
    {
        return Excel::download(
            new ProdusenExport($this->produsenQuery($request)),
            'produsen.xlsx'
        );
    }

    public function importForm()
    {
        return view('pages.produsen.import', ['preview' => null, 'token' => null]);
    }

    public function importTemplate()
    {
        return Excel::download(new ProdusenImportTemplate, 'template-produsen.xlsx');
    }

    public function previewImport(Request $request, ProdusenBulkImport $import)
    {
        $request->validate([
            'file_excel' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/octet-stream,application/zip|max:10240',
        ], [
            'file_excel.required' => 'File Excel wajib dipilih.',
            'file_excel.mimetypes' => 'File harus berformat .xlsx atau .xls.',
            'file_excel.max' => 'Ukuran file maksimal 10MB.',
        ]);

        try {
            $preview = $import->preview($request->file('file_excel')->getRealPath());
        } catch (SpreadsheetReaderException $exception) {
            return back()->withErrors([
                'file_excel' => 'File Excel tidak dapat dibaca. Pastikan file tidak rusak dan menggunakan template yang disediakan.',
            ]);
        }

        $token = null;
        if (empty($preview['errors'])) {
            $token = (string) Str::uuid();
            Cache::put(
                $this->produsenImportCacheKey($token),
                $preview['rows'],
                now()->addMinutes(20)
            );
        }

        return view('pages.produsen.import', compact('preview', 'token'));
    }

    public function confirmImport(Request $request, ProdusenBulkImport $import)
    {
        $validated = $request->validate(['token' => 'required|uuid']);
        $cacheKey = $this->produsenImportCacheKey($validated['token']);
        $rows = Cache::get($cacheKey);

        if (! is_array($rows) || empty($rows)) {
            return redirect()->route('admin.produsen.import')
                ->withErrors(['file_excel' => 'Pratinjau sudah kedaluwarsa atau tidak ditemukan. Silakan unggah ulang file.']);
        }

        try {
            $imported = $import->store($rows);
        } catch (\DomainException $exception) {
            Cache::forget($cacheKey);

            return redirect()->route('admin.produsen.import')
                ->withErrors(['file_excel' => 'Data produsen berubah setelah pratinjau dibuat. Silakan unggah ulang untuk memeriksa data terbaru.']);
        }

        Cache::forget($cacheKey);

        return redirect()->route('admin.produsen.index')
            ->with('success', "{$imported} produsen berhasil ditambahkan dari Excel.");
    }

    private function produsenImportCacheKey(string $token): string
    {
        return 'produsen_import:'.auth()->user()->getAuthIdentifier().':'.$token;
    }

    private function produsenQuery(Request $request): Builder
    {
        $query = ProdusenData::where('status', 1);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_produsen', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nama_contact_person', 'like', "%{$search}%")
                    ->orWhere('kontak', 'like', "%{$search}%");
            });
        }

        $sortDirection = $request->input('sort', 'terbaru') === 'terlama' ? 'asc' : 'desc';

        return $query->orderBy('created_at', $sortDirection)
            ->orderBy('produsen_id', $sortDirection);
    }

    public function create()
    {
        return view('pages.produsen.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_produsen' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'nama_contact_person' => 'nullable|string|max:100',
            'kontak' => 'nullable|string|max:100',
            'alamat' => 'nullable|string|max:255',
        ]);

        ProdusenData::create($request->only([
            'nama_produsen', 'email', 'nama_contact_person', 'kontak', 'alamat',
        ]));

        return redirect()->route('admin.produsen.index')
            ->with('success', 'Produsen berhasil ditambahkan.');
    }

    public function show(ProdusenData $produsen)
    {
        return view('pages.produsen.show', compact('produsen'));
    }

    public function edit(ProdusenData $produsen)
    {
        return view('pages.produsen.edit', compact('produsen'));
    }

    public function update(Request $request, ProdusenData $produsen)
    {
        $request->validate([
            'nama_produsen' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'nama_contact_person' => 'nullable|string|max:100',
            'kontak' => 'nullable|string|max:100',
            'alamat' => 'nullable|string|max:255',
        ]);

        $produsen->update($request->only([
            'nama_produsen', 'email', 'nama_contact_person', 'kontak', 'alamat',
        ]));

        return redirect()->route('admin.produsen.index')
            ->with('success', 'Produsen berhasil diperbarui.');
    }

    public function toggleStatus(ProdusenData $produsen)
    {
        $produsen->update(['status' => $produsen->status === 1 ? 0 : 1]);

        $status = $produsen->status === 1 ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.produsen.index')
            ->with('success', "Produsen {$produsen->nama_produsen} berhasil {$status}.");
    }

    public function destroy(ProdusenData $produsen)
    {
        try {
            return redirect()->route('admin.produsen.index')
                ->with('error', 'Produsen tidak dapat dihapus. Gunakan tombol nonaktifkan untuk menonaktifkan produsen.');
        } catch (\Exception $e) {
            return redirect()->route('admin.produsen.index')
                ->with('error', 'Produsen tidak dapat dihapus karena masih memiliki data rujukan.');
        }
    }
}
