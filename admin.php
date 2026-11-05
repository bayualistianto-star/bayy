<?php
/* =====================================================================
   ROYAL KOS - ADMIN  (satu file lengkap)   Buka: localhost/admin.php
   ===================================================================== */
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* ================= KONEKSI & DATABASE OTOMATIS ================= */
$db = new mysqli('localhost', 'root', '');   // ubah password jika MySQL kamu memakai password
$db->query("CREATE DATABASE IF NOT EXISTS db_kos");
$db->select_db('db_kos');
$db->query("CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL)");
$db->query("CREATE TABLE IF NOT EXISTS kamar (
  id INT AUTO_INCREMENT PRIMARY KEY, no_kamar VARCHAR(10) NOT NULL UNIQUE,
  tipe VARCHAR(30) NOT NULL, harga INT NOT NULL, fasilitas VARCHAR(255),
  status ENUM('kosong','terisi') NOT NULL DEFAULT 'kosong')");
$db->query("CREATE TABLE IF NOT EXISTS penyewa (
  id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL, no_hp VARCHAR(20) NOT NULL,
  kamar_id INT NULL, tgl_masuk DATE NOT NULL,
  FOREIGN KEY (kamar_id) REFERENCES kamar(id) ON DELETE SET NULL)");
$db->query("CREATE TABLE IF NOT EXISTS pembayaran (
  id INT AUTO_INCREMENT PRIMARY KEY, penyewa_id INT NOT NULL, bulan CHAR(7) NOT NULL,
  jumlah INT NOT NULL, tgl_bayar DATE NULL,
  status ENUM('lunas','belum') NOT NULL DEFAULT 'belum',
  FOREIGN KEY (penyewa_id) REFERENCES penyewa(id) ON DELETE CASCADE)");
if ($db->query("SELECT COUNT(*) FROM kamar")->fetch_row()[0] == 0) {
  $db->query("INSERT INTO kamar (no_kamar,tipe,harga,fasilitas) VALUES
   ('A1','Standar',700000,'Kasur, lemari, kipas'),
   ('A2','Standar',700000,'Kasur, lemari, kipas'),
   ('B1','AC',1000000,'Kasur, lemari, AC, kamar mandi dalam')");
}
if (!$db->query("SHOW COLUMNS FROM penyewa LIKE 'password'")->num_rows)
  $db->query("ALTER TABLE penyewa ADD password VARCHAR(255) NULL");
$db->query("CREATE TABLE IF NOT EXISTS bukti_bayar (
  id INT AUTO_INCREMENT PRIMARY KEY, pembayaran_id INT NOT NULL, file VARCHAR(100) NOT NULL,
  catatan VARCHAR(255) NULL, status ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pembayaran_id) REFERENCES pembayaran(id) ON DELETE CASCADE)");
$db->query("CREATE TABLE IF NOT EXISTS keluhan (
  id INT AUTO_INCREMENT PRIMARY KEY, penyewa_id INT NOT NULL, judul VARCHAR(120) NOT NULL,
  deskripsi TEXT NOT NULL, status ENUM('baru','diproses','selesai') NOT NULL DEFAULT 'baru',
  tanggapan TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (penyewa_id) REFERENCES penyewa(id) ON DELETE CASCADE)");
$db->query("CREATE TABLE IF NOT EXISTS pengajuan_keluar (
  id INT AUTO_INCREMENT PRIMARY KEY, penyewa_id INT NOT NULL, tgl_keluar DATE NOT NULL,
  alasan VARCHAR(255) NOT NULL, status ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (penyewa_id) REFERENCES penyewa(id) ON DELETE CASCADE)");
$db->query("CREATE TABLE IF NOT EXISTS booking (
  id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL, no_hp VARCHAR(20) NOT NULL,
  kamar_id INT NULL, tgl_masuk DATE NOT NULL, status ENUM('baru','disetujui','ditolak') NOT NULL DEFAULT 'baru',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kamar_id) REFERENCES kamar(id) ON DELETE SET NULL)");
$db->query("CREATE TABLE IF NOT EXISTS pengumuman (
  id INT AUTO_INCREMENT PRIMARY KEY, judul VARCHAR(120) NOT NULL, isi TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

/* ================= FUNGSI BANTU ================= */
function e($s) { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function rp($n) { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }
function go($p) { header("Location: ?page=$p"); exit; }
function gotab($t) { header("Location: ?page=permintaan&tab=$t"); exit; }
function flash($m) { $_SESSION['flash'] = $m; }
function q($sql, $t = '', ...$a) {
  global $db; $s = $db->prepare($sql);
  if ($t) $s->bind_param($t, ...$a);
  $s->execute(); return $s;
}
function bulanOk($b) { return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string)$b); }
function periode($b) {
  $bl = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  return $bl[(int)substr($b, 5, 2)] . ' ' . substr($b, 0, 4);
}
function badge($s) { return '<span class="badge ' . e($s) . '">' . e($s) . '</span>'; }
function aksi($a, $rid, $label, $cls = '') {
  return '<form method="post" class="inline"><input type="hidden" name="aksi" value="' . $a . '"><input type="hidden" name="rid" value="' . (int)$rid . '">'
       . '<button class="' . $cls . '">' . $label . '</button></form> ';
}

