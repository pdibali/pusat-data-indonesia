@php
    $oldLocationId = old('location_id');
    $chain = ['provinsi' => null, 'kabupaten' => null, 'kecamatan' => null, 'desa' => null];
    $preload = ['provinsiList' => [], 'kabupatenList' => [], 'kecamatanList' => [], 'desaList' => []];

    if ($oldLocationId) {
        $selected = \App\Models\Location::find($oldLocationId);
        if ($selected) {
            $code = str_pad($selected->location_id, 10, '0', STR_PAD_LEFT);
            $provCode = substr($code, 0, 2);
            $kabCode  = substr($code, 0, 4);
            $kecCode  = substr($code, 0, 7);

            // Isi chain sesuai level data yang dipilih
            $chain['provinsi'] = (int) $provCode;
            if (substr($code, 2, 2) !== '00') $chain['kabupaten'] = (int) $kabCode;
            if (substr($code, 4, 3) !== '000') $chain['kecamatan'] = (int) $kecCode;
            if (substr($code, 7, 3) !== '000') $chain['desa'] = (int) $code;

            // Preload semua opsi di tiap level yang relevan, biar dropdown langsung terisi tanpa AJAX round-trip
            $preload['provinsiList'] = \App\Models\Location::where('status', 1)
                ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 3, 8) = '00000000'")
                ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 1, 2) != '00'")
                ->orderBy('nama_wilayah')->get(['location_id', 'nama_wilayah']);

            if ($chain['kabupaten'] || $chain['kecamatan'] || $chain['desa']) {
                $preload['kabupatenList'] = \App\Models\Location::where('status', 1)
                    ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 1, 2) = ?", [$provCode])
                    ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 5, 6) = '000000'")
                    ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 3, 2) != '00'")
                    ->orderBy('nama_wilayah')->get(['location_id', 'nama_wilayah']);
            }
            if ($chain['kecamatan'] || $chain['desa']) {
                $preload['kecamatanList'] = \App\Models\Location::where('status', 1)
                    ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 1, 4) = ?", [$kabCode])
                    ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 8, 3) = '000'")
                    ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 5, 3) != '000'")
                    ->orderBy('nama_wilayah')->get(['location_id', 'nama_wilayah']);
            }
            if ($chain['desa']) {
                $preload['desaList'] = \App\Models\Location::where('status', 1)
                    ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 1, 7) = ?", [$kecCode])
                    ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 8, 3) != '000'")
                    ->orderBy('nama_wilayah')->get(['location_id', 'nama_wilayah']);
            }
        }
    }
@endphp

<div x-data="wilayahCascade(@js($chain), @js($preload))" x-init="init()">
    <input type="hidden" name="location_id" :value="selectedLocationId">

    <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">Negara</label>
        <select disabled class="w-full p-2 rounded-md border-2 border-gray-300 bg-gray-100 text-gray-500 text-sm">
            <option>Indonesia</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi <span class="text-red-500">*</span></label>
        <select x-model="provinsi" @change="onProvinsiChange()" required
                class="w-full p-2 rounded-md border-2 border-gray-300 text-sm focus:border-sky-400 focus:ring-sky-400">
            <option value="">-- Pilih Provinsi --</option>
            <template x-for="item in provinsiList" :key="item.location_id">
                <option :value="item.location_id" x-text="item.nama_wilayah" :selected="item.location_id == provinsi"></option>
            </template>
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">Kabupaten/Kota</label>
        <select x-model="kabupaten" @change="onKabupatenChange()" :disabled="!provinsi"
                class="w-full p-2 rounded-md border-2 border-gray-300 text-sm focus:border-sky-400 focus:ring-sky-400 disabled:bg-gray-100">
            <option value="">-- Pilih Kabupaten (opsional) --</option>
            <template x-for="item in kabupatenList" :key="item.location_id">
                <option :value="item.location_id" x-text="item.nama_wilayah"></option>
            </template>
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan</label>
        <select x-model="kecamatan" @change="onKecamatanChange()" :disabled="!kabupaten"
                class="w-full p-2 rounded-md border-2 border-gray-300 text-sm focus:border-sky-400 focus:ring-sky-400 disabled:bg-gray-100">
            <option value="">-- Pilih Kecamatan (opsional) --</option>
            <template x-for="item in kecamatanList" :key="item.location_id">
                <option :value="item.location_id" x-text="item.nama_wilayah"></option>
            </template>
        </select>
    </div>

    <div class="mb-1">
        <label class="block text-sm font-medium text-gray-700 mb-1">Desa/Kelurahan</label>
        <select x-model="desa" @change="onDesaChange()" :disabled="!kecamatan"
                class="w-full p-2 rounded-md border-2 border-gray-300 text-sm focus:border-sky-400 focus:ring-sky-400 disabled:bg-gray-100">
            <option value="">-- Pilih Desa (opsional) --</option>
            <template x-for="item in desaList" :key="item.location_id">
                <option :value="item.location_id" x-text="item.nama_wilayah"></option>
            </template>
        </select>
    </div>

    @error('location_id')
        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
    @enderror
</div>

<script>
function wilayahCascade(initialChain, preload) {
    return {
        provinsi: initialChain.provinsi || '',
        kabupaten: initialChain.kabupaten || '',
        kecamatan: initialChain.kecamatan || '',
        desa: initialChain.desa || '',
        provinsiList: preload.provinsiList || [],
        kabupatenList: preload.kabupatenList || [],
        kecamatanList: preload.kecamatanList || [],
        desaList: preload.desaList || [],
        selectedLocationId: initialChain.desa || initialChain.kecamatan || initialChain.kabupaten || initialChain.provinsi || null,

        async init() {
            if (this.provinsiList.length === 0) {
                const res = await fetch('{{ route('wilayah.provinsi') }}');
                this.provinsiList = await res.json();
            }
        },

        async onProvinsiChange() {
            this.kabupaten = ''; this.kecamatan = ''; this.desa = '';
            this.kabupatenList = []; this.kecamatanList = []; this.desaList = [];
            this.selectedLocationId = this.provinsi || null;
            if (!this.provinsi) return;
            const res = await fetch(`{{ route('wilayah.kabupaten') }}?provinsi_id=${this.provinsi}`);
            this.kabupatenList = await res.json();
        },

        async onKabupatenChange() {
            this.kecamatan = ''; this.desa = '';
            this.kecamatanList = []; this.desaList = [];
            this.selectedLocationId = this.kabupaten || this.provinsi;
            if (!this.kabupaten) return;
            const res = await fetch(`{{ route('wilayah.kecamatan') }}?kabupaten_id=${this.kabupaten}`);
            this.kecamatanList = await res.json();
        },

        async onKecamatanChange() {
            this.desa = '';
            this.desaList = [];
            this.selectedLocationId = this.kecamatan || this.kabupaten || this.provinsi;
            if (!this.kecamatan) return;
            const res = await fetch(`{{ route('wilayah.desa') }}?kecamatan_id=${this.kecamatan}`);
            this.desaList = await res.json();
        },

        onDesaChange() {
            this.selectedLocationId = this.desa || this.kecamatan || this.kabupaten || this.provinsi;
        },
    }
}
</script>