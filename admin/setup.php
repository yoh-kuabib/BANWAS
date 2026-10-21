<?php
/**
 * Setup Admin Password - FIX
 * File: admin/setup.php
 */

require_once '../config/database.php';

echo "<h1>Setup Admin Password</h1>";
echo "<hr>";

try {
    // Cek data admin saat ini
    $stmt = $pdo->query("SELECT id, username, password FROM admin");
    $admins = $stmt->fetchAll();
    
    echo "<h3>Data Admin Saat Ini:</h3>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Username</th><th>Password (DB)</th><th>Type</th></tr>";
    
    foreach ($admins as $admin) {
        $type = 'Unknown';
        if (strpos($admin['password'], '$2y$') === 0) {
            $type = 'password_hash';
        } elseif (strlen($admin['password']) == 32 && ctype_xdigit($admin['password'])) {
            $type = 'MD5';
        } else {
            $type = 'Plain Text';
        }
        
        echo "<tr>";
        echo "<td>{$admin['id']}</td>";
        echo "<td>{$admin['username']}</td>";
        echo "<td><code>{$admin['password']}</code></td>";
        echo "<td><strong style='color: " . ($type == 'Plain Text' ? 'red' : 'green') . ";'>$type</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br>";
    echo "<h3>Perbaikan Password:</h3>";
    
    // Update semua admin dengan password_hash
    foreach ($admins as $admin) {
        // Cek apakah password plain text atau MD5
        $is_plain = !(strpos($admin['password'], '$2y$') === 0) && 
                    !(strlen($admin['password']) == 32 && ctype_xdigit($admin['password']));
        
        if ($is_plain) {
            echo "<p>🔴 Password untuk '{$admin['username']}' adalah PLAIN TEXT! Memperbaiki...</p>";
            
            // Update dengan password_hash
            $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $updateStmt->execute([$new_hash, $admin['id']]);
            
            echo "<p style='color: green;'>✅ Password untuk '{$admin['username']}' telah diupdate ke password_hash</p>";
        } else {
            echo "<p style='color: green;'>✅ Password untuk '{$admin['username']}' sudah aman</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Hasil Setelah Perbaikan:</h3>";
    
    // Tampilkan data setelah update
    $stmt = $pdo->query("SELECT id, username, password FROM admin");
    $updated = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Username</th><th>Password (Hash)</th><th>Type</th></tr>";
    foreach ($updated as $admin) {
        $type = strpos($admin['password'], '$2y$') === 0 ? 'password_hash' : 'MD5';
        echo "<tr>";
        echo "<td>{$admin['id']}</td>";
        echo "<td>{$admin['username']}</td>";
        echo "<td><code>" . substr($admin['password'], 0, 30) . "...</code></td>";
        echo "<td><strong style='color: green;'>$type</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br>";
    echo "<div style='padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<strong style='color: #155724;'>✅ Setup selesai! Sekarang login dengan:</strong><br>";
    echo "<strong>Username:</strong> admin<br>";
    echo "<strong>Password:</strong> admin123";
    echo "</div>";
    
    echo "<br>";
    echo "<a href='login.php' style='display: inline-block; padding: 10px 20px; background: #1a3a5c; color: white; text-decoration: none; border-radius: 5px;'>";
    echo "← Kembali ke Login";
    echo "</a>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>