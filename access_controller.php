<?php
/**
 * File: access_controller.php
 * Tujuan: Mengontrol akses ke folder assets dan menampilkan halaman forbidden jika diperlukan
 * Letakkan file ini di root folder website Anda (di luar folder assets)
 */

// Mulai session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mendapatkan URI yang diminta
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/hotel_reservation_web/'; // Sesuaikan dengan path website Anda

// Ekstrak bagian path assets dari URI
$assets_pattern = '/\/assets\/([^?]*)/';
preg_match($assets_pattern, $request_uri, $matches);
$requested_asset = isset($matches[1]) ? $matches[1] : '';

// Flag untuk memeriksa otorisasi akses
$authorized = false;

// Fungsi untuk mengecek apakah user sudah login
function is_logged_in() {
    return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
}

// Fungsi untuk mengecek apakah user adalah admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Cek otorisasi berdasarkan jenis aset
if (empty($requested_asset)) {
    // Jika user mencoba mengakses direktori assets/ langsung
    $authorized = false;
} 
// Untuk folder profile_pictures
else if (strpos($requested_asset, 'profile_pictures/') === 0) {
    $file_name = basename($requested_asset);
    
    // Jika file adalah default profile picture, izinkan akses
    if ($file_name === 'default_profile.jpg') {
        $authorized = true;
    }
    // Jika user login dan mencoba mengakses foto profilnya sendiri
    else if (is_logged_in() && isset($_SESSION['profile_picture']) && $file_name === $_SESSION['profile_picture']) {
        $authorized = true;
    }
    // Admin dapat mengakses semua foto profil
    else if (is_admin()) {
        $authorized = true;
    }
}
// Untuk folder facilities - semua pengunjung dapat melihat
else if (strpos($requested_asset, 'facilities/') === 0) {
    $authorized = true;
}
// Untuk folder slide_assets - semua pengunjung dapat melihat
else if (strpos($requested_asset, 'slide_assets/') === 0) {
    $authorized = true;
}
// Untuk file yang bersifat publik seperti CSS, JS, dll
else if (preg_match('/\.(css|js|jpg|jpeg|png|gif|svg|webp|ico|woff|woff2|ttf|eot)$/i', $requested_asset)) {
    $authorized = true;
}

// Jika user tidak diizinkan mengakses aset, tampilkan halaman forbidden
if (!$authorized) {
    header("HTTP/1.1 403 Forbidden");
    ?>
    <!DOCTYPE HTML>
    <html>
    <head>
        <title>403 - Akses Ditolak</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                text-align: center; 
                padding-top: 50px;
                background-color: #f5f5f5;
                margin: 0;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 30px;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            h1 { 
                color: #e74c3c; 
                margin-top: 0;
            }
            p { 
                color: #555; 
                font-size: 16px;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background-color: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 20px;
                transition: background-color 0.3s;
                font-weight: bold;
            }
            .btn:hover {
                background-color: #2980b9;
            }
            .icon {
                font-size: 60px;
                margin-bottom: 20px;
                color: #e74c3c;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">⛔</div>
            <h1>403 - Akses Ditolak</h1>
            <?php if (is_logged_in()): ?>
                <p>Maaf, Anda tidak memiliki izin untuk mengakses file atau direktori ini.</p>
            <?php else: ?>
                <p>Anda perlu login untuk mengakses konten ini.</p>
                <a href="<?php echo $base_path; ?>login.php" class="btn">Login</a>
            <?php endif; ?>
            <p>
                <a href="<?php echo $base_path; ?>" class="btn">Kembali ke Beranda</a>
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Jika kode sampai disini, maka sebenarnya user diizinkan mengakses file tersebut
// Tapi karena .htaccess mengalihkan request kesini, kita perlu menjawab dengan file yang diminta

$file_path = __DIR__ . '/assets/' . $requested_asset;

if (file_exists($file_path)) {
    // Tentukan content type berdasarkan ekstensi file
    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
    $content_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject'
    ];
    
    $content_type = isset($content_types[strtolower($extension)]) 
        ? $content_types[strtolower($extension)] 
        : 'application/octet-stream';
    
    header("Content-Type: " . $content_type);
    readfile($file_path);
    exit;
} else {
    // File tidak ditemukan
    header("HTTP/1.1 404 Not Found");
    ?>
    <!DOCTYPE HTML>
    <html>
    <head>
        <title>404 - File Tidak Ditemukan</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                text-align: center; 
                padding-top: 50px;
                background-color: #f5f5f5;
                margin: 0;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 30px;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            h1 { 
                color: #e74c3c; 
                margin-top: 0;
            }
            p { 
                color: #555; 
                font-size: 16px;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background-color: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 20px;
                transition: background-color 0.3s;
                font-weight: bold;
            }
            .btn:hover {
                background-color: #2980b9;
            }
            .icon {
                font-size: 60px;
                margin-bottom: 20px;
                color: #e74c3c;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">🔍</div>
            <h1>404 - File Tidak Ditemukan</h1>
            <p>File yang Anda cari tidak ditemukan.</p>
            <a href="<?php echo $base_path; ?>" class="btn">Kembali ke Beranda</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>  