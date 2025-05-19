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
        $password = $_POST['password'];

        $sql = "SELECT id_user, password, role FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id_user, $hashed_password, $role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['id_user'] = $id_user;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;

                // Redirect berdasarkan peran
                if ($role == "admin") {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit;
            } else {
                $error_message = "Username atau password tidak valid!";
            }
        } else {
            $error_message = "Username atau password tidak valid!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/login_page.css">
    <title>Login</title>
    <link rel="stylesheet" href="../styles/login_page.css">
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
                    <a href="login.php"><button class="active">Login</button></a>
                    <span class="divider">|</span>
                    <a href="register.php"><button>Register</button></a>
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
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="submit-button">
                        <button type="submit">Login</button>
                    </div>
                </form>
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.slide');
            const prevBtn = document.querySelector('.prev');
            const nextBtn = document.querySelector('.next');
            let currentSlide = 0;
            
            // Hide all slides except the first one
            function showSlide(index) {
                const slideWidth = slides[0].clientWidth;
                document.querySelector('.slides').style.transform = `translateX(-${index * slideWidth}px)`;
            }
            
            // Next slide
            nextBtn.addEventListener('click', function() {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            });
            
            // Previous slide
            prevBtn.addEventListener('click', function() {
                currentSlide = (currentSlide - 1 + slides.length) % slides.length;
                showSlide(currentSlide);
            });
            
            // Auto slide every 5 seconds
            setInterval(function() {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            }, 5000);
            
            // Initial slide
            showSlide(0);
        });
    </script>
</body>

</html>