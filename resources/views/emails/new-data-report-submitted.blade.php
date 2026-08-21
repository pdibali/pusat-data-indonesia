<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Data Baru</title>
</head>
<body>
    <h2>Laporan data baru menunggu tinjauan</h2>

    <p><strong>Nama data:</strong> {{ $dataReport->nama_data }}</p>
    <p><strong>Produsen data:</strong> {{ $dataReport->produsen_data }}</p>
    <p><strong>Deskripsi kesalahan:</strong></p>
    <p>{{ $dataReport->deskripsi_kesalahan }}</p>

    <p>Silakan masuk ke aplikasi untuk meninjau laporan ini.</p>
</body>
</html>
