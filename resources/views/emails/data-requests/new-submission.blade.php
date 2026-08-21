<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Usulan Data Baru</title>
</head>
<body>
    <h2>Usulan data baru menunggu tinjauan</h2>

    <p><strong>Nama data:</strong> {{ $dataRequest->nama_data }}</p>
    <p><strong>Instansi perkiraan:</strong> {{ $dataRequest->instansi_perkiraan }}</p>
    <p><strong>Deskripsi:</strong></p>
    <p>{{ $dataRequest->deskripsi }}</p>

    <p>
        <a href="{{ $reviewUrl }}">Tinjau usulan data</a>
    </p>
</body>
</html>
