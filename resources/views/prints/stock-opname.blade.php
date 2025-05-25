<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <title>Bukti Stok Opname Barang</title>
    <style>
        body {
            font-family: Poppins, Helvetica, "sans-serif";
            font-size: 12px;
            padding: 10px;
            color: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            width: 80%;
            max-width: 900px;
            margin: 0 auto;
            page-break-inside: avoid; /* Mencegah pembagian halaman di dalam container */
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 8px 0;
            vertical-align: top;
        }

        .line {
            border-bottom: 1px solid #000;
            width: 70%;
            margin-top: 4px;
        }

        table.item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.item-table th,
        table.item-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }

        table.item-table th {
            background: #120f0f0c;
            color: #000;
        }

        .note {
            margin-top: 10px;
            font-weight: bold;
        }

        .signatures {
            width: 100%;
            margin-top: 60px;
            text-align: center;
        }

        .signatures td {
            height: 80px;
            vertical-align: bottom;
        }

        .print-button {
            margin-top: 40px;
            text-align: center;
        }

        .text-right {
            text-align: right !important;
        }

        .text-left {
            text-align: left !important;
        }

        .text-center {
            text-align: center !important;
        }

        .bottom-signature {
            border-bottom: 1px dotted #000; width: 100px; margin: 0 auto;
        }

        @media print {
        body {
            margin: 0;
            padding: 0;
            display: block;
            text-align: left;
        }

        .container {
            width: 100%;
            margin: 0;
            page-break-inside: avoid;
        }

        .no-print {
            display: none;
        }

        @page {

            margin: 10mm;
        }

        /* Pastikan tidak ada pemutusan yang tidak diinginkan */
        table, .container {
            page-break-inside: avoid;
        }

        /* Jika Anda ingin memastikan hanya satu halaman */
        html, body {
            max-width: 100%;
            max-height: 100%;
            overflow: hidden;
        }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bukti Stok Opname Barang <br> {{ $setting->company_name }}</h1>

        <!-- Informasi Header -->
        <table class="info-table">
        <tr>
            <td>
                <strong>No Transaksi</strong> : {{ $transaction->number }}<br>
                <div class="line"></div>
            </td>
            <td width="50%">
            <strong>Tanggal</strong> : {{ $transaction->date->format('d F Y') }}<br>
            <div class="line"></div>
            </td>
        </tr>
        </table>

        <!-- Tabel Barang -->
        @php
            $groupedDetails = $transactionDetails->groupBy('item_category');
        @endphp

        @foreach ($groupedDetails as $item_category => $details)
            <h4>Kategori: {{ ucfirst($item_category) }}</h4>

            <table class="item-table">
                <thead>
                    <tr>
                        <th rowspan="2" width="5%">No.</th>
                        <th rowspan="2">Code</th>
                        <th rowspan="2">Name</th>
                        <th rowspan="2">Color</th>
                        <th colspan="3">Stock</th>
                        <th rowspan="2" width="10%">Satuan</th>
                    </tr>
                    <tr>
                        <th width="7%">System</th>
                        <th width="7%">Aktual</th>
                        <th width="7%">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($details as $key => $transactionDetail)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td class="text-left">{{ $transactionDetail->item->code }}</td>
                            <td class="text-left">{{ $transactionDetail->item->name }}</td>
                            <td class="text-left">{{ $transactionDetail->ItemVariant->color }}</td>
                            <td class="text-right">{{ $transactionDetail->stockOpnameDetail->system_stock }}</td>
                            <td class="text-right">{{ $transactionDetail->stockOpnameDetail->actual_stock }}</td>
                            <td class="text-right">{{ with_prefix_diff($transactionDetail->stockOpnameDetail->diff_stock) }}</td>
                            <td>{{ $transactionDetail->item->unit }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
        <!-- Catatan -->
        <div class="note">
        Catatan:<br>
        <span style="font-weight: normal;">{{ $transaction->note }}</span>
        </div>

        <!-- Tanda Tangan -->
        <table class="signatures">
        <tr>
            <td>Diperiksa oleh</td>
            <td>Diterima oleh</td>
            <td>Mengetahui</td>
        </tr>
        <tr>
            <td class="text-center">{{ ucfirst($transaction->pic_field) }}<div class="bottom-signature"></div></td>
            <td><div class="bottom-signature"></div></td>
            <td><div class="bottom-signature"></div></td>
        </tr>
        </table>
    </div>
</body>
<script>
    window.onload = function () {
        window.print();
    };
</script>

</html>
