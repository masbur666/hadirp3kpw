<?php
// --- KONFIGURASI DATABASE ---
$host = 'localhost';
$user = 'beetvmyi_presensipw'; 
$pass = 'Segawon_666';       
$db   = 'beetvmyi_presensipw'; 

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    // Fallback untuk testing lokal jika kredensial di atas gagal
    $conn = mysqli_connect('localhost', 'root', '', 'presensi_db'); 
    if (!$conn) die("Koneksi gagal: " . mysqli_connect_error());
}

// Set Timezone
date_default_timezone_set('Asia/Jakarta');

// --- FITUR BARU: OTOMATISASI PRESENSI (AUTO-FILL) ---
// Logika: Jika lewat 07:30 dan bukan weekend, pegawai yang belum absen akan diisi otomatis (06:45 - 07:30)
$jam_sekarang_int = (int)date('Hi'); // Format Hi (contoh 0730)
$hari_ini_en = date('l');
$is_weekend = ($hari_ini_en == 'Saturday' || $hari_ini_en == 'Sunday');

if (!$is_weekend && $jam_sekarang_int > 730) {
    $tgl_today = date('Y-m-d');
    
    // Ambil semua pegawai
    $q_all_peg = mysqli_query($conn, "SELECT id FROM pegawai");
    while($pg = mysqli_fetch_assoc($q_all_peg)) {
        // Cek apakah sudah ada presensi hari ini
        $chk = mysqli_query($conn, "SELECT id FROM presensi WHERE pegawai_id='{$pg['id']}' AND tanggal='$tgl_today'");
        if(mysqli_num_rows($chk) == 0) {
            // Belum absen, buatkan jam acak 06:45 s/d 07:30
            // 06:45 = (6*60)+45 = 405 menit
            // 07:30 = (7*60)+30 = 450 menit
            $rand_menit = rand(405, 450);
            $jam_rnd = floor($rand_menit / 60);
            $menit_rnd = $rand_menit % 60;
            $waktu_random = sprintf("%02d:%02d:00", $jam_rnd, $menit_rnd);
            
            // Insert otomatis
            $q_auto = "INSERT INTO presensi (pegawai_id, tanggal, jam_masuk, status, keterangan) 
                       VALUES ('{$pg['id']}', '$tgl_today', '$waktu_random', 'Hadir', 'Otomatis System')";
            mysqli_query($conn, $q_auto);
        }
    }
}
// -----------------------------------------------------

// Array Hari
$nama_hari_indo = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];

// Array Bulan Indo
$bulan_indo = [
    '01' => 'JANUARI', '02' => 'FEBRUARI', '03' => 'MARET', '04' => 'APRIL', '05' => 'MEI', '06' => 'JUNI',
    '07' => 'JULI', '08' => 'AGUSTUS', '09' => 'SEPTEMBER', '10' => 'OKTOBER', '11' => 'NOVEMBER', '12' => 'DESEMBER'
];

// --- LOGIC HANDLER (BACKEND) ---

