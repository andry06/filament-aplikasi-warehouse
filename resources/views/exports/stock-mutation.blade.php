<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $categoryIdn }}</title>
</head>

<body>
    <table style="border: 1px solid black">
        <thead>
            <tr>
                <th colspan="8"
                    style="
                        border: 1px solid black;
                        height: 40px;
                        border : none;
                        background-color : #C00000;
                        color: white;
                        word-wrap: break-word"
                >
                    {{ $setting->company_name }}
                </th>
            </tr>
            <tr>
                <th colspan="8"
                    style="
                        border: 1px solid black;
                        height: 40px;
                        border : none;
                        background-color : #C00000;
                        color: white;
                        word-wrap: break-word"
                >
                    Mutasi Stok {{ $categoryIdn }} Gudang {{ $warehouse->name }}
            </tr>
            <tr>
                <th colspan="8"
                    style="
                        border: 1px solid black;
                        height: 40px;
                        border : none;
                        background-color : #C00000;
                        color: white;
                        word-wrap: break-word"
                >
                    Periode {{ $dates }}
            </tr>
            <tr>
                <th colspan="8"
                    style="
                        height: 40px;
                        word-wrap: break-word"
                    ></th>
            </tr>
             <tr>
                <th
                    rowspan="2"
                    style="
                        border: 1px solid black;
                        background-color: #F1A983;
                        text-align: center;
                        vertical-align: middle;"
                >
                    No
                </th>
                <th
                    colspan="3"
                    style="
                        border: 1px solid black;
                        background-color: #F1A983;
                        text-align: center;
                        vertical-align: middle;"
                >
                    Barang
                </th>
                <th rowspan="2"
                    style="
                        border: 1px solid black;
                        background-color: #F1A983;
                        text-align: center;
                        vertical-align: middle;"
                >
                    Stok
                    <br>
                    Awal
                </th>
                <th colspan="2"
                    style="
                        border: 1px solid black;
                        background-color: #F1A983;
                        text-align: center;
                        vertical-align: middle;"
                >
                    Jumlah Transaksi
                </th>
                <th rowspan="2"
                    style="
                        border: 1px solid black;
                        background-color: #F1A983;
                        text-align: center;
                        vertical-align: middle;"
                >
                    Stok
                    <br>
                    Akhir
                </th>
             </tr>
             <tr>
                <th
                    style="
                        border: 1px solid black;
                        background-color: #F1A983;
                        text-align: center;
                        vertical-align: middle;"
                >
                    Kode
                </th>
                 <th
                    style="
                        border: 1px solid black;
                        background-color: #F1A983;
                        text-align: center;
                        vertical-align: middle;"
                >
                    Nama
                </th>
                <th
                    style="
                        border: 1px solid black;
                        background-color: #F1A983;
                        text-align: center;
                        vertical-align: middle;"
                >
                    Warna
                </th>
                <th
                    style="
                        border: 1px solid black;
                        background-color: #F1A983;
                        text-align: center;
                        vertical-align: middle;"
                >
                    Masuk
                </th>
                <th
                    style="
                        border: 1px solid black;
                        background-color: #F1A983;
                        text-align: center;
                        vertical-align: middle;"
                >
                    Keluar
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stockMutations as $key => $stockMutation )
                <tr>
                    <td style="text-align: center">{{ $key+1 }}</td>
                    <td>{{ $stockMutation->code }}</td>
                    <td>{{ $stockMutation->name }}</td>
                    <td>{{ $stockMutation->color }}</td>
                    <td>{{ $stockMutation->begin_stock }}</td>
                    <td>{{ $stockMutation->total_qty_in }}</td>
                    <td>{{ $stockMutation->total_qty_out }}</td>
                    <td>{{ $stockMutation->ending_stock }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
