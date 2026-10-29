<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="AIMATRIK - Rekapan Penilaian">
    <meta name="author" content="AIMATRIK Team">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>AIMATRIK - Rekapan Penilaian Tata Kelola Kecerdasan Buatan (AI) - {{ $findDaftarPenilaian->name }} </title>
    <link rel="stylesheet" href="{{ asset('assets/extensions/printjs/print.min.css') }}">
    <link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.ico') }}" type="image/png" />
    @include('admin.pages.rekapan-penilaian.print._style')
</head>

<body>

    <div class="no-print" style="text-align: center; margin-bottom: 1rem;">
        <button onclick="printContent()"
            style="
            background-color: #1679AB;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: background-color 0.3s ease;
        "
            onmouseover="this.style.backgroundColor='#125c85'" onmouseout="this.style.backgroundColor='#1679AB'">
            🖨️ Cetak Halaman
        </button>
    </div>

    <div id="print-area">
        <!-- COVER PAGE -->
        <div class="print-container print-cover-page">
            <div style="padding-top: 8cm; padding-bottom: 8cm; text-align: left;">
                <h2 style="margin-bottom: 1rem;">Rekapan Penilaian Tata Kelola Kecerdasan Buatan (AI)</h2>
                <h3>{{ $findDaftarPenilaian->name }}</h3>
                <p><strong>Oleh :</strong> {{ Auth::user()->name }}</p>
            </div>
        </div>

        @forelse ($dataRingkasan as $index=>$itemQuestion)
            <div class="print-container">
                <center>
                    <h2>Bagian {{ $loop->iteration }}. Domain {{ $itemQuestion['domain'] }}</h2>
                </center>
                <hr>
                <div class="section">
                    <h3><strong>1. Hasil Input Penilaian</strong></h3>
                    <br>
                    @include('admin.pages.rekapan-penilaian.print._print_hasil_input_penilaian')
                </div>

                <div class="section">
                    <h3>2. Gap dan Maturity Level</h3>
                    <br>

                    @include('admin.pages.rekapan-penilaian.print._print_hasil_input_gap_dan_grafik')
                </div>
            </div>
        @empty
        @endforelse

        <div class="print-container">
            <center>
                <h2>Ringkasan Umum</h2>
            </center>
            <hr>
            <br>
            @include('admin.pages.rekapan-penilaian.print._print_hasil_rekapan')
        </div>
    </div>

    @include('admin.pages.rekapan-penilaian.print._script')
</body>

</html>