// 1. Simpan Pegawai
if (isset($_POST['action']) && $_POST['action'] == 'save_pegawai') {
    $nip = mysqli_real_escape_string($conn, $_POST['nip']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $id = $_POST['id'] ?? '';

    if ($id) {
        $query = "UPDATE pegawai SET nip='$nip', nama='$nama', jabatan='$jabatan', no_hp='$no_hp' WHERE id='$id'";
    } else {
        $query = "INSERT INTO pegawai (nip, nama, jabatan, no_hp) VALUES ('$nip', '$nama', '$jabatan', '$no_hp')";
    }
    mysqli_query($conn, $query);
    header("Location: index.php?tab=pegawai");
    exit;
}

// 2. Hapus Pegawai
if (isset($_GET['delete_pegawai'])) {
    $id = $_GET['delete_pegawai'];
    mysqli_query($conn, "DELETE FROM pegawai WHERE id='$id'");
    header("Location: index.php?tab=pegawai");
    exit;
}

// 3. Input Presensi
if (isset($_POST['action']) && $_POST['action'] == 'input_presensi') {
    $pegawai_id = $_POST['pegawai_id'];
    $tanggal = $_POST['tanggal'];
    $jenis = $_POST['jenis']; 
    $waktu = date('H:i:s'); 
    
    // PERBAIKAN LOGIKA STATUS:
    if ($jenis == 'masuk' || $jenis == 'pulang') {
        $status_input = 'Hadir';
        $keterangan = ''; 
    } else {
        $status_input = $_POST['status_presensi']; 
        $keterangan = $_POST['keterangan'] ?? '';
    }

    // Cek data hari ini
    $cek = mysqli_query($conn, "SELECT * FROM presensi WHERE pegawai_id='$pegawai_id' AND tanggal='$tanggal'");
    $row = mysqli_fetch_assoc($cek);

    // FITUR BARU: VALIDASI DUPLIKASI DATA
    if ($jenis == 'masuk') {
        if ($row) {
            // Jika sudah ada jam masuk, tolak
            if (!empty($row['jam_masuk'])) {
                header("Location: index.php?tab=presensi&error=masuk_exist");
                exit;
            }
            $q = "UPDATE presensi SET jam_masuk='$waktu', status='Hadir' WHERE id='{$row['id']}'";
        } else {
            $q = "INSERT INTO presensi (pegawai_id, tanggal, jam_masuk, status, keterangan) VALUES ('$pegawai_id', '$tanggal', '$waktu', 'Hadir', '')";
        }
    } elseif ($jenis == 'pulang') {
        if ($row) {
            // Jika sudah ada jam pulang, tolak
            if (!empty($row['jam_pulang'])) {
                header("Location: index.php?tab=presensi&error=pulang_exist");
                exit;
            }
            $q = "UPDATE presensi SET jam_pulang='$waktu' WHERE id='{$row['id']}'";
        } else {
            // Pulang tanpa masuk (jarang, tapi mungkin)
            $q = "INSERT INTO presensi (pegawai_id, tanggal, jam_pulang, status, keterangan) VALUES ('$pegawai_id', '$tanggal', '$waktu', 'Hadir', '')";
        }
    } elseif ($jenis == 'keterangan') {
        $jam_dinas = ($status_input == 'Dinas') ? $waktu : NULL;
        
        if ($row) {
            $q_add = ($status_input == 'Dinas') ? ", jam_masuk='$waktu'" : "";
            $q = "UPDATE presensi SET status='$status_input', keterangan='$keterangan' $q_add WHERE id='{$row['id']}'";
        } else {
            $q = "INSERT INTO presensi (pegawai_id, tanggal, jam_masuk, status, keterangan) VALUES ('$pegawai_id', '$tanggal', '$jam_dinas', '$status_input', '$keterangan')";
        }
    }

    if(isset($q)) mysqli_query($conn, $q);
    header("Location: index.php?tab=presensi&msg=sukses");
    exit;
}

// 4. Export Excel & Print PDF View
if (isset($_GET['action']) && ($_GET['action'] == 'export_excel' || $_GET['action'] == 'print_pdf')) {
    $bulan = $_GET['bulan']; 
    $tahun_bulan_obj = DateTime::createFromFormat('Y-m', $bulan);
    
    $m_angka = $tahun_bulan_obj->format('m');
    $y_angka = $tahun_bulan_obj->format('Y');
    
    // Nama Bulan untuk Judul (Kapital semua)
    $nama_bulan_judul = $bulan_indo[$m_angka] . ' ' . $y_angka;
    
    // Hitung tgl tanda tangan (Tanggal 1 bulan berikutnya)
    $next_month = clone $tahun_bulan_obj;
    $next_month->modify('+1 month');
    $m_next = $next_month->format('m');
    $y_next = $next_month->format('Y');
    
    // Format Tgl TTD: 1 Februari 2026 (Title Case untuk nama bulan di TTD)
    $bulan_indo_kapital = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $tgl_ttd_str = "1 " . $bulan_indo_kapital[$m_next] . " " . $y_next;

    $jml_hari = $tahun_bulan_obj->format('t');
    
    $mode = $_GET['action'];

    if ($mode == 'export_excel') {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Rekap_Presensi_$bulan.xls");
    }
    
    // Ambil Data
    $pegawais = mysqli_query($conn, "SELECT * FROM pegawai ORDER BY nama ASC");
    $presensi_raw = mysqli_query($conn, "SELECT * FROM presensi WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'");
    $data_presensi = [];
    while($p = mysqli_fetch_assoc($presensi_raw)) {
        $tgl = (int)date('d', strtotime($p['tanggal']));
        $data_presensi[$p['pegawai_id']][$tgl] = $p;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Cetak Presensi</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 11px; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            th, td { border: 1px solid black; padding: 2px; text-align: center; vertical-align: middle; }
            .text-left { text-align: left; }
            .header-kop { text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 10px; }
            .no-border { border: none !important; }
            
            @media print {
                @page { size: landscape; margin: 5mm; }
                .no-print { display: none; }
                body { -webkit-print-color-adjust: exact; }
                table { page-break-inside: auto; }
                tr { page-break-inside: avoid; page-break-after: auto; }
            }
        </style>
    </head>
    <body <?php if($mode == 'print_pdf') echo 'onload="window.print()"'; ?>>
        
        <?php if($mode == 'print_pdf'): ?>
            <div class="no-print" style="margin-bottom: 20px;">
                <button onclick="window.print()" style="padding: 10px 20px; cursor:pointer;">🖨️ Cetak / Simpan PDF</button>
                <button onclick="window.close()" style="padding: 10px 20px; cursor:pointer;">Tutup</button>
            </div>
        <?php endif; ?>

        <!-- KOP SURAT SESUAI TEMPLATE BIDANG ASET -->
        <div class="header-kop">
            DAFTAR HADIR PPPK-PW BIDANG ASET<br>
            BADAN KEUANGAN DAN ASET DAERAH<br>
            KABUPATEN GUNUNGKIDUL<br>
            BULAN <?= $nama_bulan_judul ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2" width="30">No</th>
                    <th rowspan="2" width="200">Nama<br>NIP</th>
                    <th rowspan="2" width="50">Jenis</th>
                    <th colspan="<?= $jml_hari ?>">TANGGAL</th>
                </tr>
                <tr>
                    <?php for($i=1; $i<=$jml_hari; $i++) echo "<th width='20'>$i</th>"; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($peg = mysqli_fetch_assoc($pegawais)): 
                ?>
                <!-- Baris M (Masuk) -->
                <tr>
                    <td rowspan="4"><?= $no++ ?></td>
                    <td rowspan="4" class="text-left" style="vertical-align: top; padding: 4px;">
                        <b><?= $peg['nama'] ?></b><br>
                        NIP. <?= $peg['nip'] ?>
                    </td>
                    <td>M</td> 
                    <?php for($d=1; $d<=$jml_hari; $d++): 
                        $val = '';
                        if(isset($data_presensi[$peg['id']][$d])) {
                            $p = $data_presensi[$peg['id']][$d];
                            if($p['status'] == 'Hadir' && !empty($p['jam_masuk'])) {
                                $val = date('H.i', strtotime($p['jam_masuk']));
                            }
                        }
                    ?>
                        <td><?= $val ?></td>
                    <?php endfor; ?>
                </tr>
                <!-- Baris P (Pulang) -->
                <tr>
                    <td>P</td> 
                    <?php for($d=1; $d<=$jml_hari; $d++): 
                        $val = '';
                        if(isset($data_presensi[$peg['id']][$d])) {
                            $p = $data_presensi[$peg['id']][$d];
                            if($p['status'] == 'Hadir' && !empty($p['jam_pulang'])) {
                                $val = date('H.i', strtotime($p['jam_pulang']));
                            }
                        }
                    ?>
                        <td><?= $val ?></td>
                    <?php endfor; ?>
                </tr>
                <!-- Baris D (Dinas) -->
                <tr>
                    <td>D</td> 
                    <?php for($d=1; $d<=$jml_hari; $d++): 
                        $val = '';
                        if(isset($data_presensi[$peg['id']][$d])) {
                            $p = $data_presensi[$peg['id']][$d];
                            if($p['status'] == 'Dinas' && !empty($p['jam_masuk'])) {
                                $val = date('H.i', strtotime($p['jam_masuk']));
                            }
                        }
                    ?>
                        <td><?= $val ?></td>
                    <?php endfor; ?>
                </tr>
                <!-- Baris L (Lainnya) -->
                <tr>
                    <td>L</td> 
                    <?php for($d=1; $d<=$jml_hari; $d++): 
                        $val = '';
                        if(isset($data_presensi[$peg['id']][$d])) {
                            $status = $data_presensi[$peg['id']][$d]['status'];
                            if(in_array($status, ['TK','CT','CS','CBN'])) $val = $status;
                        }
                    ?>
                        <td><?= $val ?></td>
                    <?php endfor; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- FOOTER TANDA TANGAN -->
        <table style="border: none; margin-top: 10px; width: 100%;">
            <tr style="border: none;">
                <!-- Kolom Keterangan (Paling Kiri, span full width dibawahnya nanti) -->
                <td colspan="3" style="border: none; text-align: left; padding-bottom: 10px;">
                    <b>KETERANGAN :</b><br>
                    M : Masuk (jam . menit) &nbsp;&nbsp; P : Pulang (jam . menit) &nbsp;&nbsp; D : Dinas (jam . menit)<br>
                    L : (TK: Tanpa Ket, CT: Cuti Tahunan, CS: Cuti Sakit, CBN: Cuti Bersalin)
                </td>
            </tr>
            <tr style="border: none;">
                <!-- Tanda Tangan KIRI -->
                <td style="border: none; width: 40%; text-align: center; vertical-align: top;">
                    Kepala Badan Keuangan dan Aset Daerah<br>
                    Kabupaten Gunungkidul<br>
                    <br><br><br><br>
                    <b><u>PUTRO SAPTO WAHYONO, S.IP., M.T.</u></b><br>
                    NIP. 19701117 199103 1 005
                </td>
                
                <!-- Spacer Tengah -->
                <td style="border: none; width: 20%;"></td>

                <!-- Tanda Tangan KANAN -->
                <td style="border: none; width: 40%; text-align: center; vertical-align: top;">
                    Wonosari, <?= $tgl_ttd_str ?><br>
                    Kepala Bidang Aset<br>
                    <br><br><br><br>
                    <b><u>DONNY PRASETYO WIDYA UTAMA, SH., M.H.</u></b><br>
                    NIP. 198406122010011024
                </td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Meta Viewport untuk HP -->
    <title>Aplikasi Presensi PPPK-PW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: none; }
        .nav-tabs .nav-link { color: #555; }
        .nav-tabs .nav-link.active { font-weight: bold; color: #0d6efd; border-bottom: 3px solid #0d6efd; background: transparent; }
        .main-container { max-width: 1000px; margin: 0 auto; padding-bottom: 60px; }
        
        @media (max-width: 576px) {
            .table-responsive { font-size: 12px; }
            .btn-lg { width: 100%; margin-bottom: 10px; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top mb-4 shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#"><i class="bi bi-clock-history"></i> E-Presensi PW</a>
  </div>
</nav>

<div class="container main-container">
    
    <?php $tab = $_GET['tab'] ?? 'presensi'; ?>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4 justify-content-center justify-content-lg-start">
        <li class="nav-item">
            <a class="nav-link <?= $tab=='presensi'?'active':'' ?>" href="?tab=presensi">
                <i class="bi bi-person-check"></i> Form Presensi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab=='pegawai'?'active':'' ?>" href="?tab=pegawai">
                <i class="bi bi-people"></i> Data Pegawai
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab=='laporan'?'active':'' ?>" href="?tab=laporan">
                <i class="bi bi-file-earmark-text"></i> Laporan
            </a>
        </li>
    </ul>

    <!-- ================= TAB PRESENSI ================= -->
    <?php if($tab == 'presensi'): ?>
    <div class="row justify-content-center">
        <!-- Form Input -->
        <div class="col-lg-5 col-md-12 mb-4">
            <div class="card p-4">
                <h5 class="text-center mb-4 fw-bold text-primary">Input Kehadiran</h5>
                
                <?php if(isset($_GET['msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> Data berhasil disimpan!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> 
                        <?php 
                        if($_GET['error'] == 'masuk_exist') echo "Anda sudah melakukan absen MASUK hari ini!";
                        elseif($_GET['error'] == 'pulang_exist') echo "Anda sudah melakukan absen PULANG hari ini!";
                        else echo "Terjadi kesalahan.";
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST" onsubmit="return validatePresensi(event)">
                    <input type="hidden" name="action" value="input_presensi">
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted">Tanggal</label>
                        <input type="date" name="tanggal" id="tgl_absen" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Nama Pegawai</label>
                        <select name="pegawai_id" class="form-select" required>
                            <option value="">-- Pilih Nama --</option>
                            <?php 
                            $q_peg = mysqli_query($conn, "SELECT * FROM pegawai ORDER BY nama ASC");
                            while($p = mysqli_fetch_assoc($q_peg)):
                            ?>
                                <option value="<?= $p['id'] ?>"><?= $p['nama'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Jenis Aktivitas</label>
                        <select name="jenis" class="form-select fw-bold" id="jenis_aksi" onchange="toggleStatus()" required>
                            <option value="masuk">🟢 Absen MASUK</option>
                            <option value="pulang">🔴 Absen PULANG</option>
                            <option value="keterangan">⚠️ Input Dinas / Izin / Cuti</option>
                        </select>
                    </div>

                    <!-- Input Khusus Keterangan -->
                    <div id="status_container" class="mb-3 bg-light p-3 rounded border" style="display:none;">
                        <label class="form-label text-danger fw-bold">Pilih Keterangan:</label>
                        <select name="status_presensi" id="status_presensi_input" class="form-select mb-2">
                            <option value="Dinas">Dinas Luar (DL)</option>
                            <option value="TK">Tanpa Keterangan (Alpa)</option>
                            <option value="CT">Cuti Tahunan</option>
                            <option value="CS">Cuti Sakit</option>
                            <option value="CBN">Cuti Bersalin</option>
                        </select>
                        <textarea name="keterangan" class="form-control form-control-sm" rows="2" placeholder="Detail kegiatan dinas atau alasan..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mt-2 fw-bold">
                        <i class="bi bi-send"></i> Kirim Data
                    </button>
                </form>
            </div>
        </div>
        
        <!-- List Hari Ini -->
        <div class="col-lg-7 col-md-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold">Rekap Hari Ini: <?= $nama_hari_indo[date('l')] . ', ' . date('d-m-Y') ?></h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama</th>
                                    <th>Status</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $tgl_today = date('Y-m-d');
                                $q_today = mysqli_query($conn, "SELECT p.*, pg.nama FROM presensi p JOIN pegawai pg ON p.pegawai_id = pg.id WHERE p.tanggal = '$tgl_today' ORDER BY p.jam_masuk DESC");
                                if(mysqli_num_rows($q_today) > 0):
                                    while($row = mysqli_fetch_assoc($q_today)):
                                ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold d-block"><?= $row['nama'] ?></span>
                                            <small class="text-muted"><?= $row['keterangan'] ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            if($row['status'] == 'Hadir') echo '<span class="badge bg-success">Hadir</span>';
                                            else if($row['status'] == 'Dinas') echo '<span class="badge bg-info text-dark">Dinas</span>';
                                            else echo '<span class="badge bg-warning text-dark">'.$row['status'].'</span>';
                                            ?>
                                        </td>
                                        <td style="min-width: 100px;">
                                            <small>
                                                <i class="bi bi-box-arrow-in-right text-success"></i> <?= $row['jam_masuk'] ? substr($row['jam_masuk'], 0, 5) : '--:--' ?><br>
                                                <i class="bi bi-box-arrow-left text-danger"></i> <?= $row['jam_pulang'] ? substr($row['jam_pulang'], 0, 5) : '--:--' ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada data presensi hari ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleStatus() {
            var jenis = document.getElementById('jenis_aksi').value;
            var container = document.getElementById('status_container');
            if(jenis == 'keterangan') {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        // FITUR BARU: VALIDASI WEEKEND (SABTU/MINGGU)
        function validatePresensi(event) {
            const dateInput = document.getElementById('tgl_absen').value;
            if (!dateInput) return true;

            const dateObj = new Date(dateInput);
            const day = dateObj.getDay(); // 0 = Minggu, 6 = Sabtu
            
            if (day === 0 || day === 6) {
                const confirmLembur = confirm("Hari ini adalah hari libur (Sabtu/Minggu). Apakah Anda yakin ingin melakukan presensi (Lembur)?");
                if (!confirmLembur) {
                    event.preventDefault(); // Batalkan submit jika user pilih Cancel
                    return false;
                }
            }
            return true;
        }
    </script>
    <?php endif; ?>


    <!-- ================= TAB PEGAWAI ================= -->
    <?php if($tab == 'pegawai'): ?>
    <div class="card p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-2">
            <h4 class="m-0 fw-bold">Data Pegawai</h4>
            <button class="btn btn-primary w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#modalPegawai">
                <i class="bi bi-plus-lg"></i> Tambah Pegawai
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>No. HP</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $q = mysqli_query($conn, "SELECT * FROM pegawai ORDER BY id DESC");
                    $no = 1;
                    while($row = mysqli_fetch_assoc($q)):
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= $row['nip'] ?></td>
                        <td class="fw-bold"><?= $row['nama'] ?></td>
                        <td><?= $row['jabatan'] ?></td>
                        <td><?= $row['no_hp'] ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-warning btn-edit text-white" 
                                    data-id="<?= $row['id'] ?>" 
                                    data-nip="<?= $row['nip'] ?>"
                                    data-nama="<?= $row['nama'] ?>"
                                    data-jabatan="<?= $row['jabatan'] ?>"
                                    data-hp="<?= $row['no_hp'] ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalPegawai"><i class="bi bi-pencil"></i></button>
                                <a href="index.php?delete_pegawai=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Pegawai -->
    <div class="modal fade" id="modalPegawai" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="index.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Data Pegawai</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_pegawai">
                        <input type="hidden" name="id" id="p_id">
                        
                        <div class="mb-3">
                            <label>NIP</label>
                            <input type="number" name="nip" id="p_nip" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" id="p_nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Jabatan</label>
                            <input type="text" name="jabatan" id="p_jabatan" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>No. WhatsApp / HP</label>
                            <input type="number" name="no_hp" id="p_hp" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        const editBtns = document.querySelectorAll('.btn-edit');
        editBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('p_id').value = this.dataset.id;
                document.getElementById('p_nip').value = this.dataset.nip;
                document.getElementById('p_nama').value = this.dataset.nama;
                document.getElementById('p_jabatan').value = this.dataset.jabatan;
                document.getElementById('p_hp').value = this.dataset.hp;
            });
        });
        document.getElementById('modalPegawai').addEventListener('hidden.bs.modal', function () {
             document.getElementById('p_id').value = '';
             document.querySelector('form').reset();
        });
    </script>
    <?php endif; ?>


    <!-- ================= TAB LAPORAN ================= -->
    <?php if($tab == 'laporan'): ?>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-5 text-center">
                <h3 class="mb-4">Cetak Laporan Bulanan</h3>
                <p class="text-muted mb-4">Pilih periode bulan untuk mencetak rekapitulasi kehadiran.</p>
                
                <form id="formCetak" method="GET" target="_blank" class="row g-3 justify-content-center">
                    <input type="hidden" name="action" id="action_type" value="">
                    
                    <div class="col-md-6 col-12">
                        <input type="month" name="bulan" class="form-control form-control-lg" value="<?= date('Y-m') ?>" required>
                    </div>
                    
                    <div class="col-12 mt-4 d-flex flex-column flex-md-row justify-content-center gap-3">
                        <button type="button" onclick="submitForm('export_excel')" class="btn btn-success btn-lg">
                            <i class="bi bi-file-earmark-excel"></i> Download Excel (.xlsx)
                        </button>
                        <button type="button" onclick="submitForm('print_pdf')" class="btn btn-danger btn-lg">
                            <i class="bi bi-file-earmark-pdf"></i> Cetak PDF / Print
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function submitForm(type) {
            document.getElementById('action_type').value = type;
            document.getElementById('formCetak').submit();
        }
    </script>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
