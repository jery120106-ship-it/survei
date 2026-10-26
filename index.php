<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_edusmart";

$conn = mysqli_connect($host, $user, $pass, $db);

// --- PROSES AJAX SIMPAN DATA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_action'])) {
    error_reporting(0);
    header('Content-Type: application/json');

    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "Koneksi database gagal."]);
        exit;
    }

    $nama      = mysqli_real_escape_string($conn, $_POST['nama']);
    $usia      = mysqli_real_escape_string($conn, $_POST['usia']);
    $jk        = mysqli_real_escape_string($conn, $_POST['jk']);
    $status    = mysqli_real_escape_string($conn, $_POST['status']);
    $frekuensi = mysqli_real_escape_string($conn, $_POST['frekuensi']);
    $merek     = mysqli_real_escape_string($conn, $_POST['merek']);

    $query = "INSERT INTO hasil_survey (nama, usia, jk, status, frekuensi, merek) 
              VALUES ('$nama', '$usia', '$jk', '$status', '$frekuensi', '$merek')";

    if (mysqli_query($conn, $query)) {
        echo json_encode(["status" => "success", "message" => "Data berhasil disimpan ke database XAMPP!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan: " . mysqli_error($conn)]);
    }
    exit;
}

// --- AMBIL DATA STATISTIK & TABEL DARI DATABASE ---
$total_responden = 0;
$result_data = [];
$stats_merek = [];
$stats_frekuensi = [];
$stats_usia = [];

