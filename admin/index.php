<?php
/**
 * Admin Index Page with Splash Screen
 */

session_start();

// Check login status
$is_logged_in = (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) || 
                (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);

// If not logged in, redirect to login
if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

// Check if tables exist
try {
    require_once '../config/database.php';
    $pdo->query("SELECT 1 FROM users LIMIT 1");
    $pdo->query("SELECT 1 FROM wisma LIMIT 1");
} catch (PDOException $e) {
    header('Location: setup_db.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - PLBN Wini</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .splash-container {
            text-align: center;
            padding: 40px;
            animation: fadeIn 0.5s ease;
        }
        
        .splash-logo {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #1a3a5c 0%, #2d6a8f 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 3rem;
            color: white;
            box-shadow: 0 10px 30px rgba(26, 58, 92, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }
        
        .splash-title {
            font-size: 2rem;
            color: #1a3a5c;
            margin-bottom: 10px;
        }
        
        .splash-subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        .splash-user {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: inline-block;
            margin-bottom: 30px;
        }
        
        .splash-user i {
            color: #ffd700;
            font-size: 1.2rem;
            margin-right: 10px;
        }
        
        .splash-user span {
            font-weight: 600;
            color: #1a3a5c;
        }
        
        .loader {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 4px solid #e1e5ea;
            border-top: 4px solid #1a3a5c;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        .loading-text {
            color: #999;
            font-size: 0.9rem;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        
        .btn-continue {
            display: inline-block;
            background: linear-gradient(135deg, #1a3a5c 0%, #2d6a8f 100%);
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(26, 58, 92, 0.3);
        }
        
        .btn-continue i {
            margin-right: 8px;
        }
        
        @media (max-width: 480px) {
            .splash-title {
                font-size: 1.5rem;
            }
            
            .splash-subtitle {
                font-size: 0.95rem;
            }
            
            .splash-user {
                padding: 15px 20px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="splash-container">
        <div class="splash-logo">
            <i class="fas fa-user-shield"></i>
        </div>
        
        <h1 class="splash-title">Admin Panel</h1>
        <p class="splash-subtitle">PLBN Wini - Sistem Manajemen Konten</p>
        
        <div class="splash-user">
            <i class="fas fa-user-circle"></i>
            Selamat datang, <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username']); ?></span>
        </div>
        
        <div class="loader"></div>
        <p class="loading-text">Mengarahkan ke dashboard...</p>
        
        <br>
        <a href="dashboard.php" class="btn-continue">
            <i class="fas fa-arrow-right"></i> Lanjut ke Dashboard
        </a>
    </div>

    <script>
        // Auto redirect after 3 seconds
        setTimeout(function() {
            window.location.href = 'dashboard.php';
        }, 3000);
        
        // Or redirect immediately on click
        document.querySelector('.btn-continue').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'dashboard.php';
        });
    </script>
</body>
</html>