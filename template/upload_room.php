<?php
include '../config/connection.php';
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Proses form upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_name = $_POST['room_name'];
    $room_price = $_POST['room_price'];
    $room_facilities = $_POST['room_facilities'];
    $room_image = $_FILES['room_image'];

    // Validasi input
    if (empty($room_name) || empty($room_price) || empty($room_facilities) || empty($room_image['name'])) {
        $_SESSION['error'] = "Silakan isi semua bidang yang diperlukan.";
    } else {
        // Upload gambar
        $target_dir = '../uploads/';
        $filename = time() . '_' . basename($room_image['name']); // Unique filename
        $target_file = $target_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Cek apakah file adalah gambar
        $check = getimagesize($room_image['tmp_name']);
        if ($check === false) {
            $_SESSION['error'] = "File bukan gambar yang valid.";
        } 
        // Cek ukuran file (max 5MB)
        else if ($room_image['size'] > 5000000) {
            $_SESSION['error'] = "Ukuran file terlalu besar. Maksimal 5MB.";
        } 
        // Cek format file
        else if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $_SESSION['error'] = "Hanya file JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
        } else {
            // Upload file ke folder uploads
            if (move_uploaded_file($room_image['tmp_name'], $target_file)) {
                // Simpan data kamar ke database
                $stmt = $conn->prepare("INSERT INTO rooms (name, price, facilities, image) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sdss", $room_name, $room_price, $room_facilities, $filename);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Kamar berhasil ditambahkan!";
                    header("Location: manage_rooms.php");
                    exit;
                } else {
                    $_SESSION['error'] = "Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = "Maaf, terjadi kesalahan saat mengunggah file.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Kamar - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --warning-color: #fca311;
            --danger-color: #e63946;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: var(--dark-color);
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100%;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-header h2 {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-header h2 i {
            font-size: 1.5rem;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            gap: 10px;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--accent-color);
        }
        
        .sidebar-menu i {
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background-color: #c1121f;
        }
        
        .page-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--dark-color);
        }
        
        /* Form Styles */
        .form-container {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .form-title {
            font-size: 1.2rem;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
            color: var(--dark-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .file-upload {
            background-color: #f8f9fa;
            border: 1px dashed #ced4da;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .file-upload:hover {
            background-color: #e9ecef;
        }
        
        .file-upload input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-upload i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .file-upload p {
            margin: 0;
            color: #6c757d;
        }
        
        .submit-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .submit-btn:hover {
            background-color: #419642;
        }
        
        .cancel-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            margin-right: 10px;
        }
        
        .cancel-btn:hover {
            background-color: #5a6268;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #721c24;
        }
        
        #filename-display {
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--primary-color);
            display: none;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar-header h2 span, .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a {
                justify-content: center;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .cancel-btn, .submit-btn {
                width: 100%;
                justify-content: center;
            }
            
            .cancel-btn {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-cog"></i> <span>Admin Panel</span></h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_rooms.php"><i class="fas fa-hotel"></i> <span>Kelola Kamar</span></a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> <span>Kelola Pengguna</span></a></li>
                <li><a href="manage_bookings.php"><i class="fas fa-calendar-alt"></i> <span>Kelola Reservasi</span></a></li>
                <li><a href="#" class="active"><i class="fas fa-upload"></i> <span>Upload Kamar</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Upload Kamar Baru</h1>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="form-container">
                <h3 class="form-title">Detail Kamar</h3>
                
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                            echo $_SESSION['success']; 
                            unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="room_name">Nama Kamar</label>
                        <input type="text" id="room_name" name="room_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="room_price">Harga (Rp)</label>
                        <input type="number" id="room_price" name="room_price" class="form-control" min="0" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="room_facilities">Fasilitas</label>
                        <textarea id="room_facilities" name="room_facilities" class="form-control" placeholder="Contoh: AC, TV, WiFi, Kamar Mandi Dalam, dll" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="room_image">Foto Kamar</label>
                        <div class="file-upload">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Klik atau drag & drop untuk upload gambar</p>
                            <p><small>Format: JPG, JPEG, PNG, GIF (Max: 5MB)</small></p>
                            <input type="file" id="room_image" name="room_image" accept="image/*" required>
                            <div id="filename-display"></div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="manage_rooms.php" class="cancel-btn"><i class="fas fa-times"></i> Batal</a>
                        <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Simpan Kamar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        // Display selected filename
        document.getElementById('room_image').addEventListener('change', function() {
            const fileDisplay = document.getElementById('filename-display');
            if (this.files && this.files[0]) {
                fileDisplay.textContent = 'File dipilih: ' + this.files[0].name;
                fileDisplay.style.display = 'block';
            } else {
                fileDisplay.style.display = 'none';
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.getElementById('room_name').value.trim();
            const price = document.getElementById('room_price').value;
            const facilities = document.getElementById('room_facilities').value.trim();
            const image = document.getElementById('room_image').files;
            
            if (!name || !price || !facilities || !image.length) {
                e.preventDefault();
                alert('Silakan isi semua bidang yang diperlukan.');
            }
        });
    </script>
</body>

</html>