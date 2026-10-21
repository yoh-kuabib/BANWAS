<?php
session_start();

// Check login - support both user dan admin login
if (!isset($_SESSION['user_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Ensure tables exist
function ensureGedungTablesExist($pdo) {
    try {
        $pdo->query("SELECT 1 FROM gedung LIMIT 1");
        $pdo->query("SELECT 1 FROM gedung_facilities LIMIT 1");
    } catch (PDOException $e) {
        // Create gedung table
        $pdo->exec("CREATE TABLE IF NOT EXISTS gedung (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Create gedung_facilities table
        $pdo->exec("CREATE TABLE IF NOT EXISTS gedung_facilities (
            id INT PRIMARY KEY AUTO_INCREMENT,
            gedung_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            image VARCHAR(255),
            capacity INT,
            status ENUM('baik', 'rusak') DEFAULT 'baik',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (gedung_id) REFERENCES gedung(id) ON DELETE CASCADE
        )");
        
        // Insert default gedung if not exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM gedung");
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO gedung (name, description) VALUES ('Gedung Serbaguna', 'Gedung serbaguna dengan berbagai fasilitas yang dapat disewa')");
        }
    }

    try {
        $columns = $pdo->query("SHOW COLUMNS FROM gedung_facilities LIKE 'image'")->fetch();
        if (!$columns) {
            $pdo->exec("ALTER TABLE gedung_facilities ADD image VARCHAR(255) NULL AFTER description");
        }

        $price_column = $pdo->query("SHOW COLUMNS FROM gedung_facilities LIKE 'price'")->fetch();
        if ($price_column) {
            $pdo->exec("ALTER TABLE gedung_facilities DROP COLUMN price");
        }

        $pdo->exec("ALTER TABLE gedung_facilities MODIFY status VARCHAR(20) NOT NULL DEFAULT 'baik'");
        $pdo->exec("UPDATE gedung_facilities SET status = CASE WHEN status = 'maintenance' THEN 'rusak' ELSE 'baik' END");
        $pdo->exec("ALTER TABLE gedung_facilities MODIFY status ENUM('baik', 'rusak') DEFAULT 'baik'");

        $gedung_price_column = $pdo->query("SHOW COLUMNS FROM gedung LIKE 'price'")->fetch();
        if (!$gedung_price_column) {
            $pdo->exec("ALTER TABLE gedung ADD price DECIMAL(12,2) NULL AFTER description");
        }
    } catch (PDOException $e) {
        // Keep the page usable when the database user cannot alter the table.
    }
}

ensureGedungTablesExist($pdo);

// Get the single gedung record
$gedung = $pdo->query("SELECT * FROM gedung ORDER BY id LIMIT 1")->fetch();
$gedung_id = $gedung['id'];

// Handle Update Gedung Info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_gedung') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'] !== '' ? max(0, (float) $_POST['price']) : null;
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            $target_dir = '../uploads/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $image = time() . '_' . uniqid() . '.' . $file_ext;
            $target_file = $target_dir . $image;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // Delete old image if exists
                if ($gedung['image']) {
                    $old_file = $target_dir . $gedung['image'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                $stmt = $pdo->prepare("UPDATE gedung SET name = ?, description = ?, price = ?, image = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $image, $gedung_id]);
            } else {
                $_SESSION['message'] = 'Gagal upload gambar!';
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = 'Format file tidak didukung!';
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $stmt = $pdo->prepare("UPDATE gedung SET name = ?, description = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $gedung_id]);
    }
    
    if (!isset($_SESSION['message'])) {
        $_SESSION['message'] = 'Gedung berhasil diperbarui!';
        $_SESSION['message_type'] = 'success';
    }
    
    header('Location: gedung.php');
    exit();
}

// Handle Delete Facility
if (isset($_GET['delete_facility']) && is_numeric($_GET['delete_facility'])) {
    try {
        $stmt = $pdo->prepare("SELECT image FROM gedung_facilities WHERE id = ? AND gedung_id = ?");
        $stmt->execute([$_GET['delete_facility'], $gedung_id]);
        $facility = $stmt->fetch();

        if ($facility && $facility['image']) {
            $image_path = '../uploads/' . $facility['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM gedung_facilities WHERE id = ? AND gedung_id = ?");
        $stmt->execute([$_GET['delete_facility'], $gedung_id]);
        $_SESSION['message'] = 'Fasilitas berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Gagal menghapus fasilitas!';
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: gedung.php');
    exit();
}

// Handle Add/Edit Facility
$edit_facility = null;
if (isset($_GET['edit_facility']) && is_numeric($_GET['edit_facility'])) {
    $stmt = $pdo->prepare("SELECT * FROM gedung_facilities WHERE id = ? AND gedung_id = ?");
    $stmt->execute([$_GET['edit_facility'], $gedung_id]);
    $edit_facility = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manage_facility') {
    $facility_name = trim($_POST['facility_name']);
    $facility_description = trim($_POST['facility_description']);
    $capacity = isset($_POST['capacity']) && $_POST['capacity'] !== ''
        ? max(1, (int) $_POST['capacity'])
        : 1;
    $status = $_POST['status'];
    $facility_id = isset($_POST['facility_id']) ? $_POST['facility_id'] : null;
    $image = '';
    $facility_upload_error = false;

    if (isset($_FILES['facility_image']) && $_FILES['facility_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['facility_image']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed, true)) {
            $target_dir = '../uploads/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $image = time() . '_' . uniqid() . '.' . $file_ext;
            if (!move_uploaded_file($_FILES['facility_image']['tmp_name'], $target_dir . $image)) {
                $image = '';
                $facility_upload_error = true;
                $_SESSION['message'] = 'Gagal upload gambar fasilitas!';
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $facility_upload_error = true;
            $_SESSION['message'] = 'Format gambar fasilitas tidak didukung!';
            $_SESSION['message_type'] = 'danger';
        }
    }

    if ($facility_upload_error) {
        header('Location: gedung.php');
        exit();
    }
    
    try {
        if ($facility_id) {
            if ($image) {
                $old_stmt = $pdo->prepare("SELECT image FROM gedung_facilities WHERE id = ? AND gedung_id = ?");
                $old_stmt->execute([$facility_id, $gedung_id]);
                $old = $old_stmt->fetch();
                if ($old && $old['image'] && file_exists('../uploads/' . $old['image'])) {
                    unlink('../uploads/' . $old['image']);
                }

                $stmt = $pdo->prepare("UPDATE gedung_facilities SET name = ?, description = ?, capacity = ?, image = ?, status = ? WHERE id = ? AND gedung_id = ?");
                $stmt->execute([$facility_name, $facility_description, $capacity, $image, $status, $facility_id, $gedung_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE gedung_facilities SET name = ?, description = ?, capacity = ?, status = ? WHERE id = ? AND gedung_id = ?");
                $stmt->execute([$facility_name, $facility_description, $capacity, $status, $facility_id, $gedung_id]);
            }
            $_SESSION['message'] = 'Fasilitas berhasil diperbarui!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO gedung_facilities (gedung_id, name, description, capacity, image, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$gedung_id, $facility_name, $facility_description, $capacity, $image, $status]);
            $_SESSION['message'] = 'Fasilitas berhasil ditambahkan!';
        }
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: gedung.php');
    exit();
}

// Get all facilities
$facilities = $pdo->query("SELECT * FROM gedung_facilities WHERE gedung_id = $gedung_id ORDER BY id DESC")->fetchAll();

// Reload gedung data
$gedung = $pdo->query("SELECT * FROM gedung WHERE id = $gedung_id")->fetch();

include '../includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-building"></i> Kelola Gedung Serbaguna</h1>
    </div>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <i class="fas fa-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
            <button class="close-alert" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php endif; ?>
    
    <!-- Gedung Info Section -->
    <div class="form-card">
        <h3><i class="fas fa-info-circle"></i> Informasi Gedung</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_gedung">
            
            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label><i class="fas fa-tag"></i> Nama Gedung <span class="required">*</span></label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($gedung['name']); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Deskripsi <span class="required">*</span></label>
                <textarea name="description" rows="4" required><?php echo htmlspecialchars($gedung['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label><i class="fas fa-money-bill-wave"></i> Tarif Sewa per Hari (Rp)</label>
                <input type="number" name="price" min="0" step="1000" value="<?php echo htmlspecialchars($gedung['price'] ?? ''); ?>" placeholder="Contoh: 1500000">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-image"></i> Gambar</label>
                <input type="file" name="image" accept="image/*">
                <?php if ($gedung['image']): ?>
                    <div class="current-image">
                        <img src="../uploads/<?php echo $gedung['image']; ?>" alt="Gedung" style="max-width: 150px; max-height: 150px; margin-top: 10px; border-radius: 5px;">
                        <p style="font-size: 0.8rem; color: #666;">Gambar saat ini</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Update Gedung
                </button>
            </div>
        </form>
    </div>
    
    <!-- Facilities Section -->
    <div style="margin-top: 30px;">
        <div class="page-header">
            <h2><i class="fas fa-door-open"></i> Fasilitas Gedung (<?php echo count($facilities); ?>)</h2>
            <button class="btn btn-primary" onclick="toggleFacilityForm()">
                <i class="fas fa-plus"></i> Tambah Fasilitas
            </button>
        </div>
        
        <!-- Facility Form -->
        <div id="facilityFormContainer" style="display: <?php echo isset($_GET['edit_facility']) ? 'block' : 'none'; ?>; margin-bottom: 20px;">
            <div class="form-card">
                <h3><?php echo $edit_facility ? 'Edit Fasilitas' : 'Tambah Fasilitas Baru'; ?></h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="manage_facility">
                    <?php if ($edit_facility): ?>
                        <input type="hidden" name="facility_id" value="<?php echo $edit_facility['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Nama Fasilitas <span class="required">*</span></label>
                            <input type="text" name="facility_name" value="<?php echo $edit_facility ? htmlspecialchars($edit_facility['name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Jumlah Unit</label>
                            <input type="number" name="capacity" value="<?php echo $edit_facility && $edit_facility['capacity'] ? (int) $edit_facility['capacity'] : 1; ?>" min="1" step="1" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-circle"></i> Status <span class="required">*</span></label>
                            <select name="status" required>
                                <option value="baik" <?php echo (!$edit_facility || $edit_facility['status'] == 'baik') ? 'selected' : ''; ?>>Baik</option>
                                <option value="rusak" <?php echo ($edit_facility && $edit_facility['status'] == 'rusak') ? 'selected' : ''; ?>>Rusak</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-image"></i> Gambar Fasilitas</label>
                            <input type="file" name="facility_image" accept="image/*">
                            <?php if ($edit_facility && !empty($edit_facility['image'])): ?>
                                <div class="facility-current-image">
                                    <img src="../uploads/<?php echo htmlspecialchars($edit_facility['image']); ?>" alt="Gambar fasilitas">
                                    <small>Gambar saat ini</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Deskripsi</label>
                        <textarea name="facility_description" rows="3"><?php echo $edit_facility ? htmlspecialchars($edit_facility['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> <?php echo $edit_facility ? 'Update' : 'Simpan'; ?>
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="toggleFacilityForm()">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Facilities Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Gambar</th>
                            <th>Nama Fasilitas</th>
                            <th>Kapasitas</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($facilities) > 0): ?>
                            <?php foreach ($facilities as $index => $facility): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <?php if (!empty($facility['image'])): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($facility['image']); ?>" alt="<?php echo htmlspecialchars($facility['name']); ?>" class="facility-table-image">
                                    <?php else: ?>
                                        <span class="no-image">Tidak ada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($facility['name']); ?></strong>
                                    <?php if ($facility['description']): ?>
                                        <br><small style="color: #666;"><?php echo htmlspecialchars(substr($facility['description'], 0, 50)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $facility['capacity'] ? $facility['capacity'] . ' unit' : '-'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $facility['status']; ?>">
                                        <?php 
                                        $status_text = [
                                            'baik' => 'Baik',
                                            'rusak' => 'Rusak'
                                        ];
                                        echo $status_text[$facility['status']];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($facility['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit_facility=<?php echo $facility['id']; ?>" class="btn-action btn-edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete_facility=<?php echo $facility['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus fasilitas ini?')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Belum ada fasilitas. Klik "Tambah Fasilitas" untuk menambahkan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.admin-content {
    max-width: 1180px;
    margin: 0 auto;
    padding: 28px;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
}

.page-header h1,
.page-header h2 {
    margin: 0;
    color: #1a3a5c;
    line-height: 1.25;
}

.page-header h1 {
    font-size: 1.8rem;
}

.page-header h2 {
    font-size: 1.35rem;
}

.page-header h1 i,
.page-header h2 i,
.form-card h3 i {
    margin-right: 8px;
}

.form-card {
    background: #fff;
    padding: 26px;
    border: 1px solid #e5eaf0;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(26, 58, 92, 0.08);
    margin-bottom: 28px;
}

.form-card h3 {
    margin: 0 0 22px;
    color: #1a3a5c;
    font-size: 1.2rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.form-group {
    min-width: 0;
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    color: #243b53;
    font-size: 0.9rem;
    font-weight: 600;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 11px 13px;
    border: 1px solid #d5dce5;
    border-radius: 6px;
    background: #fbfcfd;
    color: #243b53;
    font-size: 0.95rem;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}

.form-group input[type="file"] {
    padding: 9px;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #2d6a8f;
    outline: none;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(45, 106, 143, 0.12);
}

.form-group textarea {
    min-height: 112px;
    resize: vertical;
}

.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 4px;
}

.current-image {
    margin-top: 12px;
}

.current-image img {
    display: block;
    width: 180px;
    height: 120px;
    object-fit: cover;
    border: 1px solid #d5dce5;
    border-radius: 8px;
}

.facility-current-image {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
}

.facility-current-image img,
.facility-table-image {
    display: block;
    object-fit: cover;
    border: 1px solid #d5dce5;
    border-radius: 6px;
}

.facility-current-image img {
    width: 90px;
    height: 60px;
}

.facility-current-image small {
    color: #66788a;
    font-size: 0.78rem;
}

.facility-table-image {
    width: 84px;
    height: 58px;
}

.no-image {
    color: #8a98a8;
    font-size: 0.8rem;
}

.current-image p {
    margin: 6px 0 0;
    color: #66788a;
    font-size: 0.8rem;
}

.status-baik {
    background: #d4edda;
    color: #155724;
}

.status-rusak {
    background: #f8d7da;
    color: #721c24;
}

@media (max-width: 768px) {
    .admin-content {
        padding: 20px 16px;
    }

    .form-card {
        padding: 20px 16px;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .page-header h1 {
        font-size: 1.45rem;
    }
}
</style>

<script>
    function toggleFacilityForm() {
        var container = document.getElementById('facilityFormContainer');
        if (container.style.display === 'none' || container.style.display === '') {
            container.style.display = 'block';
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            container.style.display = 'none';
        }
    }
</script>

<?php include '../includes/footer.php'; ?>
