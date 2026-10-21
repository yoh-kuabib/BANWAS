<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Get wisma data
$wisma = $pdo->query("SELECT * FROM wisma LIMIT 1")->fetch();

// Handle Add/Edit Room
$edit_room = null;
if (isset($_GET['edit_room']) && is_numeric($_GET['edit_room'])) {
    $stmt = $pdo->prepare("SELECT * FROM wisma_rooms WHERE id = ?");
    $stmt->execute([$_GET['edit_room']]);
    $edit_room = $stmt->fetch();
}

// Handle Delete Room
if (isset($_GET['delete_room']) && is_numeric($_GET['delete_room'])) {
    $room_id = $_GET['delete_room'];
    try {
        // Get image
        $stmt = $pdo->prepare("SELECT image FROM wisma_rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch();
        
        if ($room && $room['image']) {
            $image_path = '../uploads/' . $room['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM wisma_rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        
        $_SESSION['message'] = 'Kamar berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Gagal menghapus kamar!';
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: wisma.php');
    exit();
}

// Handle Wisma Update
if (isset($_POST['update_wisma'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $lobby = trim($_POST['lobby']);
    $dining_room = trim($_POST['dining_room']);
    $parking_2wheel = intval($_POST['parking_2wheel']);
    $parking_4wheel = intval($_POST['parking_4wheel']);
    
    try {
        $stmt = $pdo->prepare("UPDATE wisma SET name = ?, description = ?, lobby = ?, dining_room = ?, parking_2wheel = ?, parking_4wheel = ? WHERE id = ?");
        $stmt->execute([$name, $description, $lobby, $dining_room, $parking_2wheel, $parking_4wheel, $wisma['id']]);
        
        $_SESSION['message'] = 'Data Wisma berhasil diperbarui!';
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Gagal memperbarui wisma: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: wisma.php');
    exit();
}

// Handle Room Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_number'])) {
    $room_number = trim($_POST['room_number']);
    $room_name = trim($_POST['room_name']);
    $description = trim($_POST['description']);
    $capacity = intval($_POST['capacity']);
    $price = floatval($_POST['price']);
    $facilities = trim($_POST['facilities']);
    $status = $_POST['status'];
    $edit_id = isset($_POST['edit_id']) ? $_POST['edit_id'] : null;
    
    // Handle image
    $image = '';
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed)) {
            $_SESSION['message'] = 'Format file tidak didukung!';
            $_SESSION['message_type'] = 'danger';
        } else {
            $target_dir = '../uploads/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $image = time() . '_' . uniqid() . '.' . $file_ext;
            $target_file = $target_dir . $image;
            
            if (move_uploaded_file($_FILES['room_image']['tmp_name'], $target_file)) {
                // Delete old image if editing
                if ($edit_id) {
                    $stmt = $pdo->prepare("SELECT image FROM wisma_rooms WHERE id = ?");
                    $stmt->execute([$edit_id]);
                    $old = $stmt->fetch();
                    if ($old && $old['image']) {
                        $old_file = $target_dir . $old['image'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                }
            } else {
                $image = '';
                $_SESSION['message'] = 'Gagal mengupload gambar!';
                $_SESSION['message_type'] = 'danger';
            }
        }
    }
    
    try {
        if ($edit_id) {
            // Update
            if ($image) {
                $stmt = $pdo->prepare("UPDATE wisma_rooms SET room_name = ?, description = ?, capacity = ?, price = ?, facilities = ?, image = ?, status = ? WHERE id = ?");
                $stmt->execute([$room_name, $description, $capacity, $price, $facilities, $image, $status, $edit_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE wisma_rooms SET room_name = ?, description = ?, capacity = ?, price = ?, facilities = ?, status = ? WHERE id = ?");
                $stmt->execute([$room_name, $description, $capacity, $price, $facilities, $status, $edit_id]);
            }
            $_SESSION['message'] = 'Kamar berhasil diperbarui!';
            $_SESSION['message_type'] = 'success';
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO wisma_rooms (wisma_id, room_number, room_name, description, capacity, price, facilities, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$wisma['id'], $room_number, $room_name, $description, $capacity, $price, $facilities, $image, $status]);
            $_SESSION['message'] = 'Kamar berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: wisma.php');
    exit();
}

// Get all rooms
$rooms = $pdo->query("SELECT * FROM wisma_rooms WHERE wisma_id = " . $wisma['id'] . " ORDER BY room_number")->fetchAll();

include '../includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-hotel"></i> Kelola Wisma & Kamar</h1>
    </div>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible">
            <i class="fas fa-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
            <button class="close-alert" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Wisma Info Section -->
    <div class="form-card">
        <h2><i class="fas fa-building"></i> Informasi Wisma Utama</h2>
        <form method="POST" action="">
            <input type="hidden" name="update_wisma" value="1">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Wisma</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($wisma['name']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3"><?php echo htmlspecialchars($wisma['description']); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Deskripsi Lobi</label>
                    <input type="text" name="lobby" value="<?php echo htmlspecialchars($wisma['lobby']); ?>" placeholder="Contoh: Lobi yang luas dengan AC">
                </div>
                <div class="form-group">
                    <label>Deskripsi Ruang Makan</label>
                    <input type="text" name="dining_room" value="<?php echo htmlspecialchars($wisma['dining_room']); ?>" placeholder="Contoh: Ruang makan dengan kapasitas 20 orang">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Parkiran Motor (unit)</label>
                    <input type="number" name="parking_2wheel" value="<?php echo $wisma['parking_2wheel']; ?>" min="0">
                </div>
                <div class="form-group">
                    <label>Parkiran Mobil (unit)</label>
                    <input type="number" name="parking_4wheel" value="<?php echo $wisma['parking_4wheel']; ?>" min="0">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <!-- Rooms Management Section -->
    <div class="form-card" style="margin-top: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2><i class="fas fa-door-open"></i> Kelola Kamar (<?php echo count($rooms); ?>/4)</h2>
            <?php if (count($rooms) < 4): ?>
                <button class="btn btn-primary" onclick="toggleRoomForm()">
                    <i class="fas fa-plus"></i> Tambah Kamar
                </button>
            <?php endif; ?>
        </div>

        <!-- Room Form -->
        <?php if (count($rooms) < 4 || $edit_room): ?>
        <div id="roomFormContainer" style="background: #f5f7fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: <?php echo ($edit_room || count($rooms) < 4) ? 'block' : 'none'; ?>;">
            <h3><?php echo $edit_room ? 'Edit Kamar' : 'Tambah Kamar Baru'; ?></h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <?php if ($edit_room): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_room['id']; ?>">
                <?php endif; ?>

                <div class="form-row">
                    <?php if (!$edit_room): ?>
                    <div class="form-group">
                        <label>Nomor Kamar *</label>
                        <input type="text" name="room_number" value="<?php echo $edit_room['room_number'] ?? ''; ?>" placeholder="Contoh: 101" required>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Nama Kamar</label>
                        <input type="text" name="room_name" value="<?php echo $edit_room['room_name'] ?? ''; ?>" placeholder="Contoh: Kamar Suite" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Deskripsi Kamar</label>
                        <textarea name="description" rows="3" placeholder="Deskripsi lengkap kamar"><?php echo $edit_room['description'] ?? ''; ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Kapasitas Tamu</label>
                        <input type="number" name="capacity" value="<?php echo $edit_room['capacity'] ?? 2; ?>" min="1" max="4">
                    </div>
                    <div class="form-group">
                        <label>Harga per Malam (Rp)</label>
                        <input type="number" name="price" value="<?php echo $edit_room['price'] ?? ''; ?>" min="0" step="10000" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fasilitas Kamar</label>
                        <textarea name="facilities" rows="3" placeholder="Pisahkan dengan koma (Contoh: AC, Kasur, WiFi, TV)"><?php echo $edit_room['facilities'] ?? ''; ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Gambar Kamar</label>
                        <input type="file" name="room_image" accept="image/*">
                        <?php if ($edit_room && $edit_room['image']): ?>
                            <small style="color: #666;">Gambar saat ini: <?php echo $edit_room['image']; ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="available" <?php echo ($edit_room['status'] ?? 'available') == 'available' ? 'selected' : ''; ?>>Tersedia</option>
                            <option value="booked" <?php echo ($edit_room['status'] ?? '') == 'booked' ? 'selected' : ''; ?>>Dipesan</option>
                            <option value="maintenance" <?php echo ($edit_room['status'] ?? '') == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Kamar</button>
                    <?php if (!$edit_room): ?>
                        <button type="button" class="btn btn-secondary" onclick="toggleRoomForm()"><i class="fas fa-times"></i> Batal</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Rooms List -->
        <div class="rooms-grid">
            <?php if (count($rooms) > 0): ?>
                <?php foreach ($rooms as $room): ?>
                    <div class="room-card">
                        <div class="room-header">
                            <h3>Kamar <?php echo htmlspecialchars($room['room_number']); ?> - <?php echo htmlspecialchars($room['room_name']); ?></h3>
                            <div class="room-status status-<?php echo $room['status']; ?>">
                                <?php 
                                $status_text = [
                                    'available' => 'Tersedia',
                                    'booked' => 'Dipesan',
                                    'maintenance' => 'Maintenance'
                                ];
                                echo $status_text[$room['status']] ?? $room['status'];
                                ?>
                            </div>
                        </div>

                        <?php if ($room['image'] && file_exists('../uploads/' . $room['image'])): ?>
                            <div class="room-image">
                                <img src="../uploads/<?php echo $room['image']; ?>" alt="Kamar <?php echo $room['room_number']; ?>">
                            </div>
                        <?php endif; ?>

                        <div class="room-body">
                            <p><strong>Deskripsi:</strong> <?php echo htmlspecialchars($room['description']); ?></p>
                            <p><strong>Kapasitas:</strong> <?php echo $room['capacity']; ?> tamu</p>
                            <p><strong>Harga:</strong> Rp <?php echo number_format($room['price'], 0, ',', '.'); ?>/malam</p>
                            
                            <?php if ($room['facilities']): ?>
                                <p><strong>Fasilitas:</strong></p>
                                <div class="facilities-list">
                                    <?php 
                                    $facilities = array_map('trim', explode(',', $room['facilities']));
                                    foreach ($facilities as $facility): 
                                    ?>
                                        <span class="facility-badge"><?php echo htmlspecialchars($facility); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="room-actions">
                            <a href="?edit_room=<?php echo $room['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Edit</a>
                            <a href="?delete_room=<?php echo $room['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus kamar ini?')"><i class="fas fa-trash"></i> Hapus</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align: center; color: #999;">Belum ada kamar. Silakan tambahkan kamar terlebih dahulu.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.room-card {
    background: white;
    border: 1px solid #e8ecf0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.room-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.room-header {
    background: #f8f9fa;
    padding: 15px;
    border-bottom: 1px solid #e8ecf0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.room-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.room-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-available {
    background: #d4edda;
    color: #155724;
}

.status-booked {
    background: #fff3cd;
    color: #856404;
}

.status-maintenance {
    background: #f8d7da;
    color: #721c24;
}

.room-image {
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: #f0f0f0;
}

.room-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.room-body {
    padding: 15px;
}

.room-body p {
    margin: 8px 0;
    font-size: 0.9rem;
}

.facilities-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}

.facility-badge {
    background: #e7f3ff;
    color: #0066cc;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
}

.room-actions {
    padding: 12px 15px;
    border-top: 1px solid #e8ecf0;
    display: flex;
    gap: 8px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.85rem;
}

.btn-info {
    background: #0066cc;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.btn-info:hover {
    background: #0052a3;
}

function toggleRoomForm() {
    const container = document.getElementById('roomFormContainer');
    container.style.display = container.style.display === 'none' ? 'block' : 'none';
}
</style>

<script>
function toggleRoomForm() {
    const container = document.getElementById('roomFormContainer');
    container.style.display = container.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php include '../includes/footer.php'; ?>