/* ================= CSS ================= */
function css() { echo <<<'CSS'
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0d16;--panel:rgba(255,255,255,.045);--line:rgba(212,175,55,.22);--gold:#d4af37;--gold2:#f5d77a;--text:#ece8dc;--muted:#9a9686;--ok:#4ade80;--bad:#f87171;--warn:#fbbf24}
*{box-sizing:border-box}
body{margin:0;font-family:'Inter',system-ui,sans-serif;color:var(--text);min-height:100vh;
  background:radial-gradient(1200px 600px at 85% -10%,#1b2340 0%,transparent 60%),radial-gradient(900px 500px at -10% 110%,#2a2110 0%,transparent 55%),var(--bg);background-attachment:fixed}
h1,h2,.brand{font-family:'Playfair Display',Georgia,serif;font-weight:600}
h1{font-size:2rem;margin:0 0 6px;letter-spacing:.3px}
h1 small{display:block;font-family:'Inter',sans-serif;font-size:.85rem;font-weight:400;color:var(--muted);margin-top:4px;letter-spacing:.5px}
.gline{height:1px;background:linear-gradient(90deg,var(--gold),transparent);margin:14px 0 26px;border:0}
a{color:var(--gold2)}
.side{position:fixed;inset:0 auto 0 0;width:250px;padding:28px 18px;display:flex;flex-direction:column;gap:6px;background:linear-gradient(180deg,rgba(10,13,22,.96),rgba(14,18,30,.96));border-right:1px solid var(--line)}
.brand{font-size:1.5rem;color:var(--gold2);padding:0 10px 4px;letter-spacing:1px}
.brand span{display:block;font-family:'Inter',sans-serif;font-size:.65rem;letter-spacing:4px;color:var(--muted);margin-top:2px;font-weight:400}
.side hr{border:0;height:1px;background:var(--line);margin:18px 0 12px;width:100%}
.side a.nl{color:var(--muted);text-decoration:none;padding:11px 14px;border-radius:10px;display:flex;gap:12px;align-items:center;font-size:.95rem;transition:.2s;border:1px solid transparent}
.side a.nl:hover{color:var(--text);background:var(--panel)}
.side a.nl.on{color:#1a1405;background:linear-gradient(135deg,var(--gold2),var(--gold));font-weight:600;box-shadow:0 6px 20px rgba(212,175,55,.25)}
.side i.cnt{font-style:normal;background:var(--bad);color:#fff;border-radius:99px;padding:1px 8px;font-size:.7rem;margin-left:auto}
.side .me{margin-top:auto;padding:14px;border:1px solid var(--line);border-radius:12px;background:var(--panel);font-size:.85rem;color:var(--muted)}
.side .me b{display:block;color:var(--text);font-size:.95rem;margin-bottom:6px}
.content{margin-left:250px;padding:38px 42px;max-width:1250px}
.card,.box{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:22px;backdrop-filter:blur(10px);box-shadow:0 10px 40px rgba(0,0,0,.35)}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:18px;margin-bottom:28px}
.card{position:relative;overflow:hidden;transition:.25s}
.card:hover{transform:translateY(-4px);border-color:var(--gold)}
.card::after{content:"";position:absolute;right:-30px;top:-30px;width:110px;height:110px;border-radius:50%;background:radial-gradient(circle,rgba(212,175,55,.18),transparent 70%)}
.card .lbl{color:var(--muted);font-size:.8rem;letter-spacing:1.5px;text-transform:uppercase}
.card b{display:block;font-family:'Playfair Display',serif;font-size:2rem;margin-top:10px;color:var(--gold2);font-weight:600}
.card .ic{position:absolute;right:18px;top:16px;font-size:1.3rem;opacity:.9}
h2{font-size:1.25rem;margin:8px 0 14px;color:var(--gold2)}
.two{display:grid;grid-template-columns:1.4fr 1fr;gap:20px;margin-bottom:28px}
.wrap{overflow-x:auto;border:1px solid var(--line);border-radius:16px;background:var(--panel);backdrop-filter:blur(10px)}
table{width:100%;border-collapse:collapse}
th{padding:15px 16px;text-align:left;font-size:.72rem;letter-spacing:2px;text-transform:uppercase;color:var(--gold);background:rgba(212,175,55,.07);border-bottom:1px solid var(--line);font-weight:600}
td{padding:15px 16px;border-bottom:1px solid rgba(255,255,255,.05);font-size:.93rem;vertical-align:top}
tr:last-child td{border-bottom:0}
tbody tr:hover td{background:rgba(212,175,55,.045)}
.empty{text-align:center;color:var(--muted);padding:30px}
label{display:block;font-size:.75rem;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
input,select,textarea{width:100%;padding:12px 14px;margin-bottom:18px;color:var(--text);font:inherit;background:rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.12);border-radius:10px;outline:0;transition:.2s}
input:focus,select:focus,textarea:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(212,175,55,.15)}
textarea{min-height:90px;resize:vertical}
select option{background:#12172a;color:var(--text)}
input[type=date],input[type=month]{color-scheme:dark}
td input,td select,td textarea{margin-bottom:8px;padding:8px 10px;font-size:.85rem}
.bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.bar input,.bar select{width:auto;min-width:200px;margin:0}
.formbox{max-width:560px}
button,.btn{font:inherit;font-weight:600;font-size:.88rem;cursor:pointer;text-decoration:none;display:inline-block;padding:11px 20px;border-radius:10px;border:0;color:#1a1405;transition:.2s;background:linear-gradient(135deg,var(--gold2),var(--gold));box-shadow:0 6px 18px rgba(212,175,55,.22)}
button:hover,.btn:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(212,175,55,.38)}
td .btn,td button{padding:7px 14px;font-size:.8rem;box-shadow:none}
.red{background:transparent!important;color:var(--bad)!important;border:1px solid rgba(248,113,113,.45)!important;box-shadow:none!important}
.red:hover{background:rgba(248,113,113,.12)!important}
.green{background:transparent!important;color:var(--ok)!important;border:1px solid rgba(74,222,128,.45)!important;box-shadow:none!important}
.green:hover{background:rgba(74,222,128,.12)!important}
.ghost{background:transparent;color:var(--muted);box-shadow:none;border:1px solid rgba(255,255,255,.15)}
.ghost:hover{color:var(--text)}
form.inline{display:inline}
td .act{display:flex;gap:6px;flex-wrap:wrap}
.badge{padding:4px 12px;border-radius:999px;font-size:.72rem;letter-spacing:1px;text-transform:uppercase;font-weight:600;border:1px solid;white-space:nowrap}
.lunas,.kosong,.disetujui,.selesai{color:var(--ok);background:rgba(74,222,128,.1);border-color:rgba(74,222,128,.35)}
.belum,.terisi,.ditolak{color:var(--bad);background:rgba(248,113,113,.1);border-color:rgba(248,113,113,.35)}
.menunggu,.baru,.diproses{color:var(--warn);background:rgba(251,191,36,.1);border-color:rgba(251,191,36,.35)}
.err{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.4);color:#fecaca;padding:13px 16px;border-radius:12px;margin-bottom:20px}
.okmsg{background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.35);color:#bbf7d0;padding:13px 16px;border-radius:12px;margin-bottom:20px}
.info{background:rgba(212,175,55,.08);border:1px solid var(--line);padding:14px 18px;border-radius:12px;margin-bottom:14px}
.info b{color:var(--gold2)}
.chart{display:flex;gap:12px;align-items:flex-end;height:210px;padding-top:10px}
.col{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:6px;height:100%}
.col em{font-style:normal;font-size:.7rem;color:var(--muted)}
.col .fill{width:100%;max-width:56px;border-radius:8px 8px 0 0;background:linear-gradient(180deg,var(--gold2),var(--gold));transition:.3s}
.col:hover .fill{filter:brightness(1.2)}
.col span{font-size:.72rem;letter-spacing:1px;color:var(--text)}
.prog{height:12px;border-radius:99px;background:rgba(255,255,255,.1);overflow:hidden;margin:12px 0 8px}
.prog i{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,var(--gold),var(--gold2))}
.big{font-family:'Playfair Display',serif;font-size:2.6rem;color:var(--gold2)}
.authpage{min-height:100vh;display:grid;place-items:center;padding:24px}
.auth{width:100%;max-width:410px;padding:38px 34px}
.auth .brand{text-align:center;font-size:2rem;padding:0;margin-bottom:4px}
.auth .brand span{margin-bottom:6px}
.auth p.sub{text-align:center;color:var(--muted);font-size:.9rem;margin:0 0 26px}
.auth button{width:100%;padding:13px;margin-top:4px}
.auth .alt{text-align:center;margin-top:18px;font-size:.88rem;color:var(--muted)}
.tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:22px}
.tabs a{padding:9px 16px;border-radius:10px;text-decoration:none;color:var(--muted);border:1px solid rgba(255,255,255,.12);font-size:.88rem}
.tabs a.on{background:linear-gradient(135deg,var(--gold2),var(--gold));color:#1a1405;font-weight:600;border-color:transparent}
.tabs i{font-style:normal;background:var(--bad);color:#fff;border-radius:99px;padding:1px 7px;font-size:.7rem;margin-left:6px}
#toasts{position:fixed;right:20px;top:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;max-width:340px}
.toast{background:#12172a;border:1px solid var(--gold);border-radius:12px;padding:14px 16px;box-shadow:0 12px 40px rgba(0,0,0,.5);color:var(--text);font-size:.9rem;animation:tin .35s ease}
.toast b{display:block;color:var(--gold2);margin-bottom:4px}
.toast a{display:inline-block;margin-top:8px;font-weight:600}
@keyframes tin{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:none}}
@media(max-width:860px){
  .side{position:static;width:auto;flex-direction:row;flex-wrap:wrap;align-items:center;padding:14px}
  .side hr,.side .me{display:none}
  .brand{width:100%}
  .content{margin-left:0;padding:24px 16px}
  .two{grid-template-columns:1fr}
}
@media print{
  .side,.bar,.noprint,.btn,button,.gline,#toasts{display:none!important}
  body{background:#fff!important;color:#000}
  .content{margin:0;padding:0}
  .card,.wrap,.box{background:#fff;border:1px solid #999;box-shadow:none;color:#000}
  th{color:#000;background:#eee}h1,h2,.card b,.big{color:#000}h1 small,.card .lbl{color:#444}
  .badge{border-color:#555;color:#000;background:#fff}
}
</style>
CSS;
}

$page  = $_GET['page'] ?? 'dashboard';
$id    = (int)($_GET['id'] ?? 0);
$err   = '';
$post  = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($page === 'logout') { session_destroy(); go('login'); }
if (!isset($_SESSION['user']) && !in_array($page, ['login', 'register'])) go('login');

/* ---------- NOTIFIKASI (polling JSON) ---------- */
if ($page === 'notif') {
  session_write_close();
  header('Content-Type: application/json; charset=utf-8');
  $tbl = ['bukti' => 'bukti_bayar', 'keluhan' => 'keluhan', 'keluar' => 'pengajuan_keluar', 'booking' => 'booking'];
  $pend = [
    'bukti'   => "SELECT COUNT(*) FROM bukti_bayar WHERE status='menunggu'",
    'keluhan' => "SELECT COUNT(*) FROM keluhan WHERE status<>'selesai'",
    'keluar'  => "SELECT COUNT(*) FROM pengajuan_keluar WHERE status='menunggu'",
    'booking' => "SELECT COUNT(*) FROM booking WHERE status='baru'",
  ];
  $out = ['max' => [], 'fresh' => [], 'pending' => []];
  foreach ($tbl as $k => $t) {
    $out['max'][$k]     = (int)$db->query("SELECT COALESCE(MAX(id),0) FROM $t")->fetch_row()[0];
    $out['fresh'][$k]   = isset($_GET[$k]) ? (int)q("SELECT COUNT(*) FROM $t WHERE id>?", 'i', (int)$_GET[$k])->get_result()->fetch_row()[0] : 0;
    $out['pending'][$k] = (int)$db->query($pend[$k])->fetch_row()[0];
  }
  echo json_encode($out); exit;
}

/* ---------- EXPORT CSV ---------- */
if ($page === 'export') {
  $t = $_GET['t'] ?? '';
  $map = [
    'kamar'      => "SELECT no_kamar,tipe,harga,fasilitas,status FROM kamar ORDER BY no_kamar",
    'penyewa'    => "SELECT p.nama,p.no_hp,k.no_kamar,p.tgl_masuk FROM penyewa p LEFT JOIN kamar k ON k.id=p.kamar_id ORDER BY p.nama",
    'pembayaran' => "SELECT p.nama,b.bulan,b.jumlah,b.tgl_bayar,b.status FROM pembayaran b JOIN penyewa p ON p.id=b.penyewa_id ORDER BY b.bulan DESC,p.nama",
  ];
  if (!isset($map[$t])) go('dashboard');
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $t . '-' . date('Ymd') . '.csv"');
  echo "\xEF\xBB\xBF";
  $out = fopen('php://output', 'w'); $first = true;
  $r = $db->query($map[$t]);
  while ($row = $r->fetch_assoc()) {
    if ($first) { fputcsv($out, array_keys($row), ';', '"', '\\'); $first = false; }
    fputcsv($out, $row, ';', '"', '\\');
  }
  fclose($out); exit;
}

/* ---------- CETAK KUITANSI ---------- */
if ($page === 'kuitansi') {
  function terbilang($n) {
    $s = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
    if ($n < 12) return $s[$n];
    if ($n < 20) return terbilang($n - 10) . ' belas';
    if ($n < 100) return terbilang(intdiv($n, 10)) . ' puluh' . ($n % 10 ? ' ' . terbilang($n % 10) : '');
    if ($n < 200) return 'seratus' . ($n - 100 ? ' ' . terbilang($n - 100) : '');
    if ($n < 1000) return terbilang(intdiv($n, 100)) . ' ratus' . ($n % 100 ? ' ' . terbilang($n % 100) : '');
    if ($n < 2000) return 'seribu' . ($n - 1000 ? ' ' . terbilang($n - 1000) : '');
    if ($n < 1000000) return terbilang(intdiv($n, 1000)) . ' ribu' . ($n % 1000 ? ' ' . terbilang($n % 1000) : '');
    if ($n < 1000000000) return terbilang(intdiv($n, 1000000)) . ' juta' . ($n % 1000000 ? ' ' . terbilang($n % 1000000) : '');
    return (string)$n;
  }
  $bl = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  $tglId = fn($d) => $d ? date('j', strtotime($d)) . ' ' . $bl[(int)date('n', strtotime($d))] . ' ' . date('Y', strtotime($d)) : '-';
  $b = q("SELECT b.*, p.nama, p.no_hp, k.no_kamar, k.tipe FROM pembayaran b
          JOIN penyewa p ON p.id=b.penyewa_id LEFT JOIN kamar k ON k.id=p.kamar_id WHERE b.id=?", 'i', $id)->get_result()->fetch_assoc();
  if (!$b) go('pembayaran');
  $lunas = $b['status'] === 'lunas';
  $periode = $bl[(int)substr($b['bulan'], 5, 2)] . ' ' . substr($b['bulan'], 0, 4); ?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
<title>Kuitansi KW-<?= str_pad($b['id'], 5, '0', STR_PAD_LEFT) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<style>
@page{size:A5 landscape;margin:8mm}
*{box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact}
body{margin:0;padding:28px 16px;background:#e9e6df;font-family:'Inter',system-ui,sans-serif;color:#1d1b16}
.btn{display:block;margin:0 auto 20px;padding:11px 30px;border:0;border-radius:8px;cursor:pointer;font:600 .95rem 'Inter',sans-serif;color:#1a1405;background:linear-gradient(135deg,#f5d77a,#d4af37);box-shadow:0 6px 18px rgba(0,0,0,.2)}
.k{max-width:820px;margin:auto;background:#fffdf8;position:relative;overflow:hidden;border:1px solid #cdb25a;box-shadow:0 14px 50px rgba(0,0,0,.25)}
.k::before{content:"";position:absolute;inset:8px;border:1px solid #d9c37a;pointer-events:none;z-index:3}
.wm{position:absolute;left:50%;top:52%;transform:translate(-50%,-50%) rotate(-18deg);font:700 7rem 'Playfair Display',serif;letter-spacing:12px;color:rgba(212,175,55,.07);white-space:nowrap;z-index:0}
.head{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;padding:30px 44px;background:linear-gradient(135deg,#0d1220,#1b2340);color:#f5d77a}
.head .brand{font:700 1.9rem 'Playfair Display',serif;letter-spacing:4px}
.head .brand small{display:block;font:400 .62rem 'Inter',sans-serif;letter-spacing:5px;color:#b9b39b;margin-top:3px}
.head .ttl{text-align:right}
.head .ttl b{display:block;font:600 1.5rem 'Playfair Display',serif;letter-spacing:3px}
.head .ttl span{font-size:.85rem;color:#d8d2bd;letter-spacing:1px}
.goldbar{height:5px;background:linear-gradient(90deg,#8a6d1c,#f5d77a,#8a6d1c);position:relative;z-index:1}
.body{position:relative;z-index:1;padding:30px 44px 10px}
.row{display:flex;gap:16px;padding:11px 0;border-bottom:1px dotted #c9c2ad;align-items:baseline}
.row .l{width:180px;flex:none;font-size:.75rem;letter-spacing:1.5px;text-transform:uppercase;color:#7a7461}
.row .v{flex:1;font-weight:500;font-size:1rem}
.row .v small{color:#7a7461;font-weight:400}
.sum{position:relative;z-index:1;margin:18px 44px 0;display:flex;gap:20px;align-items:stretch}
.amt{flex:none;padding:14px 26px;border:2px solid #b8962e;background:linear-gradient(135deg,#fff7da,#fffdf8);border-radius:6px}
.amt small{display:block;font-size:.65rem;letter-spacing:3px;color:#7a7461;text-transform:uppercase}
.amt b{font:700 1.9rem 'Playfair Display',serif;color:#7d6216;white-space:nowrap}
.words{flex:1;padding:14px 18px;border-left:3px solid #d4af37;background:rgba(212,175,55,.08);font-size:.95rem;font-style:italic;display:flex;flex-direction:column;justify-content:center}
.words small{font-style:normal;font-size:.65rem;letter-spacing:3px;color:#7a7461;text-transform:uppercase;margin-bottom:3px}
.foot{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:flex-end;padding:24px 44px 34px;gap:20px}
.stamp{display:inline-block;padding:8px 26px;border:3px solid;border-radius:8px;transform:rotate(-6deg);font:700 1.4rem 'Playfair Display',serif;letter-spacing:4px}
.stamp.ok{color:#1c8a3f;background:rgba(28,138,63,.06)} .stamp.no{color:#c0392b;background:rgba(192,57,43,.06)}
.note{font-size:.72rem;color:#7a7461;margin-top:12px;max-width:330px;line-height:1.5}
.ttd{text-align:center;min-width:210px;font-size:.9rem}
.ttd .sp{height:64px}
.ttd .nm{border-top:1px solid #1d1b16;padding-top:6px;font-weight:600}
.ttd .rl{font-size:.75rem;color:#7a7461}
.bottom{position:relative;z-index:1;text-align:center;padding:12px;font-size:.68rem;letter-spacing:3px;color:#b9b39b;background:#14192b}
@media print{body{background:#fff;padding:0}.btn{display:none}.k{box-shadow:none;max-width:none}}
</style></head><body>
<button class="btn" onclick="window.print()">&#128424; Cetak Kuitansi</button>
<div class="k">
  <div class="wm">ROYAL KOS</div>
  <div class="head">
    <div class="brand">ROYAL KOS<small>RESIDENCE MANAGER</small></div>
    <div class="ttl"><b>KUITANSI</b><span>No. KW-<?= str_pad($b['id'], 5, '0', STR_PAD_LEFT) ?></span></div>
  </div>
  <div class="goldbar"></div>
  <div class="body">
    <div class="row"><div class="l">Diterima dari</div><div class="v"><?= e($b['nama']) ?> <small>&middot; <?= e($b['no_hp']) ?></small></div></div>
    <div class="row"><div class="l">Unit kamar</div><div class="v"><?= e($b['no_kamar'] ?? '-') ?> <small><?= e($b['tipe'] ?? '') ?></small></div></div>
    <div class="row"><div class="l">Untuk pembayaran</div><div class="v">Sewa kamar periode <?= e($periode) ?></div></div>
    <div class="row"><div class="l">Tanggal bayar</div><div class="v"><?= e($tglId($b['tgl_bayar'])) ?></div></div>
  </div>
  <div class="sum">
    <div class="amt"><small>Jumlah</small><b><?= rp($b['jumlah']) ?></b></div>
    <div class="words"><small>Terbilang</small><?= e(ucfirst(terbilang((int)$b['jumlah']))) ?> rupiah</div>
  </div>
  <div class="foot">
    <div>
      <span class="stamp <?= $lunas ? 'ok' : 'no' ?>"><?= $lunas ? 'LUNAS' : 'BELUM LUNAS' ?></span>
      <div class="note">Kuitansi ini sah sebagai bukti pembayaran sewa kamar apabila berstatus LUNAS. Harap disimpan dengan baik.</div>
    </div>
    <div class="ttd">Dikeluarkan pada <?= e($tglId(date('Y-m-d'))) ?><div class="sp"></div>
      <div class="nm"><?= e($_SESSION['user']['nama']) ?></div><div class="rl">Pengelola Kos</div></div>
  </div>
  <div class="bottom">TERIMA KASIH &middot; ROYAL KOS</div>
</div></body></html>
<?php exit; }

/* ================= PROSES FORM ADMIN ================= */
if ($post) {
  switch ($page) {

    case 'register':
      $nama = trim($_POST['nama']); $u = trim($_POST['username']); $p = $_POST['password'];
      if ($nama === '' || $u === '' || strlen($p) < 6) { $err = 'Semua field wajib diisi, password minimal 6 karakter.'; break; }
      if (q("SELECT id FROM users WHERE username=?", 's', $u)->get_result()->num_rows) { $err = 'Username sudah dipakai.'; break; }
      q("INSERT INTO users (nama,username,password) VALUES (?,?,?)", 'sss', $nama, $u, password_hash($p, PASSWORD_DEFAULT));
      header('Location: ?page=login&ok=1'); exit;

    case 'login':
      $u = q("SELECT * FROM users WHERE username=?", 's', $_POST['username'])->get_result()->fetch_assoc();
      if ($u && password_verify($_POST['password'], $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id'], 'nama' => $u['nama']];
        go('dashboard');
      }
      $err = 'Username atau password salah.'; break;

    case 'profil':
      $u = q("SELECT password FROM users WHERE id=?", 'i', $_SESSION['user']['id'])->get_result()->fetch_assoc();
      if (!password_verify($_POST['lama'], $u['password'])) { $err = 'Password lama salah.'; break; }
      if (strlen($_POST['baru']) < 6) { $err = 'Password baru minimal 6 karakter.'; break; }
      if ($_POST['baru'] !== $_POST['ulang']) { $err = 'Konfirmasi password tidak sama.'; break; }
      q("UPDATE users SET password=? WHERE id=?", 'si', password_hash($_POST['baru'], PASSWORD_DEFAULT), $_SESSION['user']['id']);
      flash('Password berhasil diganti.'); go('profil');

    case 'kamar':
      if (isset($_POST['hapus'])) { q("DELETE FROM kamar WHERE id=?", 'i', (int)$_POST['hapus']); flash('Kamar dihapus.'); }
      go('kamar');

    case 'kamar_form':
      $no = trim($_POST['no_kamar']); $tipe = trim($_POST['tipe']);
      $harga = (int)$_POST['harga']; $fas = trim($_POST['fasilitas']);
      if ($no === '' || $harga <= 0) { $err = 'No kamar dan harga wajib diisi dengan benar.'; break; }
      try {
        if ($id) q("UPDATE kamar SET no_kamar=?,tipe=?,harga=?,fasilitas=? WHERE id=?", 'ssisi', $no, $tipe, $harga, $fas, $id);
        else     q("INSERT INTO kamar (no_kamar,tipe,harga,fasilitas) VALUES (?,?,?,?)", 'ssis', $no, $tipe, $harga, $fas);
        flash('Data kamar disimpan.'); go('kamar');
      } catch (mysqli_sql_exception $x) { $err = 'Nomor kamar sudah ada.'; }
      break;

    case 'penyewa':
      if (isset($_POST['reset'])) {
        q("UPDATE penyewa SET password=NULL WHERE id=?", 'i', (int)$_POST['reset']);
        flash('Password portal direset. Penyewa login memakai No HP-nya.');
      }
      if (isset($_POST['hapus'])) {
        $h = (int)$_POST['hapus'];
        q("UPDATE kamar SET status='kosong' WHERE id=(SELECT kamar_id FROM penyewa WHERE id=?)", 'i', $h);
        q("DELETE FROM penyewa WHERE id=?", 'i', $h);
        flash('Penyewa dihapus dan kamarnya dikosongkan.');
      }
      go('penyewa');

    case 'penyewa_form':
      $nama = trim($_POST['nama']); $hp = trim($_POST['no_hp']);
      $kid = (int)$_POST['kamar_id'] ?: null; $tgl = $_POST['tgl_masuk'];
      if ($nama === '' || !preg_match('/^[0-9+]{8,15}$/', $hp)) { $err = 'Nama wajib diisi dan No HP harus 8-15 digit angka.'; break; }
      if ($id) {
        $old = q("SELECT kamar_id FROM penyewa WHERE id=?", 'i', $id)->get_result()->fetch_row()[0] ?? null;
        q("UPDATE penyewa SET nama=?,no_hp=?,kamar_id=?,tgl_masuk=? WHERE id=?", 'ssisi', $nama, $hp, $kid, $tgl, $id);
        if ($old) q("UPDATE kamar SET status='kosong' WHERE id=?", 'i', $old);
      } else {
        q("INSERT INTO penyewa (nama,no_hp,kamar_id,tgl_masuk) VALUES (?,?,?,?)", 'ssis', $nama, $hp, $kid, $tgl);
      }
      if ($kid) q("UPDATE kamar SET status='terisi' WHERE id=?", 'i', $kid);
      flash('Data penyewa disimpan.'); go('penyewa');

    case 'pembayaran':
      if (isset($_POST['hapus'])) {
        q("DELETE FROM pembayaran WHERE id=?", 'i', (int)$_POST['hapus']); flash('Data pembayaran dihapus.');
      } elseif (isset($_POST['lunas'])) {
        q("UPDATE pembayaran SET status='lunas', tgl_bayar=CURDATE() WHERE id=?", 'i', (int)$_POST['lunas']);
        flash('Tagihan ditandai lunas.');
      } elseif (isset($_POST['generate'])) {
        $bln = $_POST['bulan'] ?? '';
        if (!bulanOk($bln)) { flash('Format bulan tidak valid.'); go('pembayaran'); }
        $s = q("INSERT INTO pembayaran (penyewa_id,bulan,jumlah,status)
                SELECT p.id, ?, k.harga, 'belum' FROM penyewa p JOIN kamar k ON k.id=p.kamar_id
                WHERE NOT EXISTS (SELECT 1 FROM pembayaran b WHERE b.penyewa_id=p.id AND b.bulan=?)", 'ss', $bln, $bln);
        flash($s->affected_rows . " tagihan baru dibuat untuk bulan $bln.");
      }
      go('pembayaran');

    case 'pembayaran_form':
      $pid = (int)$_POST['penyewa_id']; $bulan = $_POST['bulan']; $jml = (int)$_POST['jumlah'];
      $tgl = $_POST['tgl_bayar'] ?: null; $st = $_POST['status'] === 'lunas' ? 'lunas' : 'belum';
      if (!$pid || $jml <= 0 || !bulanOk($bulan)) { $err = 'Penyewa, bulan, dan jumlah wajib diisi dengan benar.'; break; }
      if ($st === 'lunas' && !$tgl) { $err = 'Tanggal bayar wajib diisi jika status lunas.'; break; }
      if ($id) q("UPDATE pembayaran SET penyewa_id=?,bulan=?,jumlah=?,tgl_bayar=?,status=? WHERE id=?", 'isissi', $pid, $bulan, $jml, $tgl, $st, $id);
      else     q("INSERT INTO pembayaran (penyewa_id,bulan,jumlah,tgl_bayar,status) VALUES (?,?,?,?,?)", 'isiss', $pid, $bulan, $jml, $tgl, $st);
      flash('Data pembayaran disimpan.'); go('pembayaran');

    case 'permintaan':
      $aksi = $_POST['aksi'] ?? ''; $rid = (int)($_POST['rid'] ?? 0);

      if ($aksi === 'bukti_setuju') {
        $b = q("SELECT * FROM bukti_bayar WHERE id=? AND status='menunggu'", 'i', $rid)->get_result()->fetch_assoc();
        if ($b) {
          q("UPDATE bukti_bayar SET status='disetujui' WHERE id=?", 'i', $rid);
          q("UPDATE pembayaran SET status='lunas', tgl_bayar=CURDATE() WHERE id=?", 'i', $b['pembayaran_id']);
          flash('Bukti disetujui, tagihan ditandai lunas.');
        }
        gotab('bukti');
      }
      if ($aksi === 'bukti_tolak') {
        q("UPDATE bukti_bayar SET status='ditolak' WHERE id=? AND status='menunggu'", 'i', $rid);
        flash('Bukti ditolak. Penyewa dapat mengunggah ulang.'); gotab('bukti');
      }
      if ($aksi === 'keluhan') {
        $st = in_array($_POST['status'], ['baru', 'diproses', 'selesai']) ? $_POST['status'] : 'baru';
        q("UPDATE keluhan SET status=?, tanggapan=? WHERE id=?", 'ssi', $st, trim($_POST['tanggapan']), $rid);
        flash('Keluhan diperbarui.'); gotab('keluhan');
      }
      if ($aksi === 'keluar_setuju') {
        $k = q("SELECT * FROM pengajuan_keluar WHERE id=? AND status='menunggu'", 'i', $rid)->get_result()->fetch_assoc();
        if ($k) {
          q("UPDATE kamar SET status='kosong' WHERE id=(SELECT kamar_id FROM penyewa WHERE id=?)", 'i', $k['penyewa_id']);
          q("UPDATE penyewa SET kamar_id=NULL WHERE id=?", 'i', $k['penyewa_id']);
          q("UPDATE pengajuan_keluar SET status='disetujui' WHERE id=?", 'i', $rid);
          flash('Disetujui. Kamar dikosongkan dan penyewa dilepas dari kamar.');
        }
        gotab('keluar');
      }
      if ($aksi === 'keluar_tolak') {
        q("UPDATE pengajuan_keluar SET status='ditolak' WHERE id=? AND status='menunggu'", 'i', $rid);
        flash('Pengajuan ditolak.'); gotab('keluar');
      }
      if ($aksi === 'booking_setuju') {
        $b = q("SELECT * FROM booking WHERE id=? AND status='baru'", 'i', $rid)->get_result()->fetch_assoc();
        if ($b && $b['kamar_id'] && q("SELECT id FROM kamar WHERE id=? AND status='kosong'", 'i', $b['kamar_id'])->get_result()->num_rows) {
          q("INSERT INTO penyewa (nama,no_hp,kamar_id,tgl_masuk) VALUES (?,?,?,?)", 'ssis', $b['nama'], $b['no_hp'], $b['kamar_id'], $b['tgl_masuk']);
          q("UPDATE kamar SET status='terisi' WHERE id=?", 'i', $b['kamar_id']);
          q("UPDATE booking SET status='disetujui' WHERE id=?", 'i', $rid);
          flash('Booking disetujui. Penyewa baru dibuat (password awal = No HP).');
        } else flash('Gagal: kamar sudah terisi atau booking tidak valid.');
        gotab('booking');
      }
      if ($aksi === 'booking_tolak') {
        q("UPDATE booking SET status='ditolak' WHERE id=? AND status='baru'", 'i', $rid);
        flash('Booking ditolak.'); gotab('booking');
      }
      if ($aksi === 'info_tambah') {
        $j = trim($_POST['judul']); $i = trim($_POST['isi']);
        if ($j === '' || $i === '') { $err = 'Judul dan isi wajib diisi.'; break; }
        q("INSERT INTO pengumuman (judul,isi) VALUES (?,?)", 'ss', $j, $i);
        flash('Pengumuman diterbitkan.'); gotab('info');
      }
      if ($aksi === 'info_hapus') { q("DELETE FROM pengumuman WHERE id=?", 'i', $rid); flash('Pengumuman dihapus.'); gotab('info'); }
      break;
  }
}

$logged = isset($_SESSION['user']);
$nav = str_replace('_form', '', $page);
$cn = [];
if ($logged) {
  $cn['bukti']   = $db->query("SELECT COUNT(*) FROM bukti_bayar WHERE status='menunggu'")->fetch_row()[0];
  $cn['keluhan'] = $db->query("SELECT COUNT(*) FROM keluhan WHERE status<>'selesai'")->fetch_row()[0];
  $cn['keluar']  = $db->query("SELECT COUNT(*) FROM pengajuan_keluar WHERE status='menunggu'")->fetch_row()[0];
  $cn['booking'] = $db->query("SELECT COUNT(*) FROM booking WHERE status='baru'")->fetch_row()[0];
}
$totPend = array_sum($cn);
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Royal Kos &mdash; Admin</title>
<?php css(); ?>
</head><body>

<?php if ($logged): ?>
<aside class="side">
  <div class="brand">ROYAL KOS<span>RESIDENCE MANAGER</span></div>
  <hr>
  <a class="nl <?= $nav=='dashboard'?'on':'' ?>" href="?page=dashboard">&#9670; Dashboard</a>
  <a class="nl <?= $nav=='kamar'?'on':'' ?>" href="?page=kamar">&#9635; Kamar</a>
  <a class="nl <?= $nav=='penyewa'?'on':'' ?>" href="?page=penyewa">&#9786; Penyewa</a>
  <a class="nl <?= $nav=='pembayaran'?'on':'' ?>" href="?page=pembayaran">&#10070; Pembayaran</a>
  <a id="navReq" class="nl <?= $nav=='permintaan'?'on':'' ?>" href="?page=permintaan">&#9993; Permintaan<?= $totPend ? ' <i class="cnt">' . $totPend . '</i>' : '' ?></a>
  <a class="nl <?= $nav=='laporan'?'on':'' ?>" href="?page=laporan">&#9783; Laporan</a>
  <a class="nl <?= $nav=='profil'?'on':'' ?>" href="?page=profil">&#9881; Akun</a>
  <div class="me"><b><?= e($_SESSION['user']['nama']) ?></b>Administrator<br><a href="?page=logout">Keluar &rarr;</a>
    <button type="button" id="sndBtn" class="ghost" style="margin-top:10px;width:100%;padding:7px;font-size:.78rem"></button></div>
</aside>
<main class="content">
<?php if (!empty($_SESSION['flash'])): ?><div class="okmsg"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif; ?>
<?php else: ?>
<main class="authpage">
<?php endif; ?>

<?php if ($err && $logged): ?><div class="err"><?= e($err) ?></div><?php endif; ?>

<?php /* ---------- LOGIN ---------- */ if ($page === 'login'): ?>
<div class="box auth">
  <div class="brand">ROYAL KOS<span>RESIDENCE MANAGER</span></div>
  <p class="sub">Masuk untuk mengelola hunian Anda</p>
  <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
  <?php if (isset($_GET['ok'])): ?><div class="okmsg">Pendaftaran berhasil, silakan login.</div><?php endif; ?>
  <form method="post">
    <label>Username</label><input name="username" required autofocus>
    <label>Password</label><input type="password" name="password" required>
    <button>Masuk</button>
  </form>
  <div class="alt">Belum punya akun? <a href="?page=register">Daftar</a></div>
</div>

<?php /* ---------- REGISTER ---------- */ elseif ($page === 'register'): ?>
<div class="box auth">
  <div class="brand">ROYAL KOS<span>RESIDENCE MANAGER</span></div>
  <p class="sub">Buat akun administrator</p>
  <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <label>Nama Lengkap</label><input name="nama" required>
    <label>Username</label><input name="username" required>
    <label>Password</label><input type="password" name="password" minlength="6" required>
    <button>Daftar</button>
  </form>
  <div class="alt">Sudah punya akun? <a href="?page=login">Login</a></div>
</div>

<?php /* ---------- AKUN ---------- */ elseif ($page === 'profil'): ?>
<h1>Akun Saya<small>Ubah password untuk menjaga keamanan akun</small></h1><hr class="gline">
<form method="post" class="box formbox">
  <label>Password Lama</label><input type="password" name="lama" required>
  <label>Password Baru</label><input type="password" name="baru" minlength="6" required>
  <label>Ulangi Password Baru</label><input type="password" name="ulang" minlength="6" required>
  <button>Ganti Password</button>
</form>

<?php /* ---------- KAMAR (LIST) ---------- */ elseif ($page === 'kamar'):
  $f = in_array($_GET['status'] ?? '', ['kosong', 'terisi']) ? $_GET['status'] : '';
  $s = '%' . ($_GET['q'] ?? '') . '%';
  $base = "SELECT * FROM kamar WHERE (no_kamar LIKE ? OR tipe LIKE ?)";
  $r = ($f ? q("$base AND status=? ORDER BY no_kamar", 'sss', $s, $s, $f)
           : q("$base ORDER BY no_kamar", 'ss', $s, $s))->get_result(); ?>
<h1>Data Kamar<small>Kelola unit hunian, harga, dan ketersediaan</small></h1><hr class="gline">
<form class="bar" method="get"><input type="hidden" name="page" value="kamar">
  <input name="q" placeholder="Cari no / tipe kamar..." value="<?= e($_GET['q'] ?? '') ?>">
  <select name="status" style="min-width:150px">
    <option value="">Semua status</option>
    <option value="kosong" <?= $f == 'kosong' ? 'selected' : '' ?>>Kosong</option>
    <option value="terisi" <?= $f == 'terisi' ? 'selected' : '' ?>>Terisi</option>
  </select>
  <button>Filter</button>
  <a class="btn" href="?page=kamar_form">+ Tambah Kamar</a>
  <a class="btn ghost" href="?page=export&t=kamar">&#8681; Export CSV</a></form>
<div class="wrap"><table>
<tr><th>No</th><th>Tipe</th><th>Harga / bulan</th><th>Fasilitas</th><th>Status</th><th>Aksi</th></tr>
<?php $n = 0; while ($k = $r->fetch_assoc()): $n++; ?>
<tr><td><b><?= e($k['no_kamar']) ?></b></td><td><?= e($k['tipe']) ?></td><td><?= rp($k['harga']) ?></td><td><?= e($k['fasilitas']) ?></td>
<td><span class="badge <?= e($k['status']) ?>"><?= e($k['status']) ?></span></td>
<td><div class="act"><a class="btn" href="?page=kamar_form&id=<?= $k['id'] ?>">Ubah</a>
<form class="inline" method="post" onsubmit="return confirm('Hapus kamar ini?')"><button class="red" name="hapus" value="<?= $k['id'] ?>">Hapus</button></form></div></td></tr>
<?php endwhile; if (!$n): ?><tr><td colspan="6" class="empty">Belum ada data kamar.</td></tr><?php endif; ?></table></div>

<?php /* ---------- KAMAR (FORM) ---------- */ elseif ($page === 'kamar_form'):
  $row = $post ? $_POST : ($id ? (q("SELECT * FROM kamar WHERE id=?", 'i', $id)->get_result()->fetch_assoc() ?: []) : []);
  $v = fn($k, $d = '') => e($row[$k] ?? $d); ?>
<h1><?= $id ? 'Ubah' : 'Tambah' ?> Kamar<small>Lengkapi detail unit hunian</small></h1><hr class="gline">
<form method="post" class="box formbox">
  <label>No Kamar</label><input name="no_kamar" value="<?= $v('no_kamar') ?>" required>
  <label>Tipe</label><input name="tipe" value="<?= $v('tipe', 'Standar') ?>" required>
  <label>Harga per bulan (Rp)</label><input type="number" name="harga" min="1" value="<?= $v('harga') ?>" required>
  <label>Fasilitas</label><input name="fasilitas" value="<?= $v('fasilitas') ?>">
  <button>Simpan</button> <a class="btn ghost" href="?page=kamar">Batal</a>
</form>

<?php /* ---------- PENYEWA (LIST) ---------- */ elseif ($page === 'penyewa'):
  $s = '%' . ($_GET['q'] ?? '') . '%';
  $r = q("SELECT p.*, k.no_kamar,
            (SELECT COUNT(*) FROM pembayaran b WHERE b.penyewa_id=p.id AND b.status='belum') AS tung
          FROM penyewa p LEFT JOIN kamar k ON k.id=p.kamar_id
          WHERE p.nama LIKE ? OR p.no_hp LIKE ? ORDER BY p.nama", 'ss', $s, $s)->get_result(); ?>
<h1>Data Penyewa<small>Daftar penghuni, kamar, dan tunggakan</small></h1><hr class="gline">
<form class="bar" method="get"><input type="hidden" name="page" value="penyewa">
  <input name="q" placeholder="Cari nama / no HP..." value="<?= e($_GET['q'] ?? '') ?>"> <button>Cari</button>
  <a class="btn" href="?page=penyewa_form">+ Tambah Penyewa</a>
  <a class="btn ghost" href="?page=export&t=penyewa">&#8681; Export CSV</a></form>
<div class="wrap"><table>
<tr><th>Nama</th><th>No HP</th><th>Kamar</th><th>Tgl Masuk</th><th>Tunggakan</th><th>Aksi</th></tr>
<?php $n = 0; while ($p = $r->fetch_assoc()): $n++; ?>
<tr><td><b><?= e($p['nama']) ?></b></td><td><?= e($p['no_hp']) ?></td><td><?= e($p['no_kamar'] ?? '-') ?></td><td><?= e($p['tgl_masuk']) ?></td>
<td><?= $p['tung'] > 0 ? '<span class="badge belum">' . (int)$p['tung'] . ' bulan</span>' : '<span class="badge lunas">Lancar</span>' ?></td>
<td><div class="act"><a class="btn" href="?page=penyewa_form&id=<?= $p['id'] ?>">Ubah</a>
<form class="inline" method="post" onsubmit="return confirm('Reset password portal penyewa ini ke No HP?')"><button class="ghost" name="reset" value="<?= $p['id'] ?>">Reset PW</button></form>
<form class="inline" method="post" onsubmit="return confirm('Hapus penyewa ini?')"><button class="red" name="hapus" value="<?= $p['id'] ?>">Hapus</button></form></div></td></tr>
<?php endwhile; if (!$n): ?><tr><td colspan="6" class="empty">Belum ada data penyewa.</td></tr><?php endif; ?></table></div>

<?php /* ---------- PENYEWA (FORM) ---------- */ elseif ($page === 'penyewa_form'):
  $row = $post ? $_POST : ($id ? (q("SELECT * FROM penyewa WHERE id=?", 'i', $id)->get_result()->fetch_assoc() ?: []) : []);
  $v = fn($k, $d = '') => e($row[$k] ?? $d);
  $kamar = q("SELECT * FROM kamar WHERE status='kosong' OR id=? ORDER BY no_kamar", 'i', (int)($row['kamar_id'] ?? 0))->get_result(); ?>
<h1><?= $id ? 'Ubah' : 'Tambah' ?> Penyewa<small>Data penghuni dan penempatan kamar</small></h1><hr class="gline">
<form method="post" class="box formbox">
  <label>Nama</label><input name="nama" value="<?= $v('nama') ?>" required>
  <label>No HP</label><input name="no_hp" value="<?= $v('no_hp') ?>" required>
  <label>Kamar</label>
  <select name="kamar_id"><option value="0">- Belum ada kamar -</option>
  <?php while ($k = $kamar->fetch_assoc()): ?>
    <option value="<?= $k['id'] ?>" <?= $k['id'] == ($row['kamar_id'] ?? 0) ? 'selected' : '' ?>><?= e($k['no_kamar']) ?> (<?= e($k['tipe']) ?>, <?= rp($k['harga']) ?>)</option>
  <?php endwhile; ?></select>
  <label>Tanggal Masuk</label><input type="date" name="tgl_masuk" value="<?= $v('tgl_masuk', date('Y-m-d')) ?>" required>
  <button>Simpan</button> <a class="btn ghost" href="?page=penyewa">Batal</a>
</form>

<?php /* ---------- PEMBAYARAN (LIST) ---------- */ elseif ($page === 'pembayaran'):
  $f = in_array($_GET['status'] ?? '', ['lunas', 'belum']) ? $_GET['status'] : '';
  $s = '%' . ($_GET['q'] ?? '') . '%';
  $base = "SELECT b.*, p.nama FROM pembayaran b JOIN penyewa p ON p.id=b.penyewa_id WHERE p.nama LIKE ?";
  $r = ($f ? q("$base AND b.status=? ORDER BY b.bulan DESC, p.nama", 'ss', $s, $f)
           : q("$base ORDER BY b.bulan DESC, p.nama", 's', $s))->get_result(); ?>
<h1>Pembayaran<small>Catatan sewa bulanan dan status pelunasan</small></h1><hr class="gline">
<form class="bar" method="get"><input type="hidden" name="page" value="pembayaran">
  <input name="q" placeholder="Cari nama penyewa..." value="<?= e($_GET['q'] ?? '') ?>">
  <select name="status" style="min-width:150px">
    <option value="">Semua status</option>
    <option value="lunas" <?= $f == 'lunas' ? 'selected' : '' ?>>Lunas</option>
    <option value="belum" <?= $f == 'belum' ? 'selected' : '' ?>>Belum</option>
  </select>
  <button>Filter</button>
  <a class="btn" href="?page=pembayaran_form">+ Tambah Pembayaran</a>
  <a class="btn ghost" href="?page=export&t=pembayaran">&#8681; Export CSV</a></form>
<form class="bar box" method="post" style="padding:14px 18px" onsubmit="return confirm('Buat tagihan bulan ini untuk semua penyewa yang punya kamar?')">
  <span style="color:var(--muted);font-size:.85rem">Buat tagihan otomatis untuk semua penyewa:</span>
  <input type="month" name="bulan" value="<?= date('Y-m') ?>" style="min-width:160px" required>
  <button name="generate" value="1">&#10070; Buat Tagihan Bulanan</button>
</form>
<div class="wrap"><table>
<tr><th>Penyewa</th><th>Bulan</th><th>Jumlah</th><th>Tgl Bayar</th><th>Status</th><th>Aksi</th></tr>
<?php $n = 0; while ($b = $r->fetch_assoc()): $n++; ?>
<tr><td><b><?= e($b['nama']) ?></b></td><td><?= e($b['bulan']) ?></td><td><?= rp($b['jumlah']) ?></td><td><?= e($b['tgl_bayar'] ?? '-') ?></td>
<td><span class="badge <?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
<td><div class="act">
<?php if ($b['status'] === 'belum'): ?><form class="inline" method="post"><button class="green" name="lunas" value="<?= $b['id'] ?>">&#10003; Lunas</button></form><?php endif; ?>
<a class="btn ghost" target="_blank" href="?page=kuitansi&id=<?= $b['id'] ?>">Kuitansi</a>
<a class="btn" href="?page=pembayaran_form&id=<?= $b['id'] ?>">Ubah</a>
<form class="inline" method="post" onsubmit="return confirm('Hapus data ini?')"><button class="red" name="hapus" value="<?= $b['id'] ?>">Hapus</button></form></div></td></tr>
<?php endwhile; if (!$n): ?><tr><td colspan="6" class="empty">Belum ada data pembayaran.</td></tr><?php endif; ?></table></div>

<?php /* ---------- PEMBAYARAN (FORM) ---------- */ elseif ($page === 'pembayaran_form'):
  $row = $post ? $_POST : ($id ? (q("SELECT * FROM pembayaran WHERE id=?", 'i', $id)->get_result()->fetch_assoc() ?: []) : []);
  $v = fn($k, $d = '') => e($row[$k] ?? $d);
  $st = $row['status'] ?? 'belum';
  $pen = $db->query("SELECT p.id, p.nama, k.no_kamar, k.harga FROM penyewa p LEFT JOIN kamar k ON k.id=p.kamar_id ORDER BY p.nama"); ?>
<h1><?= $id ? 'Ubah' : 'Tambah' ?> Pembayaran<small>Catat tagihan atau pelunasan sewa</small></h1><hr class="gline">
<form method="post" class="box formbox">
  <label>Penyewa</label>
  <select name="penyewa_id" id="pen" required><option value="">- Pilih -</option>
  <?php while ($p = $pen->fetch_assoc()): ?>
    <option value="<?= $p['id'] ?>" data-harga="<?= (int)$p['harga'] ?>" <?= $p['id'] == ($row['penyewa_id'] ?? 0) ? 'selected' : '' ?>><?= e($p['nama']) ?> (<?= e($p['no_kamar'] ?? 'tanpa kamar') ?>)</option>
  <?php endwhile; ?></select>
  <label>Bulan</label><input type="month" name="bulan" value="<?= $v('bulan', date('Y-m')) ?>" required>
  <label>Jumlah (Rp)</label><input type="number" name="jumlah" id="jml" min="1" value="<?= $v('jumlah') ?>" required>
  <label>Tanggal Bayar</label><input type="date" name="tgl_bayar" value="<?= $v('tgl_bayar') ?>">
  <label>Status</label>
  <select name="status">
    <option value="belum" <?= $st == 'belum' ? 'selected' : '' ?>>Belum</option>
    <option value="lunas" <?= $st == 'lunas' ? 'selected' : '' ?>>Lunas</option>
  </select>
  <button>Simpan</button> <a class="btn ghost" href="?page=pembayaran">Batal</a>
</form>
<script>
document.getElementById('pen').addEventListener('change', function () {
  var h = this.selectedOptions[0].dataset.harga, j = document.getElementById('jml');
  if (h && !j.value) j.value = h;
});
</script>

<?php /* ---------- PERMINTAAN PELANGGAN ---------- */ elseif ($page === 'permintaan'):
  $tab = $_GET['tab'] ?? 'bukti';
  $tabs = ['bukti' => 'Bukti Transfer', 'keluhan' => 'Keluhan', 'keluar' => 'Pengajuan Keluar', 'booking' => 'Booking', 'info' => 'Pengumuman']; ?>
<h1>Permintaan Pelanggan<small>Proses bukti transfer, keluhan, pengajuan keluar, booking, dan pengumuman</small></h1><hr class="gline">
<div class="tabs">
<?php foreach ($tabs as $k => $l): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="?page=permintaan&tab=<?= $k ?>"><?= $l ?><?= !empty($cn[$k]) ? '<i>' . $cn[$k] . '</i>' : '' ?></a><?php endforeach; ?>
</div>

<?php if ($tab === 'bukti'):
  $r = $db->query("SELECT x.*, p.nama, b.bulan, b.jumlah FROM bukti_bayar x
     JOIN pembayaran b ON b.id=x.pembayaran_id JOIN penyewa p ON p.id=b.penyewa_id ORDER BY (x.status='menunggu') DESC, x.id DESC"); ?>
<div class="wrap"><table><tr><th>Tanggal</th><th>Penyewa</th><th>Tagihan</th><th>Bukti</th><th>Status</th><th>Aksi</th></tr>
<?php $c = 0; while ($x = $r->fetch_assoc()): $c++; ?>
<tr><td><?= e(substr($x['created_at'], 0, 16)) ?></td><td><b><?= e($x['nama']) ?></b></td>
<td><?= e(periode($x['bulan'])) ?><br><?= rp($x['jumlah']) ?></td>
<td><a target="_blank" href="uploads/bukti/<?= e($x['file']) ?>">Lihat file</a><br><small style="color:var(--muted)"><?= e($x['catatan']) ?></small></td>
<td><?= badge($x['status']) ?></td>
<td><?php if ($x['status'] === 'menunggu') echo aksi('bukti_setuju', $x['id'], '&#10003; Setujui', 'green') . aksi('bukti_tolak', $x['id'], 'Tolak', 'red'); ?></td></tr>
<?php endwhile; if (!$c): ?><tr><td colspan="6" class="empty">Belum ada bukti transfer.</td></tr><?php endif; ?></table></div>

<?php elseif ($tab === 'keluhan'):
  $r = $db->query("SELECT k.*, p.nama, km.no_kamar FROM keluhan k JOIN penyewa p ON p.id=k.penyewa_id
     LEFT JOIN kamar km ON km.id=p.kamar_id ORDER BY (k.status='selesai'), k.id DESC"); ?>
<div class="wrap"><table><tr><th>Tanggal</th><th>Penyewa</th><th>Laporan</th><th>Tanggapan &amp; Status</th></tr>
<?php $c = 0; while ($k = $r->fetch_assoc()): $c++; ?>
<tr><td><?= e(substr($k['created_at'], 0, 10)) ?></td><td><b><?= e($k['nama']) ?></b><br><small style="color:var(--muted)">Kamar <?= e($k['no_kamar'] ?? '-') ?></small></td>
<td><b><?= e($k['judul']) ?></b><br><span style="color:var(--muted)"><?= nl2br(e($k['deskripsi'])) ?></span></td>
<td style="min-width:260px"><form method="post"><input type="hidden" name="aksi" value="keluhan"><input type="hidden" name="rid" value="<?= $k['id'] ?>">
<select name="status"><?php foreach (['baru', 'diproses', 'selesai'] as $s): ?><option <?= $k['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
<textarea name="tanggapan" placeholder="Tanggapan untuk penyewa..."><?= e($k['tanggapan']) ?></textarea><button>Simpan</button></form></td></tr>
<?php endwhile; if (!$c): ?><tr><td colspan="4" class="empty">Belum ada keluhan.</td></tr><?php endif; ?></table></div>

<?php elseif ($tab === 'keluar'):
  $r = $db->query("SELECT g.*, p.nama, km.no_kamar,
     (SELECT COUNT(*) FROM pembayaran b WHERE b.penyewa_id=p.id AND b.status='belum') AS tung
     FROM pengajuan_keluar g JOIN penyewa p ON p.id=g.penyewa_id LEFT JOIN kamar km ON km.id=p.kamar_id
     ORDER BY (g.status='menunggu') DESC, g.id DESC"); ?>
<div class="wrap"><table><tr><th>Diajukan</th><th>Penyewa</th><th>Kamar</th><th>Tgl Keluar</th><th>Alasan</th><th>Tunggakan</th><th>Status</th><th>Aksi</th></tr>
<?php $c = 0; while ($g = $r->fetch_assoc()): $c++; ?>
<tr><td><?= e(substr($g['created_at'], 0, 10)) ?></td><td><b><?= e($g['nama']) ?></b></td><td><?= e($g['no_kamar'] ?? '-') ?></td>
<td><?= e($g['tgl_keluar']) ?></td><td><?= e($g['alasan']) ?></td>
<td><?= $g['tung'] > 0 ? '<span class="badge belum">' . (int)$g['tung'] . ' bulan</span>' : '<span class="badge lunas">Lancar</span>' ?></td>
<td><?= badge($g['status']) ?></td>
<td><?php if ($g['status'] === 'menunggu') echo aksi('keluar_setuju', $g['id'], '&#10003; Setujui', 'green') . aksi('keluar_tolak', $g['id'], 'Tolak', 'red'); ?></td></tr>
<?php endwhile; if (!$c): ?><tr><td colspan="8" class="empty">Belum ada pengajuan.</td></tr><?php endif; ?></table></div>
<p style="color:var(--muted);font-size:.85rem">Setuju = kamar langsung dikosongkan dan penyewa dilepas dari kamar. Data penyewa &amp; riwayat pembayarannya tetap tersimpan.</p>

<?php elseif ($tab === 'booking'):
  $r = $db->query("SELECT b.*, k.no_kamar, k.tipe, k.harga FROM booking b
     LEFT JOIN kamar k ON k.id=b.kamar_id ORDER BY (b.status='baru') DESC, b.id DESC"); ?>
<div class="wrap"><table><tr><th>Tanggal</th><th>Calon Penyewa</th><th>Kamar</th><th>Rencana Masuk</th><th>Status</th><th>Aksi</th></tr>
<?php $c = 0; while ($b = $r->fetch_assoc()): $c++;
  $wa = 'https://wa.me/' . preg_replace('/^0/', '62', preg_replace('/\D/', '', $b['no_hp'])); ?>
<tr><td><?= e(substr($b['created_at'], 0, 10)) ?></td><td><b><?= e($b['nama']) ?></b><br><a target="_blank" href="<?= e($wa) ?>"><?= e($b['no_hp']) ?></a></td>
<td><?= e($b['no_kamar'] ?? '(dihapus)') ?> <small style="color:var(--muted)"><?= e($b['tipe'] ?? '') ?></small><br><?= $b['harga'] ? rp($b['harga']) : '' ?></td>
<td><?= e($b['tgl_masuk']) ?></td><td><?= badge($b['status']) ?></td>
<td><?php if ($b['status'] === 'baru') echo aksi('booking_setuju', $b['id'], '&#10003; Setujui', 'green') . aksi('booking_tolak', $b['id'], 'Tolak', 'red'); ?></td></tr>
<?php endwhile; if (!$c): ?><tr><td colspan="6" class="empty">Belum ada booking.</td></tr><?php endif; ?></table></div>
<p style="color:var(--muted);font-size:.85rem">Setuju = penyewa baru otomatis dibuat &amp; kamar ditandai terisi. Klik nomor HP untuk menghubungi via WhatsApp sebelum menyetujui.</p>

<?php else:
  $r = $db->query("SELECT * FROM pengumuman ORDER BY id DESC"); ?>
<form method="post" class="box formbox" style="margin-bottom:24px">
  <input type="hidden" name="aksi" value="info_tambah">
  <label>Judul</label><input name="judul" maxlength="120" required>
  <label>Isi pengumuman</label><textarea name="isi" required></textarea>
  <button>Terbitkan</button>
</form>
<div class="wrap"><table><tr><th>Tanggal</th><th>Judul</th><th>Isi</th><th></th></tr>
<?php $c = 0; while ($a = $r->fetch_assoc()): $c++; ?>
<tr><td><?= e(substr($a['created_at'], 0, 10)) ?></td><td><b><?= e($a['judul']) ?></b></td><td><?= nl2br(e($a['isi'])) ?></td>
<td><form method="post" class="inline" onsubmit="return confirm('Hapus pengumuman?')"><input type="hidden" name="aksi" value="info_hapus"><input type="hidden" name="rid" value="<?= $a['id'] ?>"><button class="red">Hapus</button></form></td></tr>
<?php endwhile; if (!$c): ?><tr><td colspan="4" class="empty">Belum ada pengumuman.</td></tr><?php endif; ?></table></div>
<?php endif; ?>

<?php /* ---------- LAPORAN BULANAN ---------- */ elseif ($page === 'laporan'):
  $bln = bulanOk($_GET['bulan'] ?? '') ? $_GET['bulan'] : date('Y-m');
  $sum = fn($st) => q("SELECT COALESCE(SUM(jumlah),0) FROM pembayaran WHERE status=? AND bulan=?", 'ss', $st, $bln)->get_result()->fetch_row()[0];
  $masuk = $sum('lunas'); $tunggak = $sum('belum');
  $jml = q("SELECT COUNT(*) FROM pembayaran WHERE bulan=?", 's', $bln)->get_result()->fetch_row()[0];
  $totK = $db->query("SELECT COUNT(*) FROM kamar")->fetch_row()[0];
  $isiK = $db->query("SELECT COUNT(*) FROM kamar WHERE status='terisi'")->fetch_row()[0];
  $okup = $totK ? round($isiK / $totK * 100) : 0;
  $r = q("SELECT b.*, p.nama, k.no_kamar FROM pembayaran b JOIN penyewa p ON p.id=b.penyewa_id
          LEFT JOIN kamar k ON k.id=p.kamar_id WHERE b.bulan=? ORDER BY b.status, p.nama", 's', $bln)->get_result(); ?>
<h1>Laporan Bulanan<small>Periode <?= e($bln) ?> &mdash; dicetak <?= date('d/m/Y') ?></small></h1><hr class="gline">
<form class="bar" method="get"><input type="hidden" name="page" value="laporan">
  <input type="month" name="bulan" value="<?= e($bln) ?>"> <button>Tampilkan</button>
  <button type="button" class="ghost" onclick="window.print()">&#128424; Cetak Laporan</button></form>
<div class="cards">
  <div class="card"><div class="lbl">Pemasukan (Lunas)</div><b><?= rp($masuk) ?></b></div>
  <div class="card"><div class="lbl">Belum Dibayar</div><b><?= rp($tunggak) ?></b></div>
  <div class="card"><div class="lbl">Jumlah Tagihan</div><b><?= $jml ?></b></div>
  <div class="card"><div class="lbl">Okupansi Kamar</div><b><?= $okup ?>%</b></div>
</div>
<div class="wrap"><table>
<tr><th>Penyewa</th><th>Kamar</th><th>Jumlah</th><th>Tgl Bayar</th><th>Status</th></tr>
<?php $n = 0; while ($b = $r->fetch_assoc()): $n++; ?>
<tr><td><b><?= e($b['nama']) ?></b></td><td><?= e($b['no_kamar'] ?? '-') ?></td><td><?= rp($b['jumlah']) ?></td><td><?= e($b['tgl_bayar'] ?? '-') ?></td>
<td><span class="badge <?= e($b['status']) ?>"><?= e($b['status']) ?></span></td></tr>
<?php endwhile; if (!$n): ?><tr><td colspan="5" class="empty">Tidak ada tagihan pada bulan ini.</td></tr><?php endif; ?></table></div>

<?php /* ---------- DASHBOARD ---------- */ else:
  $one = fn($sql) => $db->query($sql)->fetch_row()[0];
  $bulan = date('Y-m');
  $totK = $one("SELECT COUNT(*) FROM kamar"); $isiK = $one("SELECT COUNT(*) FROM kamar WHERE status='terisi'");
  $okup = $totK ? round($isiK / $totK * 100) : 0;
  $tag = $db->query("SELECT b.bulan, b.jumlah, p.nama FROM pembayaran b JOIN penyewa p ON p.id=b.penyewa_id
                     WHERE b.status='belum' ORDER BY b.bulan LIMIT 5");
  $nama_bln = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
  $data = [];
  for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime(date('Y-m-01') . " -$i month"));
    $val = q("SELECT COALESCE(SUM(jumlah),0) FROM pembayaran WHERE status='lunas' AND bulan=?", 's', $m)->get_result()->fetch_row()[0];
    $data[] = [$nama_bln[substr($m, 5, 2)], (int)$val];
  }
  $max = max(array_column($data, 1)); ?>
<h1>Selamat datang, <?= e($_SESSION['user']['nama']) ?><small>Ringkasan hunian dan keuangan Anda</small></h1><hr class="gline">
<?php if ($totPend): ?><div class="info"><b>&#9993; Ada <?= (int)$totPend ?> permintaan pelanggan menunggu.</b> <a href="?page=permintaan">Lihat sekarang &rarr;</a></div><?php endif; ?>
<div class="cards">
  <div class="card"><span class="ic">&#9635;</span><div class="lbl">Total Kamar</div><b><?= $totK ?></b></div>
  <div class="card"><span class="ic">&#9670;</span><div class="lbl">Kamar Terisi</div><b><?= $isiK ?></b></div>
  <div class="card"><span class="ic">&#9671;</span><div class="lbl">Kamar Kosong</div><b><?= $totK - $isiK ?></b></div>
  <div class="card"><span class="ic">&#9786;</span><div class="lbl">Penyewa</div><b><?= $one("SELECT COUNT(*) FROM penyewa") ?></b></div>
  <div class="card"><span class="ic">&#10070;</span><div class="lbl">Pemasukan Bulan Ini</div><b><?= rp($one("SELECT COALESCE(SUM(jumlah),0) FROM pembayaran WHERE status='lunas' AND bulan='$bulan'")) ?></b></div>
  <div class="card"><span class="ic">&#9888;</span><div class="lbl">Tagihan Belum Lunas</div><b><?= $one("SELECT COUNT(*) FROM pembayaran WHERE status='belum'") ?></b></div>
</div>
<div class="two">
  <div class="box">
    <h2>Pemasukan 6 bulan terakhir</h2>
    <div class="chart">
    <?php foreach ($data as [$lb, $val]):
      $h = $max > 0 ? max(4, (int)round($val / $max * 140)) : 4; ?>
      <div class="col" title="<?= e(rp($val)) ?>">
        <em><?= $val ? number_format($val / 1000000, 1, ',', '.') . ' jt' : '-' ?></em>
        <div class="fill" style="height:<?= $h ?>px"></div><span><?= $lb ?></span>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
  <div class="box">
    <h2>Tingkat okupansi</h2>
    <div class="big"><?= $okup ?>%</div>
    <div class="prog"><i style="width:<?= $okup ?>%"></i></div>
    <div style="color:var(--muted);font-size:.9rem"><?= $isiK ?> dari <?= $totK ?> kamar sedang terisi</div>
  </div>
</div>
<h2>Tagihan menunggu pelunasan</h2>
<div class="wrap"><table>
<tr><th>Penyewa</th><th>Bulan</th><th>Jumlah</th></tr>
<?php $n = 0; while ($t = $tag->fetch_assoc()): $n++; ?>
<tr><td><b><?= e($t['nama']) ?></b></td><td><?= e($t['bulan']) ?></td><td><?= rp($t['jumlah']) ?></td></tr>
<?php endwhile; if (!$n): ?><tr><td colspan="3" class="empty">Semua tagihan sudah lunas.</td></tr><?php endif; ?>
</table></div>
<?php endif; ?>
</main>

<?php if ($logged): ?>
<script>
(function () {
  var KEY = 'kos_seen', SND = 'kos_sound', ac = null, seen = null;
  try { seen = JSON.parse(localStorage.getItem(KEY) || 'null'); } catch (e) {}
  var soundOn = localStorage.getItem(SND) !== 'off';
  var baseTitle = document.title;
  var labels = {bukti: 'bukti transfer baru', keluhan: 'keluhan baru', keluar: 'pengajuan keluar kamar', booking: 'booking baru'};
  var box = document.createElement('div'); box.id = 'toasts'; document.body.appendChild(box);

  /* browser hanya mengizinkan suara setelah ada interaksi pertama */
  function unlock() {
    if (!ac) { var C = window.AudioContext || window.webkitAudioContext; if (C) ac = new C(); }
    if (ac && ac.state === 'suspended') ac.resume();
  }
  ['click', 'keydown', 'touchstart'].forEach(function (ev) { document.addEventListener(ev, unlock); });

  function beep() {
    if (!soundOn || !ac || ac.state !== 'running') return;
    [[880, 0], [1175, .18], [1480, .36]].forEach(function (n) {
      var o = ac.createOscillator(), g = ac.createGain(), t = ac.currentTime + n[1];
      o.type = 'sine'; o.frequency.value = n[0];
      g.gain.setValueAtTime(0, t);
      g.gain.linearRampToValueAtTime(.25, t + .02);
      g.gain.exponentialRampToValueAtTime(.001, t + .35);
      o.connect(g); g.connect(ac.destination); o.start(t); o.stop(t + .4);
    });
  }

  function toast(msg, href) {
    var d = document.createElement('div'); d.className = 'toast';
    d.innerHTML = '<b>&#128276; Permintaan baru</b><span></span><br><a href="' + href + '">Lihat sekarang &rarr;</a>';
    d.querySelector('span').textContent = msg;
    box.appendChild(d);
    setTimeout(function () { d.remove(); }, 15000);
  }

  function badge(total) {
    var nav = document.getElementById('navReq'); if (!nav) return;
    var i = nav.querySelector('i.cnt');
    if (total > 0) {
      if (!i) { i = document.createElement('i'); i.className = 'cnt'; nav.appendChild(i); }
      i.textContent = total;
    } else if (i) i.remove();
    document.title = (total > 0 ? '(' + total + ') ' : '') + baseTitle;
  }

  function notify(msgs, first) {
    var text = msgs.join(', '), href = '?page=permintaan&tab=' + first;
    toast(text, href); beep();
    if ('Notification' in window && Notification.permission === 'granted') {
      var n = new Notification('Royal Kos', {body: text});
      n.onclick = function () { window.focus(); location.href = href; };
    }
  }

  function poll() {
    var qs = '?page=notif' + (seen ? '&' + new URLSearchParams(seen).toString() : '');
    fetch(qs, {credentials: 'same-origin', cache: 'no-store'})
      .then(function (r) { return r.json(); })
      .then(function (d) {
        var total = 0, msgs = [], first = null;
        for (var k in labels) {
          total += d.pending[k];
          if (seen && d.fresh[k] > 0) { msgs.push(d.fresh[k] + ' ' + labels[k]); if (!first) first = k; }
        }
        badge(total);
        seen = d.max; localStorage.setItem(KEY, JSON.stringify(seen));
        if (msgs.length) notify(msgs, first);
      }).catch(function () {});
  }

  var btn = document.getElementById('sndBtn');
  function label() { if (btn) btn.innerHTML = soundOn ? '&#128266; Suara: aktif' : '&#128263; Suara: mati'; }
  if (btn) btn.addEventListener('click', function () {
    soundOn = !soundOn; localStorage.setItem(SND, soundOn ? 'on' : 'off'); label();
    if ('Notification' in window && Notification.permission === 'default') Notification.requestPermission();
    unlock(); beep();
  });
  label(); poll(); setInterval(poll, 8000);
})();
</script>
<?php endif; ?>
</body></html>