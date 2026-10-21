<?php
/**
 * Database Setup & Initialize Tables
 * File: admin/setup_db.php
 */

require_once '../config/database.php';

echo "<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #1a3a5c 0%, #2d6a8f 100%);
    padding: 40px 20px;
    margin: 0;
    min-height: 100vh;
}
.container {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}
h1 {
    color: #1a3a5c;
    border-bottom: 3px solid #2d6a8f;
    padding-bottom: 15px;
}
h3 {
    color: #333;
    margin-top: 25px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
}
table th {
    background: #1a3a5c;
    color: white;
    padding: 12px;
    text-align: left;
}
table td {
    border-bottom: 1px solid #ddd;
    padding: 12px;
}
table tr:hover {
    background: #f5f5f5;
}
.alert {
    padding: 15px;
    border-radius: 5px;
    margin: 15px 0;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}
code {
    background: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
}
.btn {
    display: inline-block;
    padding: 10px 20px;
    background: #1a3a5c;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 15px;
}
.btn:hover {
    background: #2d6a8f;
}
</style>";

echo "<div class='container'>";
echo "<h1>Database Setup & Initialization</h1>";
echo "<hr>";

try {
    // ===== CREATE USERS TABLE =====
    echo "<h3>1. Creating/Checking Users Table</h3>";
    
    try {
        $pdo->query("SELECT 1 FROM users LIMIT 1");
        echo "<div class='alert alert-info'>✓ Tabel 'users' sudah ada</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-warning'>⚠ Tabel 'users' tidak ada. Membuat tabel...</div>";
        
        $sql = "CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100),
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "<div class='alert alert-success'>✅ Tabel 'users' berhasil dibuat</div>";
    }
    
    // ===== CREATE ADMIN TABLE =====
    echo "<h3>2. Creating/Checking Admin Table</h3>";
    
    try {
        $pdo->query("SELECT 1 FROM admin LIMIT 1");
        echo "<div class='alert alert-info'>✓ Tabel 'admin' sudah ada</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-warning'>⚠ Tabel 'admin' tidak ada. Membuat tabel...</div>";
        
        $sql = "CREATE TABLE admin (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100),
            email VARCHAR(100),
            role VARCHAR(50) DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "<div class='alert alert-success'>✅ Tabel 'admin' berhasil dibuat</div>";
    }
    
    // ===== CREATE OTHER TABLES =====
    echo "<h3>3. Creating/Checking Other Tables</h3>";
    
    // Tabel wisma
    try {
        $pdo->query("SELECT 1 FROM wisma LIMIT 1");
        echo "<div class='alert alert-info'>✓ Tabel 'wisma' sudah ada</div>";
    } catch (PDOException $e) {
        $sql = "CREATE TABLE wisma (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            dining_room BOOLEAN DEFAULT 1,
            lobby BOOLEAN DEFAULT 1,
            parking_2wheel INT DEFAULT 0,
            parking_4wheel INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "<div class='alert alert-success'>✅ Tabel 'wisma' berhasil dibuat</div>";
    }
    
    // Tabel rooms
    try {
        $pdo->query("SELECT 1 FROM rooms LIMIT 1");
        echo "<div class='alert alert-info'>✓ Tabel 'rooms' sudah ada</div>";
    } catch (PDOException $e) {
        $sql = "CREATE TABLE rooms (
            id INT PRIMARY KEY AUTO_INCREMENT,
            wisma_id INT NOT NULL,
            room_number VARCHAR(50) NOT NULL,
            room_type VARCHAR(50),
            capacity INT DEFAULT 1,
            price DECIMAL(10,2),
            facilities TEXT,
            status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (wisma_id) REFERENCES wisma(id)
        )";
        $pdo->exec($sql);
        echo "<div class='alert alert-success'>✅ Tabel 'rooms' berhasil dibuat</div>";
    }
    
    // Tabel kios
    try {
        $pdo->query("SELECT 1 FROM kios LIMIT 1");
        echo "<div class='alert alert-info'>✓ Tabel 'kios' sudah ada</div>";
    } catch (PDOException $e) {
        $sql = "CREATE TABLE kios (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10,2),
            location VARCHAR(100),
            status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "<div class='alert alert-success'>✅ Tabel 'kios' berhasil dibuat</div>";
    }
    
    // Tabel gedung
    try {
        $pdo->query("SELECT 1 FROM gedung LIMIT 1");
        echo "<div class='alert alert-info'>✓ Tabel 'gedung' sudah ada</div>";
    } catch (PDOException $e) {
        $sql = "CREATE TABLE gedung (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(12,2) NULL,
            capacity INT,
            price DECIMAL(10,2),
            facilities TEXT,
            status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "<div class='alert alert-success'>✅ Tabel 'gedung' berhasil dibuat</div>";
    }
    
    // Tabel gallery
    try {
        $pdo->query("SELECT 1 FROM gallery LIMIT 1");
        echo "<div class='alert alert-info'>✓ Tabel 'gallery' sudah ada</div>";
    } catch (PDOException $e) {
        $sql = "CREATE TABLE gallery (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            image_url VARCHAR(255),
            category VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "<div class='alert alert-success'>✅ Tabel 'gallery' berhasil dibuat</div>";
    }
    
    // ===== USER DATA =====
    echo "<h3>4. Registered Users</h3>";
    
    $stmt = $pdo->query("SELECT id, username, email, full_name, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Created At</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td><strong>{$user['username']}</strong></td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='alert alert-info'>Belum ada pengguna yang terdaftar. <a href='register.php'>Daftar sekarang</a></div>";
    }
    
    // ===== FINAL INFO =====
    echo "<h3>5. Next Steps</h3>";
    echo "<div class='alert alert-success'>";
    echo "<strong>✅ Database setup selesai!</strong><br>";
    echo "• <a href='register.php'>Daftar akun baru</a><br>";
    echo "• <a href='login.php'>Login ke admin panel</a><br>";
    echo "</div>";
    
    echo "<a href='../index.php' class='btn'>← Kembali ke Home</a>";
    
} catch (PDOException $e) {
    echo "<div class='alert' style='background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "</div>";
?>
