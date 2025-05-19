<?php
session_start();
include '../config/connection.php';


if (!isset($_SESSION['last_regenerated']) || 
    $_SESSION['last_regenerated'] < time() - 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regenerated'] = time();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Verifikasi keamanan gagal. Silakan coba lagi.";
    } else {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        if (strlen($password) < 8) {
            $error_message = "Password harus minimal 8 karakter.";
        } else {
            $check_sql = "SELECT id_user FROM users WHERE username = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $error_message = "Username sudah digunakan. Silakan pilih username lain.";
            } else {
                $check_email_sql = "SELECT id_user FROM users WHERE email = ?";
                $check_email_stmt = $conn->prepare($check_email_sql);
                $check_email_stmt->bind_param("s", $email);
                $check_email_stmt->execute();
                $check_email_stmt->store_result();
                
                if ($check_email_stmt->num_rows > 0) {
                    $error_message = "Email sudah terdaftar. Silakan gunakan email lain.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'user'; // Default sebagai user
                    
                    $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
                    
                    if ($stmt->execute()) {
                        $_SESSION['register_success'] = true;
                        header('Location: login.php');
                        exit;
                    } else {
                        $error_message = "Error: " . $stmt->error;
                    }
                }
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
    <title>Register - Cokro Hotel</title>
    <link rel="stylesheet" href="../styles/register.css">
</head>

<body>
    <div class="login-container">
        <div class="navbar">
            <div class="wifi-image">
                <img src="../assets/wifi.png" alt="WiFi">
            </div>
            <div class="navbar-container">
                <div class="navbar-content">
                    <img src="../assets/hotel_logo.png" alt="Hotel Logo">
                </div>
            </div>
        </div>

        <div class="content">
            <div class="login-page">
                <div class="greeting">
                    <h1>Welcome to</h1>
                    <h2>Cokro Hotel</h2>
                </div>
                <div class="tabs">
                    <a href="login.php"><button>Login</button></a>
                    <span class="divider">|</span>
                    <a href="register.php" class="active"><button>Register</button></a>
                </div>
                <form method="post" class="login-form">
                    <?php if(isset($error_message) && !empty($error_message)): ?>
                        <div class="error-message">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="input-group">
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Password" required>
                        <div class="password-requirements">
                            Minimal 8 karakter
                        </div>
                    </div>
                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">Saya menyetujui <a href="terms.php">syarat dan ketentuan</a> yang berlaku</label>
                    </div>
                    <div class="submit-button">
                        <button type="submit">Register</button>
                    </div>
                </form>
            </div>

            <div class="room-hotel">
                <div class="slider">
                    <div class="slides">
                        <div class="slide">
                            <img src="../assets/slide_assets/img_slide_1.jpg" alt="Room 1" loading="lazy">
                            <div class="caption">kamar dengan Jenis VIP room</div>
                        </div>
                        <div class="slide">
                            <img src="../assets/slide_assets/img_slide_2.jpg" alt="Room 2" loading="lazy">
                            <div class="caption">Marrot Hotel</div>
                        </div>
                        <div class="slide">
                            <img src="../assets/slide_assets/img_slide_3.jpg" alt="Room 3" loading="lazy">
                            <div class="caption">Macau Hotel</div>
                        </div>
                        <div class="slide">
                            <img src="../assets/slide_assets/img_slide_4.png" alt="Room 4" loading="lazy">
                            <div class="caption">Japan Hotel</div>
                        </div>
                    </div>
                    <button class="prev">&#10094;</button>
                    <button class="next">&#10095;</button>
                </div>
            </div>
        </div>
    </div>
    <script src="../scripts/register.js"></script>
</body>

</html>