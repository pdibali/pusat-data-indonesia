<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RujukanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'rujukan_id' => 1,
                'nama_rujukan' => 'Kabupaten Gianyar Dalam Angka',
                'link_rujukan' => 'https://gianyarkab.bps.go.id/id/publication/2021/02/26/5c69263f928a91121cef3e2c',
                'gambar_rujukan' => null,
                'produsen_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 2,
                'nama_rujukan' => 'Provinsi Bali Dalam Angka',
                'link_rujukan' => 'https://bali.bps.go.id/id/publication/2025/02/28/c1546258bf024478ec028d7f',
                'gambar_rujukan' => null,
                'produsen_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 3,
                'nama_rujukan' => 'Statistik Indonesia',
                'link_rujukan' => 'https://www.bps.go.id/id/publication/2021/02/26/938316574c78772f27e9b477/statistik-indonesia-2021.html',
                'gambar_rujukan' => null,
                'produsen_id' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 4,
                'nama_rujukan' => 'Kajian Ekonomi dan Keuangan Syariah Indonesia',
                'link_rujukan' => 'https://www.bi.go.id/id/publikasi/laporan/Documents/KEKSI__2025.pdf',
                'gambar_rujukan' => null,
                'produsen_id' => 1001,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 5,
                'nama_rujukan' => 'Kecamatan Ubud Dalam Angka',
                'link_rujukan' => 'https://gianyarkab.bps.go.id/id/publication/20...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 6,
                'nama_rujukan' => 'Kecamatan Tegallalang Dalam Angka',
                'link_rujukan' => 'https://gianyarkab.bps.go.id/id/publication/20...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 7,
                'nama_rujukan' => 'Kecamatan Sukawati Dalam Angka',
                'link_rujukan' => 'https://gianyarkab.bps.go.id/id/publication/20...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 8,
                'nama_rujukan' => 'Kecamatan Payangan Dalam Angka',
                'link_rujukan' => 'https://gianyarkab.bps.go.id/id/publication/20...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 9,
                'nama_rujukan' => 'Kecamatan Blahbatuh Dalam Angka',
                'link_rujukan' => 'https://gianyarkab.bps.go.id/id/publication/20...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 10,
                'nama_rujukan' => 'Kecamatan Tampaksiring Dalam Angka',
                'link_rujukan' => 'https://gianyarkab.bps.go.id/id/publication/20...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 11,
                'nama_rujukan' => 'Kota Denpasar Dalam Angka',
                'link_rujukan' => 'https://denpasarkota.bps.go.id/id/publication/...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 1002,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 12,
                'nama_rujukan' => 'Kabupaten Jembrana Dalam Angka',
                'link_rujukan' => 'https://jembranakab.bps.go.id/id/publication/2...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 1003,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 13,
                'nama_rujukan' => 'Kabupaten Badung dalam Angka',
                'link_rujukan' => 'https://badungkab.bps.go.id/id/publication/20...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 1005,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 14,
                'nama_rujukan' => 'Kabupaten Tabanan dalam Angka',
                'link_rujukan' => 'https://tabanankab.bps.go.id/id/publication/20...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 1006,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 15,
                'nama_rujukan' => 'Kabupaten Bangli Dalam Angka',
                'link_rujukan' => 'https://banglikab.bps.go.id/id/publication/202...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 1007,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 16,
                'nama_rujukan' => 'Kabupaten Klungkung dalam Angka',
                'link_rujukan' => 'https://klungkungkab.bps.go.id/id/publication/...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 1008,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 17,
                'nama_rujukan' => 'Kabupaten Buleleng dalam Angka',
                'link_rujukan' => 'https://bulelengkab.bps.go.id/id/publication/2...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 1009,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 18,
                'nama_rujukan' => 'Kabupaten Karangasem Dalam Angka',
                'link_rujukan' => 'https://karangasemkab.bps.go.id/id/publicatio...', // TODO: lengkapi URL penuh
                'gambar_rujukan' => null,
                'produsen_id' => 1010,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $row) {
            DB::table('rujukan')->insert([
                'rujukan_id'=>$row['rujukan_id'],
                'nama_rujukan'=>$row['nama_rujukan'],
                'link_rujukan'=>$row['link_rujukan'],
                'gambar_rujukan'=>$row['gambar_rujukan'],
                'produsen_id'=>$row['produsen_id'],
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        }
    }
}