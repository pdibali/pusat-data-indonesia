<?php

namespace App\Http\Controllers;

use App\Models\Rujukan;
use App\Models\ProdusenData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RujukanController extends Controller
{
    public function index(Request $request)
    {
        $query = Rujukan::with('produsen')->where('status', 1);

        if ($request->filled('search')) {
            $query->where('nama_rujukan', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('produsen_id')) {
            $query->where('produsen_id', $request->produsen_id);
        }

        $sortDirection = $request->input('sort', 'terbaru') === 'terlama' ? 'asc' : 'desc';
        $rujukans = $query->orderBy('created_at', $sortDirection)
            ->orderBy('rujukan_id', $sortDirection)
            ->paginate(10)
            ->withQueryString();
        $produsen  = ProdusenData::orderBy('nama_produsen')->get();

        return view('pages.rujukan.index', compact('rujukans', 'produsen'));
    }

    public function create()
    {
        $produsen = ProdusenData::orderBy('nama_produsen')->get();
        return view('pages.rujukan.create', compact('produsen'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_rujukan'  => 'required|string|max:255',
            'link_rujukan'  => 'nullable|url|max:255',
            'gambar_rujukan'=> 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'produsen_id'   => 'required|exists:produsen_data,produsen_id',
        ]);

        $data = $request->only(['nama_rujukan', 'link_rujukan', 'produsen_id']);

        if ($request->hasFile('gambar_rujukan')) {
            $data['gambar_rujukan'] = $request->file('gambar_rujukan')
                ->store('rujukan', 'public');
        }

        Rujukan::create($data);

        return redirect()->route('admin.rujukan.index')
            ->with('success', 'Rujukan berhasil ditambahkan.');
    }

    public function show(Rujukan $rujukan)
    {
        $rujukan->load('produsen');
        return view('pages.rujukan.show', compact('rujukan'));
    }

    public function edit(Rujukan $rujukan)
    {
        $produsen = ProdusenData::orderBy('nama_produsen')->get();
        return view('pages.rujukan.edit', compact('rujukan', 'produsen'));
    }

    public function update(Request $request, Rujukan $rujukan)
    {
        $request->validate([
            'nama_rujukan'  => 'required|string|max:255',
            'link_rujukan'  => 'nullable|url|max:255',
            'gambar_rujukan'=> 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'produsen_id'   => 'required|exists:produsen_data,produsen_id',
        ]);

        $data = $request->only(['nama_rujukan', 'link_rujukan', 'produsen_id']);

        if ($request->hasFile('gambar_rujukan')) {
            if ($rujukan->gambar_rujukan) {
                Storage::disk('public')->delete($rujukan->gambar_rujukan);
            }
            $data['gambar_rujukan'] = $request->file('gambar_rujukan')
                ->store('rujukan', 'public');
        }

        $rujukan->update($data);

        return redirect()->route('admin.rujukan.index')
            ->with('success', 'Rujukan berhasil diperbarui.');
    }

    public function toggleStatus(Rujukan $rujukan)
    {
        $rujukan->update(['status' => $rujukan->status === 1 ? 0 : 1]);

        $status = $rujukan->status === 1 ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.rujukan.index')
            ->with('success', "Rujukan {$rujukan->nama_rujukan} berhasil {$status}.");
    }

    public function destroy(Rujukan $rujukan)
    {
        return redirect()->route('admin.rujukan.index')
            ->with('error', 'Rujukan tidak dapat dihapus. Gunakan tombol nonaktifkan untuk menonaktifkan rujukan.');
    }
}