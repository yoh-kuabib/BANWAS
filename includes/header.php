<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure updated contact links and page styles are not served from stale browser cache.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Get current page name for active menu
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Treat registered users as authenticated admin-panel users as well.
$is_admin = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)
    || (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true);

// Get site settings from database
try {
    require_once __DIR__ . '/../config/database.php';
    $settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
} catch (PDOException $e) {
    $settings = null;
}

// Determine base path for links
$base_path = '';
if ($current_dir == 'admin') {
    $base_path = '../';
} elseif ($current_dir == 'pages') {
    $base_path = '';
} else {
    $base_path = '';
}

$asset_path = ($current_dir == 'admin' || $current_dir == 'pages') ? '../assets/' : 'assets/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Website resmi PLBN Wini - Pos Lintas Batas Negara di perbatasan Indonesia-Timor Leste">
    <meta name="keywords" content="PLBN Wini, Perbatasan, Indonesia, Timor Leste, Wisma, Gedung Serbaguna, Kios Pasar">
    <meta name="author" content="PLBN Wini">
    
    <title><?php echo isset($settings['site_name']) ? $settings['site_name'] : 'PLBN Wini'; ?> - 
           <?php 
           if ($current_page == 'index.php' && $current_dir != 'admin') {
               echo 'Beranda';
           } elseif ($current_page == 'wisma.php') {
               echo 'Wisma Indonesia';
           } elseif ($current_page == 'gedung.php') {
               echo 'Gedung Serbaguna';
           } elseif ($current_page == 'kios.php') {
               echo 'Kios Pasar';
           } elseif ($current_page == 'gallery.php') {
               echo 'Galeri';
           } else {
               echo 'PLBN Wini';
           }
           ?>
    </title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo $asset_path; ?>css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $is_admin ? '../assets/images/favicon.ico' : 'assets/images/favicon.ico'; ?>" type="image/x-icon">
    
    <style>
        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }

        a {
            color: inherit;
        }
        
        header {
            background: linear-gradient(135deg, #1a3a5c 0%, #2d6a8f 100%);
            color: white;
            padding: 0.8rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.3);
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }
        
        .logo a {
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            transition: opacity 0.3s;
        }
        
        .logo a:hover {
            opacity: 0.9;
        }
        
        .logo-img {
            height: 50px;
            width: 50px;
            object-fit: contain;
            border-radius: 50%;
        }
        
        .logo-text {
            max-width: 235px;
            font-size: 0.98rem;
            line-height: 1.15;
            font-weight: bold;
            margin-left: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .logo-text small {
            display: block;
            font-size: 0.7rem;
            font-weight: normal;
            opacity: 0.8;
            margin-top: 4px;
        }
        
        nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-shrink: 0;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            padding: 0.5rem 0;
            border-bottom: 2px solid transparent;
            font-size: 0.95rem;
        }
        
        nav a:hover {
            color: #ffd700;
            border-bottom-color: #ffd700;
        }
        
        nav a i {
            margin-right: 5px;
        }
        
        nav a.active {
            color: #ffd700;
            border-bottom-color: #ffd700;
        }
        
        .nav-admin-btn {
            background: #ffd700 !important;
            color: #1a3a5c !important;
            padding: 8px 18px !important;
            border-radius: 25px !important;
            border-bottom: none !important;
            font-weight: 600;
            transition: all 0.3s !important;
        }
        
        .nav-admin-btn:hover {
            background: #ffe44d !important;
            color: #1a3a5c !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }
        
        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            transition: all 0.3s;
            background: rgba(255,255,255,0.05);
        }
        
        .menu-toggle:hover {
            background: rgba(255,255,255,0.15);
        }
        
        #mobileMenu {
            display: none;
            background: linear-gradient(135deg, #1a3a5c 0%, #2d6a8f 100%);
            padding: 1rem;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 999;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            animation: slideDown 0.3s ease;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        #mobileMenu a {
            display: block;
            color: white;
            padding: 12px 15px;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        #mobileMenu a:hover {
            background: rgba(255,255,255,0.1);
            padding-left: 20px;
        }
        
        #mobileMenu a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        .mobile-admin-btn {
            background: #ffd700 !important;
            color: #1a3a5c !important;
            border-radius: 5px;
            margin-top: 10px;
            text-align: center;
            font-weight: 600;
        }
        
        .page-hero {
            background: linear-gradient(135deg, #1a3a5c 0%, #2d6a8f 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .page-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 60%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.08) 0%, transparent 70%);
            transform: rotate(30deg);
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .hero-content h1 {
            font-size: 2.8rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .hero-content p {
            display: inline-block;
            max-width: 900px;
            padding: 0.55rem 1rem;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.88);
            color: #123a5b;
            font-size: 1.2rem;
            opacity: 1;
            text-shadow: none;
        }
        
        main {
            min-height: 60vh;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        body:has(.home-hero) main {
            max-width: none;
            padding: 0;
        }
        
        @media (max-width: 768px) {
            header {
                padding: 0.6rem 1rem;
            }
            
            .logo-img {
                height: 35px;
                width: 35px;
            }
            
            .logo-text {
                font-size: 1rem;
            }
            
            .logo-text small {
                font-size: 0.5rem;
                margin-top: 2px;
            }
            
            nav {
                display: none;
            }
            
            .menu-toggle {
                display: block !important;
            }
            
            .page-hero {
                padding: 2.5rem 1.5rem;
            }
            
            .hero-content h1 {
                font-size: 1.8rem;
            }
            
            .hero-content p {
                font-size: 1rem;
            }
            
            main {
                padding: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .logo-text {
                font-size: 0.85rem;
                margin-left: 5px;
            }
            
            .logo-img {
                height: 30px;
                width: 30px;
            }
            
            .hero-content h1 {
                font-size: 1.5rem;
            }
        }

        /* Admin sidebar layout */
        body.admin-layout {
            background: #f4f6f9;
        }

        body.admin-layout .admin-bar {
            display: none;
        }

        body.admin-layout > header {
            position: fixed;
            inset: 0 auto 0 0;
            width: 306px;
            height: 100vh;
            padding: 1.5rem 0;
            background: #191f2b;
            box-shadow: 3px 0 12px rgba(0, 0, 0, 0.12);
        }

        body.admin-layout .header-container {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: stretch;
        }

        body.admin-layout .logo {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        body.admin-layout .logo a {
            align-items: flex-start;
        }

        body.admin-layout .logo-text {
            color: #fff;
        }

        body.admin-layout nav {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 0;
            padding: 1rem 0;
        }

        body.admin-layout nav a {
            display: flex;
            align-items: center;
            min-height: 54px;
            margin: 0;
            padding: 0.85rem 1.5rem;
            border: 0;
            border-radius: 0;
            color: #aebbd0;
            font-size: 1rem;
        }

        body.admin-layout nav a i {
            width: 26px;
            margin-right: 14px;
            text-align: center;
        }

        body.admin-layout nav a:hover,
        body.admin-layout nav a.active {
            background: #3db477;
            color: #fff;
            border: 0;
        }

        body.admin-layout nav .admin-logout-link {
            margin-top: 1rem;
            border-top: 2px solid rgba(255, 255, 255, 0.18);
            color: #ff7272;
        }

        body.admin-layout nav .admin-logout-link:hover {
            background: rgba(255, 114, 114, 0.12);
            color: #ff7272;
        }

        body.admin-layout main,
        body.admin-layout footer {
            margin-left: 306px;
        }

        body.admin-layout .menu-toggle,
        body.admin-layout #mobileMenu {
            display: none !important;
        }

        @media (max-width: 768px) {
            body.admin-layout > header {
                position: sticky;
                width: 100%;
                height: auto;
                padding: 0.6rem 1rem;
            }

            body.admin-layout .header-container {
                height: auto;
                flex-direction: row;
                align-items: center;
            }

            body.admin-layout .logo {
                padding: 0;
                border: 0;
            }

            body.admin-layout nav {
                display: none;
            }

            body.admin-layout .menu-toggle {
                display: block !important;
            }

            body.admin-layout main,
            body.admin-layout footer {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="<?php echo ($is_admin && $current_dir == 'admin') ? 'admin-layout' : ''; ?>">
    <?php if ($is_admin && $current_dir == 'admin'): ?>
    <!-- Admin Top Bar -->
    <div class="admin-bar">
        <i class="fas fa-user-shield"></i> 
        Anda login sebagai <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? $_SESSION['user_username'] ?? 'User'); ?></strong>
        <a href="../admin/logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    <?php endif; ?>

    <!-- Main Header -->
    <header>
        <div class="header-container">
            <div class="logo">
                <a href="<?php echo $is_admin ? '../index.php' : 'index.php'; ?>">
                    <img src="<?php echo $asset_path; ?>images/BNPP.jpeg" 
                         alt="Logo Badan Nasional Pengelola Perbatasan" 
                        class="logo-img">
                    <span class="logo-text">
                        Badan Nasional Pengelola Perbatasan
                        <small>Pos Lintas Batas Negara Wini</small>
                    </span>
                </a>
            </div>
            
            <nav>
                <?php if ($is_admin && $current_dir == 'admin'): ?>
                    <!-- Admin Navigation -->
                    <a href="/plbn_wini/admin/home.php" class="<?php echo $current_page == 'home.php' ? 'active' : ''; ?>">
                        <i class="fas fa-house"></i> Beranda
                    </a>
                    <a href="/plbn_wini/admin/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="/plbn_wini/admin/wisma.php" class="<?php echo $current_page == 'wisma.php' ? 'active' : ''; ?>">
                        <i class="fas fa-hotel"></i> Wisma
                    </a>
                    <a href="/plbn_wini/admin/gedung.php" class="<?php echo $current_page == 'gedung.php' ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i> Gedung
                    </a>
                    <a href="/plbn_wini/admin/kios.php" class="<?php echo $current_page == 'kios.php' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i> Kios
                    </a>
                    <a href="/plbn_wini/admin/reservations.php" class="<?php echo $current_page == 'reservations.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Reservasi
                    </a>
                    <a href="/plbn_wini/admin/gallery.php" class="<?php echo $current_page == 'gallery.php' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i> Galeri
                    </a>
                    <a href="/plbn_wini/admin/logout.php" class="admin-logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <!-- Public Navigation - FIXED LINKS -->
                    <?php 
                    // Determine correct paths for public pages
                    if ($current_dir == 'pages') {
                        $page_path = '';
                        $home_path = '../index.php';
                    } else {
                        $page_path = 'pages/';
                        $home_path = 'index.php';
                    }
                    ?>
                    <a href="<?php echo $home_path; ?>" 
                       class="<?php echo ($current_page == 'index.php' && $current_dir != 'admin') ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Beranda
                    </a>
                    <a href="<?php echo $page_path; ?>wisma.php" 
                       class="<?php echo $current_page == 'wisma.php' ? 'active' : ''; ?>">
                        <i class="fas fa-hotel"></i> Wisma
                    </a>
                    <a href="<?php echo $page_path; ?>gedung.php" 
                       class="<?php echo $current_page == 'gedung.php' ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i> Gedung
                    </a>
                    <a href="<?php echo $page_path; ?>kios.php" 
                       class="<?php echo $current_page == 'kios.php' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i> Kios
                    </a>
                    <a href="<?php echo $page_path; ?>gallery.php" 
                       class="<?php echo $current_page == 'gallery.php' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i> Galeri
                    </a>
                    <?php if (!$is_admin): ?>
                        <a href="/plbn_wini/admin/login.php" class="nav-admin-btn">
                            <i class="fas fa-user-lock"></i> Admin
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
            
            <!-- Mobile Menu Toggle -->
            <div class="menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation -->
    <div id="mobileMenu">
        <?php if ($is_admin && $current_dir == 'admin'): ?>
            <a href="/plbn_wini/admin/home.php"><i class="fas fa-house"></i> Beranda</a>
            <a href="/plbn_wini/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="/plbn_wini/admin/wisma.php"><i class="fas fa-hotel"></i> Wisma</a>
            <a href="/plbn_wini/admin/gedung.php"><i class="fas fa-building"></i> Gedung</a>
            <a href="/plbn_wini/admin/kios.php"><i class="fas fa-store"></i> Kios</a>
            <a href="/plbn_wini/admin/reservations.php"><i class="fas fa-calendar-check"></i> Reservasi</a>
            <a href="/plbn_wini/admin/gallery.php"><i class="fas fa-images"></i> Galeri</a>
        <?php else: ?>
            <?php 
            if ($current_dir == 'pages') {
                $page_path = '';
                $home_path = '../index.php';
            } else {
                $page_path = 'pages/';
                $home_path = 'index.php';
            }
            ?>
            <a href="<?php echo $home_path; ?>"><i class="fas fa-home"></i> Beranda</a>
            <a href="<?php echo $page_path; ?>wisma.php"><i class="fas fa-hotel"></i> Wisma</a>
            <a href="<?php echo $page_path; ?>gedung.php"><i class="fas fa-building"></i> Gedung</a>
            <a href="<?php echo $page_path; ?>kios.php"><i class="fas fa-store"></i> Kios</a>
            <a href="<?php echo $page_path; ?>gallery.php"><i class="fas fa-images"></i> Galeri</a>
            <?php if (!$is_admin): ?>
                <a href="/plbn_wini/admin/login.php" class="mobile-admin-btn"><i class="fas fa-user-lock"></i> Admin</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        function toggleMobileMenu() {
            var menu = document.getElementById('mobileMenu');
            var toggle = document.querySelector('.menu-toggle');
            if (menu.style.display === 'none' || menu.style.display === '') {
                menu.style.display = 'block';
                toggle.innerHTML = '<i class="fas fa-times"></i>';
            } else {
                menu.style.display = 'none';
                toggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            var menu = document.getElementById('mobileMenu');
            var toggle = document.querySelector('.menu-toggle');
            var header = document.querySelector('header');
            
            if (menu && menu.style.display === 'block') {
                var isClickInside = menu.contains(event.target) || 
                                   toggle.contains(event.target) || 
                                   header.contains(event.target);
                if (!isClickInside) {
                    menu.style.display = 'none';
                    toggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            }
        });

        // Handle responsive
        function handleResponsive() {
            var toggle = document.querySelector('.menu-toggle');
            var nav = document.querySelector('header nav');
            var menu = document.getElementById('mobileMenu');
            
            if (window.innerWidth <= 768) {
                toggle.style.display = 'block';
                nav.style.display = 'none';
            } else {
                toggle.style.display = 'none';
                nav.style.display = 'flex';
                if (menu) {
                    menu.style.display = 'none';
                    toggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            }
        }

        window.addEventListener('load', handleResponsive);
        window.addEventListener('resize', handleResponsive);
    </script>

    <?php if (!$is_admin || $current_dir != 'admin'): ?>
    <!-- Hero Section for Public Pages -->
    <?php 
    $show_hero = true;
    if ($current_dir == 'admin') {
        $show_hero = false;
    } elseif ($current_page == 'index.php' && $current_dir != 'admin') {
        $show_hero = false;
    }
    
    if ($show_hero): 
    ?>
    <section class="page-hero">
        <div class="hero-content">
            <h1>
                <?php 
                if ($current_page == 'wisma.php') echo '<i class="fas fa-hotel"></i> Wisma Indonesia';
                elseif ($current_page == 'gedung.php') echo '<i class="fas fa-building"></i> Gedung Serbaguna';
                elseif ($current_page == 'kios.php') echo '<i class="fas fa-store"></i> Kios Pasar';
                elseif ($current_page == 'gallery.php') echo '<i class="fas fa-images"></i> Galeri PLBN Wini';
                else echo 'PLBN Wini';
                ?>
            </h1>
            <p>
                <?php 
                if ($current_page == 'wisma.php') echo 'Penginapan nyaman dan strategis di kawasan PLBN Wini';
                elseif ($current_page == 'gedung.php') echo 'Fasilitas lengkap untuk acara dan pertemuan';
                elseif ($current_page == 'kios.php') echo 'Kios Pasar merupakan fasilitas ruang usaha di kawasan Pos Lintas Batas Negara (PLBN) Wini yang disewakan bagi pelaku UMKM untuk mendukung aktivitas ekonomi masyarakat perbatasan.';
                elseif ($current_page == 'gallery.php') echo 'Momen-momen terbaik di PLBN Wini';
                else echo 'Selamat datang di website resmi PLBN Wini';
                ?>
            </p>
        </div>
    </section>
    <?php endif; ?>
    <?php endif; ?>
    
    <!-- Main Content Wrapper -->
    <main>
