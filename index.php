<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV E-Faktur to Flat Table Converter - v3</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f9f9f9; color: #333; }
        h2 { color: #2c3e50; }
        textarea { width: 100%; height: 150px; padding: 10px; box-sizing: border-box; }
        .btn-container { margin-top: 10px; }
        button { padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; margin-right: 10px; border-radius: 4px; }
        .btn-proses { background-color: #27ae60; color: white; }
        .btn-proses:hover { background-color: #219150; }
        .btn-download { background-color: #2980b9; color: white; }
        .btn-download:hover { background-color: #1f6391; }
        .table-container { overflow-x: auto; margin-top: 25px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; background: white; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; white-space: nowrap; }
        th { background-color: #2c3e50; color: white; position: sticky; top: 0; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>

    <h2>Converter CSV e-Faktur ke Tabel Datar</h2>
    <form method="POST">
        <label for="csv_data">Paste Data CSV di sini:</label>
        <textarea name="csv_data" id="csv_data" required placeholder="FK,KD_JENIS_TRANSAKSI,...\nOF,KODE_OBJEK,..."><?= isset($_POST['csv_data']) ? htmlspecialchars($_POST['csv_data']) : '' ?></textarea>
        <div class="btn-container">
            <button type="submit" class="btn-proses">Proses Data</button>
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['csv_data'])): ?>
                <button type="button" class="btn-download" onclick="downloadCSV()">Download CSV</button>
            <?php endif; ?>
        </div>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['csv_data'])) {
        $raw_data = trim($_POST['csv_data']);
        $lines = explode("\n", $raw_data);

        $current_fk = [];
        $current_fapr = ''; 
        $flat_rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $data = str_getcsv($line, ",");
            $prefix = $data[0];

            if (in_array($data[1], ['KD_JENIS_TRANSAKSI', 'NPWP', 'KODE_OBJEK'])) {
                continue;
            }

            switch ($prefix) {
                case 'FK':
                    // Mengubah format tanggal dd/mm/yyyy -> yyyy/mm/dd
                    $formatted_date = '';
                    if (!empty($data[6])) {
                        $date_parts = explode('/', $data[6]); // Memecah string tanggal
                        if (count($date_parts) === 3) {
                            // $date_parts[0] = dd, $date_parts[1] = mm, $date_parts[2] = yyyy
                            $formatted_date = $date_parts[2] . '/' . $date_parts[1] . '/' . $date_parts[0];
                        } else {
                            $formatted_date = $data[6]; // fallback jika format tidak sesuai
                        }
                    }

                    $current_fk = [
                        'tanggal'   => $formatted_date,
                        'npwp'      => $data[7] ?? '',
                        'nama'      => $data[8] ?? '',
                        'referensi' => $data[18] ?? ''
                    ];
                    break;

                case 'FAPR':
                case 'LT':
                    $current_fapr = $data[1] ?? '';
                    break;

                case 'OF':
                    $flat_rows[] = [
                        'tanggal_faktur' => $current_fk['tanggal'] ?? '',
                        'npwp'           => $current_fk['npwp'] ?? '',
                        'nama_pembeli'   => $current_fk['nama'] ?? '',
                        'toko_fapr'      => $current_fapr,
                        'kode_objek'     => $data[1] ?? '',
                        'nama_objek'     => $data[2] ?? '',
                        'harga_satuan'   => $data[3] ?? 0,
                        'jumlah_barang'  => $data[4] ?? 0,
                        'harga_total'    => $data[5] ?? 0,
                        'dpp'            => $data[7] ?? 0,
                        'ppn'            => $data[8] ?? 0,
                        'referensi'      => $current_fk['referensi'] ?? ''
                    ];
                    break;
            }
        }

        if (!empty($flat_rows)) {
            echo '<div class="table-container">';
            echo '<table id="flatTable">';
            echo '<thead>
                    <tr>
                        <th>Tanggal Faktur</th>
                        <th>NPWP</th>
                        <th>Nama Pembeli</th>
                        <th>Toko (FAPR/LT)</th>
                        <th>Kode Objek (Barang)</th>
                        <th>Nama Objek</th>
                        <th>Harga Satuan</th>
                        <th>Qty</th>
                        <th>Harga Total</th>
                        <th>DPP</th>
                        <th>PPN</th>
                        <th>Referensi</th>
                    </tr>
                  </thead>';
            echo '<tbody>';
            foreach ($flat_rows as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['tanggal_faktur']) . '</td>';
                echo '<td>' . htmlspecialchars($row['npwp']) . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_pembeli']) . '</td>';
                echo '<td>' . htmlspecialchars($row['toko_fapr']) . '</td>';
                echo '<td>' . htmlspecialchars($row['kode_objek']) . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_objek']) . '</td>';
                echo '<td>' . (float)$row['harga_satuan'] . '</td>';
                echo '<td>' . htmlspecialchars($row['jumlah_barang']) . '</td>';
                echo '<td>' . (float)$row['harga_total'] . '</td>';
                echo '<td>' . (float)$row['dpp'] . '</td>';
                echo '<td>' . (float)$row['ppn'] . '</td>';
                echo '<td>' . htmlspecialchars($row['referensi']) . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        } else {
            echo '<p style="color:red; margin-top:20px;">Tidak ada data transaksi (OF) valid yang ditemukan untuk diratakan.</p>';
        }
    }
    ?>

    <script>
    function downloadCSV() {
        var csv = [];
        var rows = document.querySelectorAll("#flatTable tr");
        
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");
            
            for (var j = 0; j < cols.length; j++) {
                var text = cols[j].innerText.trim();
                if (text.includes(",") || text.includes("\n") || text.includes('"')) {
                    text = '"' + text.replace(/"/g, '""') + '"';
                }
                row.push(text);
            }
            csv.push(row.join(","));        
        }

        var csv_string = csv.join("\n");
        var filename = 'flat_efaktur_' + new Date().toISOString().slice(0,10) + '.csv';
        var blob = new Blob(["\ufeff" + csv_string], { type: 'text/csv;charset=utf-8;' });
        
        var link = document.createElement("a");
        if (link.download !== undefined) {
            var url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
    </script>
</body>
</html>