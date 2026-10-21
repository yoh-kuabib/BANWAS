<?php
session_start();

// If already logged in, redirect to dashboard
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        try {
            // Coba login dari users table
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_name'] = $user['full_name'] ?: $user['username'];
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Password salah!';
                }
            } else {
                $error = 'Username tidak ditemukan!';
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
    <title>Login Admin - PLBN Wini</title>
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
        
        .login-container {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 440px;
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
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header .logo {
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
        
        .login-header h1 {
            color: #1a3a5c;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .login-header p {
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
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            font-size: 0.9rem;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .form-options label {
            display: flex;
            align-items: center;
            cursor: pointer;
            color: #666;
            font-weight: 400;
        }
        
        .form-options label input[type="checkbox"] {
            margin-right: 8px;
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #1a3a5c;
        }
        
        .form-options a {
            color: #1a3a5c;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .form-options a:hover {
            color: #2d6a8f;
            text-decoration: underline;
        }
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(26, 58, 92, 0.4);
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
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #aaa;
            font-size: 0.8rem;
        }
        
        .login-footer a {
            color: #1a3a5c;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .register-prompt {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #0c5460;
        }
        
        .register-prompt a {
            color: #0c5460;
            font-weight: 600;
            text-decoration: none;
        }
        
        .register-prompt a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 25px 20px;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
            
            .login-header .logo {
                width: 65px;
                height: 65px;
                font-size: 2rem;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1>Admin Panel</h1>
            <p>PLBN Wini - Sistem Manajemen</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="register-prompt">
            <i class="fas fa-info-circle"></i>
            Belum punya akun? <a href="register.php">Daftar di sini untuk membuat akun baru</a>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" placeholder="Masukkan username" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="password" placeholder="Masukkan password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>
            </div>
            
            <div class="form-options">
                <label>
                    <input type="checkbox" name="remember"> Ingat saya
                </label>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="security-badge">
            <i class="fas fa-shield-alt"></i>
            <span>Koneksi aman terenkripsi</span>
        </div>
        
        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> <a href="../index.php">PLBN Wini</a>. All rights reserved.
        </div>
    </div>

    <script>
        function togglePassword() {
            var password = document.getElementById('password');
            var toggle = document.querySelector('.toggle-password');
            if (password.type === 'password') {
                password.type = 'text';
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
