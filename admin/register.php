<?php
session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

require_once '../config/database.php';

function ensureUsersTableExists($pdo) {
    try {
        $pdo->query("SELECT 1 FROM users LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
    }
}

ensureUsersTableExists($pdo);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    // Validasi input
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Username dan password harus diisi!';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok!';
    } else {
        try {
            // Cek apakah username sudah ada
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan! Gunakan username lain.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user baru
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $full_name, $email]);
                
                // Auto login setelah registrasi
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_username'] = $username;
                $_SESSION['user_name'] = $full_name ?: $username;
                
                header('Location: dashboard.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Admin - PLBN Wini</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            position: relative;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #1a3a5c;
            background-image: url('../assets/images/plbn wini.jpg');
            background-position: center center;
            background-repeat: no-repeat;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            margin: 0;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: rgba(10, 35, 52, 0.18);
            pointer-events: none;
        }
        
        .register-container {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 480px;
            padding: 40px 35px;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1a3a5c 0%, #2d6a8f 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2.5rem;
            color: #fff;
            box-shadow: 0 4px 15px rgba(26, 58, 92, 0.3);
        }
        
        .register-header h1 {
            color: #1a3a5c;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .register-header p {
            color: #888;
            font-size: 0.95rem;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .alert-danger {
            background: #fde8ea;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-danger i {
            color: #dc3545;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-success i {
            color: #28a745;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .form-group label i {
            margin-right: 8px;
            color: #1a3a5c;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e1e5ea;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #f8f9fa;
            color: #333;
        }
        
        .input-group input:focus {
            border-color: #1a3a5c;
            outline: none;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(26, 58, 92, 0.1);
        }
        
        .input-group input::placeholder {
            color: #aaa;
        }
        
        .input-group .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1rem;
        }
        
        .input-group .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
            transition: color 0.3s;
            font-size: 1rem;
        }
        
        .input-group .toggle-password:hover {
            color: #1a3a5c;
        }
        
        .password-strength {
            margin-top: 8px;
            font-size: 0.8rem;
            color: #666;
        }
        
        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-indicator {
            height: 100%;
            width: 0;
            background: #dc3545;
            border-radius: 2px;
            transition: width 0.3s;
        }
        
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a3a5c 0%, #2d6a8f 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(26, 58, 92, 0.4);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #888;
            font-size: 0.95rem;
        }
        
        .login-link a {
            color: #1a3a5c;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .security-badge {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e8ecf0;
        }
        
        .security-badge i {
            color: #28a745;
            margin-right: 6px;
        }
        
        .security-badge span {
            color: #888;
            font-size: 0.8rem;
        }
        
        @media (max-width: 480px) {
            .register-container {
                padding: 25px 20px;
            }
            
            .register-header h1 {
                font-size: 1.5rem;
            }
            
            .register-header .logo {
                width: 65px;
                height: 65px;
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="logo">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>Daftar Akun</h1>
            <p>PLBN Wini - Sistem Manajemen</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" placeholder="Buat username" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email (Opsional)</label>
                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="Masukkan email">
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-id-badge"></i> Nama Lengkap (Opsional)</label>
                <div class="input-group">
                    <i class="fas fa-id-badge input-icon"></i>
                    <input type="text" name="full_name" placeholder="Masukkan nama lengkap">
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="password" placeholder="Buat password (min. 6 karakter)" required onkeyup="checkPasswordStrength()">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password')"></i>
                </div>
                <div class="password-strength">
                    Kekuatan: <span id="strength-text">Lemah</span>
                    <div class="strength-bar">
                        <div class="strength-indicator" id="strength-bar"></div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Konfirmasi Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Konfirmasi password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                </div>
            </div>
            
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Daftar
            </button>
        </form>
        
        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
        
        <div class="security-badge">
            <i class="fas fa-shield-alt"></i>
            <span>Koneksi aman terenkripsi</span>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            var field = document.getElementById(fieldId);
            var icon = event.target;
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        function checkPasswordStrength() {
            var password = document.getElementById('password').value;
            var strengthBar = document.getElementById('strength-bar');
            var strengthText = document.getElementById('strength-text');
            var strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (password.length >= 10) strength += 25;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 10;
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 30) {
                strengthText.textContent = 'Lemah';
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (strength < 60) {
                strengthText.textContent = 'Sedang';
                strengthBar.style.backgroundColor = '#ffc107';
            } else if (strength < 85) {
                strengthText.textContent = 'Kuat';
                strengthBar.style.backgroundColor = '#28a745';
            } else {
                strengthText.textContent = 'Sangat Kuat';
                strengthBar.style.backgroundColor = '#20c997';
            }
        }
    </script>
</body>
</html>
