<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
// use App\Models\ProdusenData;

class ProdusenDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['produsen_id'=>2,'nama_produsen'=>'Badan Pusat Statistik Kabupaten Gianyar','email'=>'bps5104@bps.go.id','nama_contact_person'=>'Badan Pusat Statistik Kabupaten Gianyar','kontak'=>'(0361) 943075','alamat'=>'Jl. Erlangga No.5, Gianyar, Kec. Gianyar, Kabupaten Gianyar, Bali 80511'],
            ['produsen_id'=>10,'nama_produsen'=>'Dinas Kesehatan Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>19,'nama_produsen'=>'Dinas Ketahanan Pangan, Kelautan dan Perikanan Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>13,'nama_produsen'=>'Dinas Tenaga Kerja Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>22,'nama_produsen'=>'Dinas Sosial Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>16,'nama_produsen'=>'Dinas Pemberdayaan Masyarakat Dan Desa','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>21,'nama_produsen'=>'Dinas Kependudukan dan Pencatatan Sipil Kab. Gianyar', 'email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>18,'nama_produsen'=>'Dinas Perpustakaan dan Kearsipan Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>3,'nama_produsen'=>'Dinas Kebudayaan Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>9,'nama_produsen'=>'Dinas Pertanian Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>6,'nama_produsen'=>'Dinas Perindustrian dan Perdagangan Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>4,'nama_produsen'=>'Dinas Pariwisata Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>27,'nama_produsen'=>'Badan Kesatuan Bangsa dan Politik Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>24,'nama_produsen'=>'Dinas Perhubungan Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>34,'nama_produsen'=>'Bagian Tata Pemerintahan dan Kerjasama Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>15,'nama_produsen'=>'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>66,'nama_produsen'=>'Telkom Indonesia','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>14,'nama_produsen'=>'Dinas Komunikasi dan Informatika Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>68,'nama_produsen'=>'PT Pos cabang Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>17,'nama_produsen'=>'Dinas Perumahan, Kawasan Permukiman, Dan Pertanahan','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>8,'nama_produsen'=>'Dinas Lingkungan Hidup Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>5,'nama_produsen'=>'Dinas Koperasi dan UKM Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>20,'nama_produsen'=>'Dinas Pekerjaan Umum dan Penataan Ruang Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>12,'nama_produsen'=>'Dinas Pemberdayaan Perempuan dan Perlindungan Anak, Pengendalian Penduduk dan Keluarga Berencana','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>29,'nama_produsen'=>'Badan Pertanahan Nasional','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>23,'nama_produsen'=>'Dinas Kepemudaan dan Olah Raga Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>67,'nama_produsen'=>'Perusahaan Listrik Negara','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>61,'nama_produsen'=>'Istana Tampaksiring','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>11,'nama_produsen'=>'Satuan Polisi Pamong Praja Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>28,'nama_produsen'=>'Badan Penanggulangan Bencana Daerah Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>26,'nama_produsen'=>'Badan Kepegawaian dan Pengembangan SDM Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>58,'nama_produsen'=>'Polres Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>74,'nama_produsen'=>'PDAM','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>7,'nama_produsen'=>'Dinas Pendidikan Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>62,'nama_produsen'=>'Kantor Kementerian Agama','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>47,'nama_produsen'=>'Sekretariat Dewan DPRD Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>57,'nama_produsen'=>'Kodim 1616 Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>60,'nama_produsen'=>'Pengadilan Negeri','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>65,'nama_produsen'=>'Rumah Tahanan Negara','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>64,'nama_produsen'=>'Gudang Bulog Batubulan','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>56,'nama_produsen'=>'Kodam IX Udayana','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>69,'nama_produsen'=>'BPD Bali Cabang Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>71,'nama_produsen'=>'BRI Cabang Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>63,'nama_produsen'=>'KPP Pratama Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>72,'nama_produsen'=>'BRI Cabang Ubud','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>73,'nama_produsen'=>'Yonzipur','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>81,'nama_produsen'=>'Komisi Pemilihan Umum Daerah','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>25,'nama_produsen'=>'Badan Pengelolaan Keuangan dan Aset Daerah Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>86,'nama_produsen'=>'Badan Perencanaan Pembangunan Daerah dan Penelitian Pengembangan Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>82,'nama_produsen'=>'Badan Narkotika Nasional Kabupaten Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>70,'nama_produsen'=>'BPD Bali Cabang Ubud','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>78,'nama_produsen'=>'Majelis Madia Desa Pekraman Kab Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>59,'nama_produsen'=>'Kejaksaan Negeri','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>76,'nama_produsen'=>'RSU Sanjiwani','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>79,'nama_produsen'=>'Dinas Pendapatan','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>30,'nama_produsen'=>'Bagian Hukum Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>75,'nama_produsen'=>'KONI','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>80,'nama_produsen'=>'LP LPD K (Lembaga Pemberdayaan LPD Kabupaten Gianyar)','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>84,'nama_produsen'=>'Badan Riset dan Inovasi Daerah Kabupaten Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>49,'nama_produsen'=>'Kecamatan Blahbatuh','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>48,'nama_produsen'=>'Kecamatan Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>50,'nama_produsen'=>'Kecamatan Sukawati','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>53,'nama_produsen'=>'Kecamatan Ubud','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>51,'nama_produsen'=>'Kecamatan Tampaksiring','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>52,'nama_produsen'=>'Kecamatan Tegallalang','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>54,'nama_produsen'=>'Kecamatan Payangan','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>32,'nama_produsen'=>'Bagian Kesejahtraan Rakyat Kab. Gianyar','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>85,'nama_produsen'=>'Rumah Sakit Umum Payangan','email'=>null,'nama_contact_person'=>null,'kontak'=>null,'alamat'=>null],
            ['produsen_id'=>1,'nama_produsen'=>'Badan Pusat Statistik Provinsi Bali','email'=>'pst5100@bps.go.id','nama_contact_person'=>'Badan Pusat Statistik Kabupaten Gianyar','kontak'=>'081-810-5100','alamat'=>'Jl. Raya Puputan (Renon) No 1 Denpasar 80226'],
            ['produsen_id'=>1000,'nama_produsen'=>'Badan Pusat Statistik Indonesia','email'=>'bpshq@bps.go.id','nama_contact_person'=>'Badan Pusat Statistik (BPS) Indonesia','kontak'=>'(62-21) 3841195','alamat'=>'Jl. Dr. Sutomo 6-8, Jakarta 10710 Indonesia'],
            ['produsen_id'=>1001,'nama_produsen'=>'Bank Indonesia','email'=>'bicara@bi.go.id','nama_contact_person'=>'Bank Indonesia Bicara','kontak'=>'131','alamat'=>'Jalan M.H. Thamrin No. 2, Jakarta 10350'],
            ['produsen_id'=>999,'nama_produsen'=>'NA','email'=>'-','nama_contact_person'=>'-','kontak'=>'-','alamat'=>'-'],
            ['produsen_id'=>1002,'nama_produsen'=>'Badan Pusat Statistik Kota Denpasar','email'=>'bps5171@bps.go.id','nama_contact_person'=>'Badan Pusat Statistik Kota Denpasar','kontak'=>'(0361) 434326','alamat'=>'Jl. Mulawarman No.11 Denpasar 80111'],
            ['produsen_id'=>1003,'nama_produsen'=>'Badan Pusat Statistik Kabupaten Jembrana','email'=>'bps5101@bps.go.id','nama_contact_person'=>'Badan Pusat Statistik Kabupaten Jembrana','kontak'=>'(0365) 41132','alamat'=>'Jl. Mayor Sugianyar No. 15'],
            ['produsen_id'=>1005,'nama_produsen'=>'Badan Pusat Statistik Kabupaten Badung','email'=>'bps5103@bps.go.id','nama_contact_person'=>'Badan Pusat Statistik Kabupaten Badung','kontak'=>'(0361) 8441616','alamat'=>'Jl. Raya Darmasaba Abiansemal'],
            ['produsen_id'=>1006,'nama_produsen'=>'Badan Pusat Statistik Kabupaten Tabanan','email'=>'bps5102@bps.go.id','nama_contact_person'=>'Badan Pusat Statistik Kabupaten Tabanan','kontak'=>'(0361) 811571','alamat'=>'Jl. Pahlawan No 1B, Tabanan'],
            ['produsen_id'=>1007,'nama_produsen'=>'Badan Pusat Statistik Kabupaten Bangli','email'=>'bps5106@bps.go.id','nama_contact_person'=>'Badan Pusat Statistik Kabupaten Bangli','kontak'=>'0851-1160-5106','alamat'=>'Jl. Lettu Sobat no. 13 Bangli'],
            ['produsen_id'=>1008,'nama_produsen'=>'Badan Pusat Statistik Kabupaten Klungkung','email'=>'bps5105@bps.go.id','nama_contact_person'=>'Badan Pusat Statistik Kabupaten Klungkung','kontak'=>'(0366) 21180','alamat'=>'Jl. Raya Besakih, Desa Akah, 80716, Klungkung'],
            ['produsen_id'=>1009,'nama_produsen'=>'Badan Pusat Statistik Kabupaten Buleleng','email'=>'bps5108@bps.go.id','nama_contact_person'=>'Badan Pusat Statistik Kabupaten Buleleng','kontak'=>'(0362) 22145','alamat'=>'Jl. Dewi Sartika No. 19 Singaraja'],
            ['produsen_id'=>1010,'nama_produsen'=>'Badan Pusat Statistik Kabupaten Karangasem','email'=>'bps5107@bps.go.id','nama_contact_person'=>'Badan Pusat Statistik Kabupaten Karangasem','kontak'=>'(0363) 21159','alamat'=>'Jl. R.A. Kartini No.4 Amlapura'],
        ];

        foreach ($data as $row) {
            DB::table('produsen_data')->insert([
                'produsen_id'=>$row['produsen_id'],
                'nama_produsen'=>$row['nama_produsen'],
                'email'=>$row['email'],
                'nama_contact_person'=>$row['nama_contact_person'],
                'kontak'=>$row['kontak'],
                'alamat'=>$row['alamat'],
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        }
    }
}