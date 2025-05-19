<?php
include "../config/connection.php";

session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id_user'];
$username = $_SESSION['username'];

// Handle password change
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password fields
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "All password fields are required";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "New password and confirmation do not match";
        $message_type = "error";
    } elseif (strlen($new_password) < 8) {
        $message = "New password must be at least 8 characters long";
        $message_type = "error";
    } else {
        // Check if current password is correct
        $sql = "SELECT password FROM users WHERE ID_user = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $stored_password = $row['password'];
            
            // Verify current password
            if (password_verify($current_password, $stored_password)) {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password in database
                $update_sql = "UPDATE users SET password = ? WHERE ID_user = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_stmt->execute()) {
                    $message = "Password changed successfully";
                    $message_type = "success";
                    
                    // Clear post data to prevent resubmission
                    $_POST = array();
                } else {
                    $message = "Error updating password: " . $conn->error;
                    $message_type = "error";
                }
            } else {
                $message = "Current password is incorrect";
                $message_type = "error";
            }
        } else {
            $message = "User not found";
            $message_type = "error";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Cokro Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            --text-color: #333;
            --light-gray: #e9ecef;
            --medium-gray: #adb5bd;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            margin-bottom: 30px;
        }
        
        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: var(--dark-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--primary-color);
        }
        
        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .password-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        
        .password-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .password-title {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .password-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .password-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--light-gray);
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .password-requirements {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .requirements-title {
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .requirements-list {
            list-style-type: none;
            padding-left: 5px;
        }
        
        .requirements-list li {
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .requirements-list li i {
            color: var(--medium-gray);
        }
        
        .requirements-list li.valid i {
            color: var(--success-color);
        }
        
        .buttons-container {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            justify-content: space-between;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle .form-control {
            padding-right: 40px;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--medium-gray);
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 40px 0 20px;
            margin-top: 50px;
        }
        
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 30px;
        }
        
        .footer-column {
            flex: 1;
            min-width: 200px;
        }
        
        .footer-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            position: relative;
        }
        
        .footer-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 40px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #adb5bd;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 40px;
            text-align: center;
            font-size: 0.9rem;
            color: #adb5bd;
        }
        
        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .buttons-container {
                flex-direction: column;
            }
            
            .footer-content {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="#" class="logo">Cokro Hotel</a>
            <div class="nav-links">
                <a href="../template/dashboard.php"><i class="fas fa-home"></i> Home</a>
                <a href="../template/rooms.php"><i class="fas fa-hotel"></i> Rooms</a>
                <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Message Display -->
        <?php if(!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Change Password Form -->
        <div class="password-container">
            <div class="password-header">
                <h1 class="password-title">Change Password</h1>
                <p class="password-subtitle">Update your password to keep your account secure</p>
            </div>
            
            <div class="password-form">
                <form action="change_password.php" method="post" id="password-form">
                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <div class="password-toggle">
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                            <span class="toggle-password" data-target="current_password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="password-toggle">
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                            <span class="toggle-password" data-target="new_password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="password-toggle">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            <span class="toggle-password" data-target="confirm_password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="password-requirements">
                        <div class="requirements-title">
                            <i class="fas fa-shield-alt"></i> Password Requirements
                        </div>
                        <ul class="requirements-list">
                            <li id="length-check"><i class="fas fa-circle"></i> At least 8 characters long</li>
                            <li id="number-check"><i class="fas fa-circle"></i> Contains at least one number</li>
                            <li id="match-check"><i class="fas fa-circle"></i> Passwords match</li>
                        </ul>
                    </div>
                    
                    <div class="buttons-container">
                        <a href="profile.php" class="btn btn-outline">Back to Profile</a>
                        <button type="submit" name="change_password" class="btn btn-primary" id="submit-btn">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3 class="footer-title">Cokro Hotel</h3>
                    <p>Experience luxury and comfort in the heart of the city. Our hotel offers premium accommodations with exceptional service.</p>
                </div>
                <div class="footer-column">
                    <h3 class="footer-title">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="../template/dashboard.php">Home</a></li>
                        <li><a href="../template/rooms.php">Rooms</a></li>
                        <li><a href="../template/services.php">Services</a></li>
                        <li><a href="../template/about.php">About Us</a></li>
                        <li><a href="../template/contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3 class="footer-title">Contact Us</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> Jl. Cokro No. 11, Jakarta</li>
                        <li><i class="fas fa-phone"></i> +62 123 456 7890</li>
                        <li><i class="fas fa-envelope"></i> info@cokrohotel.com</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Cokro Hotel. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // Password validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const lengthCheck = document.getElementById('length-check');
        const numberCheck = document.getElementById('number-check');
        const matchCheck = document.getElementById('match-check');
        const submitBtn = document.getElementById('submit-btn');
        
        function validatePassword() {
            const password = newPassword.value;
            const confirmPwd = confirmPassword.value;
            
            // Check length
            if (password.length >= 8) {
                lengthCheck.classList.add('valid');
                lengthCheck.querySelector('i').classList.remove('fa-circle');
                lengthCheck.querySelector('i').classList.add('fa-check-circle');
            } else {
                lengthCheck.classList.remove('valid');
                lengthCheck.querySelector('i').classList.remove('fa-check-circle');
                lengthCheck.querySelector('i').classList.add('fa-circle');
            }
            
            // Check for number
            if (/\d/.test(password)) {
                numberCheck.classList.add('valid');
                numberCheck.querySelector('i').classList.remove('fa-circle');
                numberCheck.querySelector('i').classList.add('fa-check-circle');
            } else {
                numberCheck.classList.remove('valid');
                numberCheck.querySelector('i').classList.remove('fa-check-circle');
                numberCheck.querySelector('i').classList.add('fa-circle');
            }
            
            // Check passwords match
            if (password === confirmPwd && password !== '') {
                matchCheck.classList.add('valid');
                matchCheck.querySelector('i').classList.remove('fa-circle');
                matchCheck.querySelector('i').classList.add('fa-check-circle');
            } else {
                matchCheck.classList.remove('valid');
                matchCheck.querySelector('i').classList.remove('fa-check-circle');
                matchCheck.querySelector('i').classList.add('fa-circle');
            }
            
            // Enable/disable submit button based on validation
            if (password.length >= 8 && /\d/.test(password) && password === confirmPwd && password !== '') {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
        
        newPassword.addEventListener('keyup', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
        
        // Form validation before submit
        document.getElementById('password-form').addEventListener('submit', function(event) {
            const password = newPassword.value;
            const confirmPwd = confirmPassword.value;
            
            if (password.length < 8 || !/\d/.test(password) || password !== confirmPwd) {
                event.preventDefault();
                alert('Please make sure your password meets all requirements.');
            }
        });
    </script>
</body>

</html>