if ($conn) {
    // Total Responden
    $res_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM hasil_survey");
    if ($res_count) {
        $row = mysqli_fetch_assoc($res_count);
        $total_responden = $row['total'];
    }

    // Statistik Merek
    $res_merek = mysqli_query($conn, "SELECT merek, COUNT(*) as jumlah FROM hasil_survey GROUP BY merek");
    if ($res_merek) {
        while ($row = mysqli_fetch_assoc($res_merek)) {
            $stats_merek[$row['merek']] = $row['jumlah'];
        }
    }

    // Statistik Frekuensi
    $res_freq = mysqli_query($conn, "SELECT frekuensi, COUNT(*) as jumlah FROM hasil_survey GROUP BY frekuensi");
    if ($res_freq) {
        while ($row = mysqli_fetch_assoc($res_freq)) {
            $stats_frekuensi[$row['frekuensi']] = $row['jumlah'];
        }
    }

    // Statistik Usia
    $res_usia = mysqli_query($conn, "SELECT usia, COUNT(*) as jumlah FROM hasil_survey GROUP BY usia");
    if ($res_usia) {
        while ($row = mysqli_fetch_assoc($res_usia)) {
            $stats_usia[$row['usia']] = $row['jumlah'];
        }
    }

    // Data Tabel Seluruh Responden (Urut dari terbaru)
    $res_list = mysqli_query($conn, "SELECT * FROM hasil_survey ORDER BY id DESC");
    if ($res_list) {
        while ($row = mysqli_fetch_assoc($res_list)) {
            $result_data[] = $row;
        }
    }
}
?>
<!doctype html>
<html lang="id" class="scroll-smooth">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Survey Pemilihan Merek Air Mineral</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js untuk Statistik -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
      body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: radial-gradient(circle at 10% 10%, rgba(6, 182, 212, 0.08), transparent 40%),
                    radial-gradient(circle at 90% 90%, rgba(37, 99, 235, 0.08), transparent 40%),
                    #f8fafc;
      }
      .glass-panel {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
      }
      .water-gradient {
        background: linear-gradient(135deg, #0284c7 0%, #06b6d4 50%, #2563eb 100%);
      }
      .question-card {
        transition: all 0.25s ease;
      }
      .question-card:hover {
        border-color: #38bdf8;
        box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.08);
      }
      .option-item {
        transition: all 0.2s ease;
      }
      .option-item:hover {
        background-color: #f0f9ff;
        border-color: #bae6fd;
        transform: translateX(4px);
      }
    </style>
  </head>
  <body class="text-slate-800 antialiased min-h-screen flex flex-col justify-between selection:bg-cyan-500 selection:text-white">
    
    <!-- NAVBAR -->
    <header class="sticky top-0 z-50 glass-panel border-b border-slate-200/80">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
        <div class="flex items-center space-x-3.5">
          <div class="w-11 h-11 rounded-2xl water-gradient flex items-center justify-center text-white shadow-lg shadow-cyan-500/25">
            <i data-lucide="droplet" class="w-6 h-6 fill-current"></i>
          </div>
          <div>
            <span class="text-xl font-extrabold bg-gradient-to-r from-blue-700 to-cyan-600 bg-clip-text text-transparent">HydroSurvey</span>
            <div class="flex items-center gap-1.5 mt-0.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">XAMPP Database Live</span>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-700 bg-slate-100 px-4 py-2 rounded-full border border-slate-200 shadow-sm">
          <i data-lucide="users" class="w-4 h-4 text-cyan-600"></i> Total Responden: <span id="counterTotal" class="font-extrabold text-blue-600 text-sm"><?php echo $total_responden; ?></span>
        </div>
      </div>
    </header>

    <!-- HERO SECTION -->
    <section class="water-gradient text-white py-14 px-4 sm:px-6 relative overflow-hidden shadow-lg">
      <div class="absolute -right-20 -top-20 w-72 h-72 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
      <div class="absolute -left-20 -bottom-20 w-72 h-72 rounded-full bg-black/10 blur-3xl pointer-events-none"></div>
      
      <div class="max-w-2xl mx-auto text-center relative z-10 space-y-3">
        <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold bg-white/20 text-white backdrop-blur-md shadow-sm">
          <i data-lucide="sparkles" class="w-3.5 h-3.5"></i> Riset Preferensi Konsumen
        </span>
        <h1 class="text-3xl sm:text-4xl font-black tracking-tight leading-tight">Survey Pemilihan Merek Air Mineral</h1>
        <p class="text-cyan-100 max-w-lg mx-auto text-sm sm:text-base leading-relaxed">
          Platform pengumpulan data preferensi konsumen air mineral kemasan terintegrasi dengan database lokal MySQL XAMPP.
        </p>
      </div>
    </section>

    <!-- MAIN CONTAINER -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 flex-1 w-full -mt-8 relative z-20 space-y-10">
      
      <!-- DASHBOARD STATISTIK UTAMA -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Grafik 1: Merek -->
        <div class="glass-panel rounded-3xl p-6 shadow-xl border border-slate-200 flex flex-col justify-between">
          <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2 mb-4">
            <i data-lucide="pie-chart" class="w-4 h-4 text-cyan-600"></i> Preferensi Merek
          </h3>
          <div class="relative h-48 flex justify-center items-center">
            <canvas id="merekChart"></canvas>
          </div>
        </div>

        <!-- Grafik 2: Frekuensi -->
        <div class="glass-panel rounded-3xl p-6 shadow-xl border border-slate-200 flex flex-col justify-between">
          <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2 mb-4">
            <i data-lucide="bar-chart-2" class="w-4 h-4 text-blue-600"></i> Frekuensi Konsumsi
          </h3>
          <div class="relative h-48 flex justify-center items-center">
            <canvas id="frekuensiChart"></canvas>
          </div>
        </div>

        <!-- Grafik 3: Usia -->
        <div class="glass-panel rounded-3xl p-6 shadow-xl border border-slate-200 flex flex-col justify-between">
          <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2 mb-4">
            <i data-lucide="users" class="w-4 h-4 text-emerald-600"></i> Kelompok Usia
          </h3>
          <div class="relative h-48 flex justify-center items-center">
            <canvas id="usiaChart"></canvas>
          </div>
        </div>

      </div>

      <!-- FORM SURVEY -->
      <form id="googleSurveyForm" class="space-y-6">
        <div class="glass-panel rounded-3xl p-6 sm:p-8 border-t-4 border-cyan-500 shadow-xl">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="file-text" class="text-cyan-600 w-5 h-5"></i> Formulir Responden Baru
              </h2>
              <p class="text-xs text-slate-500 mt-1">Isi formulir di bawah ini untuk mengirim data ke database.</p>
            </div>
            <span class="text-xs font-bold px-3 py-1 bg-red-50 text-red-600 rounded-lg border border-red-100">* Wajib diisi</span>
          </div>
        </div>

        <!-- 1. Nama -->
        <div class="question-card glass-panel rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm">
          <label class="block text-sm font-bold text-slate-900 mb-2">1. Nama / Inisial <span class="text-red-500">*</span></label>
          <input type="text" id="inputNama" required placeholder="Contoh: Budi Santoso" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/70 focus:bg-white focus:ring-2 focus:ring-cyan-500 outline-none text-sm font-medium transition" />
        </div>

        <!-- 2. Usia -->
        <div class="question-card glass-panel rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm">
          <label class="block text-sm font-bold text-slate-900 mb-3">2. Berapa kelompok usia Anda? <span class="text-red-500">*</span></label>
          <div class="space-y-2.5 text-sm text-slate-700">
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="usia" value="< 15 Tahun" required class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">&lt; 15 Tahun</span></label>
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="usia" value="15 - 20 Tahun" class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">15 - 20 Tahun</span></label>
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="usia" value="21 - 30 Tahun" class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">21 - 30 Tahun</span></label>
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="usia" value="> 31 Tahun" class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">&gt; 31 Tahun</span></label>
          </div>
        </div>

        <!-- 3. Jenis Kelamin -->
        <div class="question-card glass-panel rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm">
          <label class="block text-sm font-bold text-slate-900 mb-3">3. Jenis Kelamin <span class="text-red-500">*</span></label>
          <div class="space-y-2.5 text-sm text-slate-700">
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="jk" value="Laki-laki" required class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">Laki-laki</span></label>
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="jk" value="Perempuan" class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">Perempuan</span></label>
          </div>
        </div>

        <!-- 4. Status -->
        <div class="question-card glass-panel rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm">
          <label class="block text-sm font-bold text-slate-900 mb-3">4. Status Anda saat ini <span class="text-red-500">*</span></label>
          <div class="space-y-2.5 text-sm text-slate-700">
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="status" value="Pelajar" required class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">Pelajar</span></label>
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="status" value="Mahasiswa" class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">Mahasiswa</span></label>
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="status" value="Karyawan" class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">Karyawan</span></label>
            <div class="option-item flex items-center gap-3 p-3 rounded-xl border border-slate-200/60 bg-slate-50/50">
              <input type="radio" name="status" id="statusOtherRadio" value="Yang lain" class="w-4 h-4 text-cyan-600 accent-cyan-600" />
              <span class="whitespace-nowrap font-medium">Lainnya:</span>
              <input type="text" id="statusLainInput" placeholder="Tuliskan status..." class="flex-1 px-3 py-2 rounded-lg border border-slate-200 bg-white text-xs outline-none focus:ring-2 focus:ring-cyan-500 font-medium" disabled />
            </div>
          </div>
        </div>

        <!-- 5. Frekuensi -->
        <div class="question-card glass-panel rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm">
          <label class="block text-sm font-bold text-slate-900 mb-3">5. Seberapa sering Anda mengonsumsi air mineral kemasan? <span class="text-red-500">*</span></label>
          <div class="space-y-2.5 text-sm text-slate-700">
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="frekuensi" value="Setiap hari" required class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">Setiap hari</span></label>
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="frekuensi" value="4-6 kali seminggu" class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">4-6 kali seminggu</span></label>
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="frekuensi" value="1-3 kali seminggu" class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">1-3 kali seminggu</span></label>
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="frekuensi" value="Jarang" class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">Jarang</span></label>
          </div>
        </div>

        <!-- 6. Merek -->
        <div class="question-card glass-panel rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm">
          <label class="block text-sm font-bold text-slate-900 mb-3">6. Merek air mineral apa yang paling sering Anda konsumsi? <span class="text-red-500">*</span></label>
          <div class="space-y-2.5 text-sm text-slate-700">
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="merek" value="Aqua" required class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">Aqua</span></label>
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="merek" value="Le minerale" class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">Le Minerale</span></label>
            <label class="option-item flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50 cursor-pointer"><input type="radio" name="merek" value="Cleo" class="w-4 h-4 text-cyan-600 accent-cyan-600" /><span class="font-medium">Cleo</span></label>
            <div class="option-item flex items-center gap-3 p-3 rounded-xl border border-slate-200/60 bg-slate-50/50">
              <input type="radio" name="merek" id="merekOtherRadio" value="Yang lain" class="w-4 h-4 text-cyan-600 accent-cyan-600" />
              <span class="whitespace-nowrap font-medium">Lainnya:</span>
              <input type="text" id="merekLainInput" placeholder="Tuliskan merek..." class="flex-1 px-3 py-2 rounded-lg border border-slate-200 bg-white text-xs outline-none focus:ring-2 focus:ring-cyan-500 font-medium" disabled />
            </div>
          </div>
        </div>

        <!-- TOMBOL KIRIM -->
        <div class="pt-2">
          <button type="submit" class="w-full py-4 px-8 rounded-2xl water-gradient text-white font-extrabold shadow-xl shadow-cyan-500/30 hover:opacity-95 active:scale-[0.99] transition flex items-center justify-center gap-3 text-base">
            <i data-lucide="send" class="w-5 h-5"></i> Kirim Jawaban ke Database XAMPP
          </button>
        </div>
      </form>

      <!-- TABEL DATA DARI DATABASE DENGAN FITUR PENCARIAN -->
      <div class="glass-panel rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-200 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
          <div>
            <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
              <i data-lucide="database" class="w-5 h-5 text-blue-600"></i> Daftar Seluruh Responden
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">Data langsung diambil dari tabel database MySQL.</p>
          </div>
          <div class="flex items-center gap-3">
            <input type="text" id="searchTable" placeholder="Cari nama / merek..." class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs outline-none focus:ring-2 focus:ring-cyan-500 w-48 font-medium" onkeyup="filterTable()" />
            <button onclick="location.reload()" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5">
              <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
            </button>
          </div>
        </div>

        <div class="overflow-x-auto max-h-96">
          <table id="respondenTable" class="w-full text-left border-collapse text-xs">
            <thead class="sticky top-0 bg-slate-100 text-slate-700 border-b border-slate-200 shadow-sm">
              <tr>
                <th class="p-3 font-bold">#</th>
                <th class="p-3 font-bold">Nama</th>
                <th class="p-3 font-bold">Usia</th>
                <th class="p-3 font-bold">Gender</th>
                <th class="p-3 font-bold">Status</th>
                <th class="p-3 font-bold">Frekuensi</th>
                <th class="p-3 font-bold">Merek</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php if (count($result_data) > 0): ?>
                <?php $no = 1; foreach ($result_data as $row): ?>
                  <tr class="hover:bg-slate-50/80 transition">
                    <td class="p-3 text-slate-500"><?php echo $no++; ?></td>
                    <td class="p-3 font-bold text-slate-900"><?php echo htmlspecialchars($row['nama']); ?></td>
                    <td class="p-3 text-slate-600"><?php echo htmlspecialchars($row['usia']); ?></td>
                    <td class="p-3 text-slate-600"><?php echo htmlspecialchars($row['jk']); ?></td>
                    <td class="p-3 text-slate-600"><?php echo htmlspecialchars($row['status']); ?></td>
                    <td class="p-3 text-slate-600"><?php echo htmlspecialchars($row['frekuensi']); ?></td>
                    <td class="p-3"><span class="px-2.5 py-1 rounded-md bg-cyan-50 text-cyan-700 font-bold border border-cyan-100"><?php echo htmlspecialchars($row['merek']); ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="p-6 text-center text-slate-400 italic">Belum ada data survei di database.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-slate-400 text-xs py-8 mt-16 border-t border-slate-800">
      <div class="max-w-5xl mx-auto px-4 text-center space-y-2">
        <p class="font-medium text-slate-300">© 2026 HydroSurvey - Sistem Pengumpulan Data Konsumen</p>
        <p class="text-slate-500">Terintegrasi dengan XAMPP MySQL & Tailwind CSS</p>
      </div>
    </footer>

    <!-- JAVASCRIPT LOGIC & CHARTS -->
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        lucide.createIcons();
        const form = document.getElementById("googleSurveyForm");
        if (form) {
          form.addEventListener("submit", handleFormSubmit);
        }
        setupConditionalInput("statusOtherRadio", "statusLainInput");
        setupConditionalInput("merekOtherRadio", "merekLainInput");
        initCharts();
      });

      function initCharts() {
        // Data dari PHP
        const merekData = <?php echo json_encode($stats_merek); ?>;
        const freqData = <?php echo json_encode($stats_frekuensi); ?>;
        const usiaData = <?php echo json_encode($stats_usia); ?>;

        // 1. Chart Merek (Doughnut)
        new Chart(document.getElementById('merekChart').getContext('2d'), {
          type: 'doughnut',
          data: {
            labels: Object.keys(merekData).length ? Object.keys(merekData) : ['Kosong'],
            datasets: [{
              data: Object.keys(merekData).length ? Object.values(merekData) : [1],
              backgroundColor: ['#0284c7', '#06b6d4', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
              borderWidth: 2, borderColor: '#ffffff'
            }]
          },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
        });

        // 2. Chart Frekuensi (Bar)
        new Chart(document.getElementById('frekuensiChart').getContext('2d'), {
          type: 'bar',
          data: {
            labels: Object.keys(freqData).length ? Object.keys(freqData) : ['Kosong'],
            datasets: [{
              label: 'Jumlah',
              data: Object.keys(freqData).length ? Object.values(freqData) : [0],
              backgroundColor: '#3b82f6',
              borderRadius: 6
            }]
          },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });

        // 3. Chart Usia (Pie)
        new Chart(document.getElementById('usiaChart').getContext('2d'), {
          type: 'pie',
          data: {
            labels: Object.keys(usiaData).length ? Object.keys(usiaData) : ['Kosong'],
            datasets: [{
              data: Object.keys(usiaData).length ? Object.values(usiaData) : [1],
              backgroundColor: ['#10b981', '#06b6d4', '#8b5cf6', '#f472b6'],
              borderWidth: 2, borderColor: '#ffffff'
            }]
          },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
        });
      }

      function filterTable() {
        const input = document.getElementById("searchTable").value.toLowerCase();
        const rows = document.querySelectorAll("#respondenTable tbody tr");
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(input) ? "" : "none";
        });
      }

      function setupConditionalInput(radioId, inputId) {
        const targetRadio = document.getElementById(radioId);
        const input = document.getElementById(inputId);
        if (!targetRadio || !input) return;
        const radioGroup = document.querySelectorAll(`input[name="${targetRadio.name}"]`);
        radioGroup.forEach(radio => {
          radio.addEventListener("change", function () {
            if (targetRadio.checked) {
              input.disabled = false;
              input.required = true;
              input.focus();
            } else {
              input.disabled = true;
              input.required = false;
              input.value = "";
            }
          });
        });
      }

      function handleFormSubmit(event) {
        event.preventDefault();

        const nama = document.getElementById("inputNama")?.value.trim() || "";
        const usia = document.querySelector('input[name="usia"]:checked')?.value || "";
        const jk = document.querySelector('input[name="jk"]:checked')?.value || "";

        let status = document.querySelector('input[name="status"]:checked')?.value || "";
        if (status === "Yang lain") {
          status = document.getElementById("statusLainInput")?.value.trim() || "";
        }

        const frekuensi = document.querySelector('input[name="frekuensi"]:checked')?.value || "";

        let merek = document.querySelector('input[name="merek"]:checked')?.value || "";
        if (merek === "Yang lain") {
          merek = document.getElementById("merekLainInput")?.value.trim() || "";
        }

        if (!nama || !usia || !jk || !status || !frekuensi || !merek) {
          alert("Harap lengkapi semua data survey terlebih dahulu!");
          return;
        }

        const formData = new URLSearchParams();
        formData.append("ajax_action", "save");
        formData.append("nama", nama);
        formData.append("usia", usia);
        formData.append("jk", jk);
        formData.append("status", status);
        formData.append("frekuensi", frekuensi);
        formData.append("merek", merek);

        fetch("index.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: formData.toString(),
        })
          .then((response) => response.text())
          .then((text) => {
            try {
              return JSON.parse(text);
            } catch (e) {
              console.error("Respon Server Bukan JSON:", text);
              throw new Error("Server mengembalikan format non-JSON.");
            }
          })
          .then((data) => {
            if (data.status === "success") {
              alert(data.message);
              document.getElementById("googleSurveyForm").reset();
              location.reload();
            } else {
              alert("Gagal menyimpan: " + data.message);
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            alert("Gagal terhubung ke database. Pastikan XAMPP Apache & MySQL menyala.");
          });
      }
    </script>
  </body>
</html>