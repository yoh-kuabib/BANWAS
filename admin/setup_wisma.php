<?php
/**
 * Database Migration untuk Wisma
 * Mengubah struktur dari banyak wisma menjadi 1 wisma dengan 4 kamar
 */

require_once '../config/database.php';

echo "<h1>Setup Wisma Database Structure</h1>";
echo "<hr>";

try {
    // 1. Rename old wisma table
    echo "<h3>1. Backup tabel wisma lama...</h3>";
    try {
        $pdo->exec("RENAME TABLE wisma TO wisma_old");
        echo "✅ Tabel wisma di-rename ke wisma_old<br>";
    } catch (PDOException $e) {
        echo "⚠️ Tabel wisma belum ada atau sudah di-rename<br>";
    }
    
    // 2. Create new wisma table (hanya 1 wisma building)
    echo "<h3>2. Membuat tabel wisma baru...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS wisma (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        
        -- Common Facilities
        lobby TEXT,
        dining_room TEXT,
        lobby_image VARCHAR(255) NULL,
        dining_room_image VARCHAR(255) NULL,
        parking_2wheel INT DEFAULT 0,
        parking_4wheel INT DEFAULT 0,
        parking_2wheel_image VARCHAR(255) NULL,
        parking_4wheel_image VARCHAR(255) NULL,
        
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✅ Tabel wisma berhasil dibuat<br>";
    
    // 3. Create wisma_rooms table
    echo "<h3>3. Membuat tabel wisma_rooms...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS wisma_rooms (
        id INT PRIMARY KEY AUTO_INCREMENT,
        wisma_id INT NOT NULL,
        room_number VARCHAR(10) NOT NULL,
        room_name VARCHAR(100),
        
        -- Room Details
        description TEXT,
        image VARCHAR(255),
        price DECIMAL(10, 2),
        capacity INT DEFAULT 2,
        
        -- Facilities
        facilities TEXT,
        
        -- Status
        status ENUM('available', 'booked', 'maintenance') DEFAULT 'available',
        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (wisma_id) REFERENCES wisma(id) ON DELETE CASCADE,
        UNIQUE KEY unique_room_number (wisma_id, room_number)
    )";
    
    $pdo->exec($sql);
    echo "✅ Tabel wisma_rooms berhasil dibuat<br>";
    
    // 4. Insert default wisma
    echo "<h3>4. Menambahkan data Wisma default...</h3>";
    $stmt = $pdo->prepare("INSERT INTO wisma (name, description, lobby, dining_room, parking_2wheel, parking_4wheel) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'Wisma PLBN Wini',
        'Wisma tamu dengan 4 kamar nyaman di kompleks PLBN Wini',
        'Tersedia lobi yang nyaman untuk istirahat',
        'Ruang makan dengan kapasitas 20 orang',
        10,
        5
    ]);
    $wisma_id = $pdo->lastInsertId();
    echo "✅ Wisma berhasil ditambahkan (ID: $wisma_id)<br>";
    
    // 5. Insert 4 rooms
    echo "<h3>5. Menambahkan 4 Kamar...</h3>";
    $rooms = [
        ['room_number' => '101', 'room_name' => 'Kamar Suite', 'capacity' => 2, 'price' => 500000],
        ['room_number' => '102', 'room_name' => 'Kamar Deluxe', 'capacity' => 2, 'price' => 400000],
        ['room_number' => '103', 'room_name' => 'Kamar Standard', 'capacity' => 2, 'price' => 300000],
        ['room_number' => '104', 'room_name' => 'Kamar Economy', 'capacity' => 1, 'price' => 200000],
    ];
    
    $facilities_text = "AC, Kasur Empuk, Kamar Mandi Pribadi, WiFi, TV, Lemari, Kursi, Meja Kerja";
    
    $stmt = $pdo->prepare("INSERT INTO wisma_rooms (wisma_id, room_number, room_name, description, capacity, price, facilities, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($rooms as $room) {
        $stmt->execute([
            $wisma_id,
            $room['room_number'],
            $room['room_name'],
            $room['room_name'] . ' dengan fasilitas lengkap',
            $room['capacity'],
            $room['price'],
            $facilities_text,
            'available'
        ]);
        echo "✅ Kamar " . $room['room_number'] . " (" . $room['room_name'] . ") ditambahkan<br>";
    }
    
    echo "<hr>";
    echo "<div style='padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-top: 20px;'>";
    echo "<strong style='color: #155724;'>✅ Setup Wisma selesai!</strong><br>";
    echo "Struktur database telah diubah:<br>";
    echo "- 1 Wisma building utama<br>";
    echo "- 4 Kamar di dalam wisma<br>";
    echo "- Fasilitas umum (Lobi, Ruang Makan, Parkiran)<br>";
    echo "- Fasilitas per kamar<br>";
    echo "</div>";
    
    echo "<br>";
    echo "<a href='wisma.php' style='display: inline-block; padding: 10px 20px; background: #1a3a5c; color: white; text-decoration: none; border-radius: 5px;'>";
    echo "← Kembali ke Manajemen Wisma";
    echo "</a>";
    
} catch (PDOException $e) {
    echo "<div style='padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;'>";
    echo "<strong>❌ Error: " . htmlspecialchars($e->getMessage()) . "</strong>";
    echo "</div>";
}
?>
