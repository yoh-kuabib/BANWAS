<?php
session_start();
if ((!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)
    && (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true)) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Handle reservation cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_reservation') {
    $id = filter_input(INPUT_POST, 'kios_id', FILTER_VALIDATE_INT);

    if ($id) {
        try {
            $stmt = $pdo->prepare("UPDATE kios SET status = 'available' WHERE id = ? AND status IN ('rented', 'occupied')");
            $stmt->execute([$id]);
            $_SESSION['message'] = $stmt->rowCount() > 0
                ? 'Reservasi kios berhasil dibatalkan!'
                : 'Kios tidak sedang disewa atau sudah diperbarui.';
            $_SESSION['message_type'] = $stmt->rowCount() > 0 ? 'success' : 'danger';
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Gagal membatalkan reservasi kios!';
            $_SESSION['message_type'] = 'danger';
        }
    }

    header('Location: kios.php');
    exit();
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("SELECT image FROM kios WHERE id = ?");
        $stmt->execute([$id]);
        $kios = $stmt->fetch();
        
        if ($kios && $kios['image']) {
            $image_path = '../uploads/' . $kios['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM kios WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['message'] = 'Kios berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Gagal menghapus kios!';
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: kios.php');
    exit();
}

// Handle Add/Edit
$edit_data = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM kios WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = !empty($_POST['price']) ? $_POST['price'] : null;
    $size = trim($_POST['size']);
    $status = $_POST['status'];
    $edit_id = isset($_POST['edit_id']) ? $_POST['edit_id'] : null;
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
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
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                if ($edit_id) {
                    $stmt = $pdo->prepare("SELECT image FROM kios WHERE id = ?");
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
            }
        }
    }
    
    try {
        if ($edit_id) {
            if ($image) {
                $stmt = $pdo->prepare("UPDATE kios SET name = ?, description = ?, price = ?, size = ?, image = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $size, $image, $status, $edit_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE kios SET name = ?, description = ?, price = ?, size = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $size, $status, $edit_id]);
            }
            $_SESSION['message'] = 'Kios berhasil diperbarui!';
            $_SESSION['message_type'] = 'success';
        } else {
            $stmt = $pdo->prepare("INSERT INTO kios (name, description, price, size, image, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $size, $image, $status]);
            $_SESSION['message'] = 'Kios berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: kios.php');
    exit();
}

$kios = $pdo->query("SELECT * FROM kios ORDER BY id DESC")->fetchAll();

include '../includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-store"></i> Kelola Kios Pasar</h1>
        <button class="btn btn-primary" onclick="toggleForm()">
            <i class="fas fa-plus"></i> Tambah Kios
        </button>
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
    
    <!-- Form -->
    <div id="formContainer" style="display: <?php echo $edit_data ? 'block' : 'none'; ?>;">
        <div class="form-card">
            <h3><?php echo $edit_data ? 'Edit Kios' : 'Tambah Kios Baru'; ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Nama Kios <span class="required">*</span></label>
                        <input type="text" name="name" value="<?php echo $edit_data ? htmlspecialchars($edit_data['name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-arrows-alt"></i> Ukuran</label>
                        <input type="text" name="size" value="<?php echo $edit_data ? htmlspecialchars($edit_data['size']) : ''; ?>" placeholder="Contoh: 3x4 meter">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-money-bill"></i> Harga Sewa (Rp)</label>
                        <input type="number" name="price" step="0.01" value="<?php echo $edit_data ? $edit_data['price'] : ''; ?>" placeholder="Contoh: 500000">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-circle"></i> Status <span class="required">*</span></label>
                        <select name="status" required>
                            <option value="available" <?php echo ($edit_data && $edit_data['status'] == 'available') ? 'selected' : ''; ?>>Tersedia</option>
                            <option value="rented" <?php echo ($edit_data && $edit_data['status'] == 'rented') ? 'selected' : ''; ?>>Disewa</option>
                            <option value="occupied" <?php echo ($edit_data && $edit_data['status'] == 'occupied') ? 'selected' : ''; ?>>Disewa (data lama)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Deskripsi <span class="required">*</span></label>
                    <textarea name="description" rows="4" required><?php echo $edit_data ? htmlspecialchars($edit_data['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-image"></i> Gambar</label>
                    <input type="file" name="image" accept="image/*" <?php echo $edit_data ? '' : 'required'; ?>>
                    <?php if ($edit_data && $edit_data['image']): ?>
                        <div class="current-image">
                            <img src="../uploads/<?php echo $edit_data['image']; ?>" alt="Current image" style="max-width: 100px; max-height: 100px; margin-top: 10px;">
                            <p style="font-size: 0.8rem; color: #666;">Gambar saat ini</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php echo $edit_data ? 'Update' : 'Simpan'; ?>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Table -->
    <div class="table-container">
        <div class="table-toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari kios..." onkeyup="searchTable()">
            </div>
            <div class="table-info">
                Menampilkan <strong><?php echo count($kios); ?></strong> data
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="admin-table" id="dataTable">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Gambar</th>
                        <th>Nama</th>
                        <th>Ukuran</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($kios) > 0): ?>
                        <?php foreach ($kios as $index => $item): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php if ($item['image']): ?>
                                    <img src="../uploads/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="table-image">
                                <?php else: ?>
                                    <span class="no-image">Tidak ada</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td><?php echo $item['size'] ? htmlspecialchars($item['size']) : '-'; ?></td>
                            <td><?php echo $item['price'] ? 'Rp ' . number_format($item['price'], 0, ',', '.') : '-'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo in_array($item['status'], ['rented', 'occupied'], true) ? 'rented' : $item['status']; ?>">
                                    <?php echo in_array($item['status'], ['rented', 'occupied'], true) ? 'Disewa' : 'Tersedia'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?edit=<?php echo $item['id']; ?>" class="btn-action btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if (in_array($item['status'], ['rented', 'occupied'], true)): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Batalkan reservasi kios ini?');">
                                            <input type="hidden" name="action" value="cancel_reservation">
                                            <input type="hidden" name="kios_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn-action btn-cancel" title="Batalkan Reservasi">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $item['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus kios ini?')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Belum ada data kios. Klik "Tambah Kios" untuk menambahkan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

.page-header h1 {
    margin: 0;
    color: #1a3a5c;
    font-size: 1.8rem;
    line-height: 1.25;
}

.page-header h1 i,
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
}

.form-group input[type="file"] {
    padding: 9px;
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

.current-image p {
    margin: 6px 0 0;
    color: #66788a;
    font-size: 0.8rem;
}

.table-container {
    overflow: hidden;
    background: #fff;
    border: 1px solid #e5eaf0;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(26, 58, 92, 0.08);
}

.table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e5eaf0;
}

.search-box {
    display: flex;
    align-items: center;
    gap: 8px;
    width: min(320px, 100%);
    padding: 8px 11px;
    background: #fff;
    border: 1px solid #d5dce5;
    border-radius: 6px;
}

.search-box i {
    color: #718096;
}

.search-box input {
    width: 100%;
    border: 0;
    outline: 0;
    color: #243b53;
}

.table-info {
    color: #66788a;
    font-size: 0.9rem;
}

.table-responsive {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    min-width: 760px;
    margin: 0;
    table-layout: fixed;
}

.admin-table th,
.admin-table td {
    padding: 12px 10px;
    vertical-align: middle;
}

.admin-table th:nth-child(1) { width: 5%; }
.admin-table th:nth-child(2) { width: 25%; }
.admin-table th:nth-child(3) { width: 15%; }
.admin-table th:nth-child(4) { width: 10%; }
.admin-table th:nth-child(5) { width: 13%; }
.admin-table th:nth-child(6) { width: 11%; }
.admin-table th:nth-child(7) { width: 11%; }
.admin-table th:nth-child(8) { width: 10%; }

.table-image {
    display: block;
    width: 220px;
    height: 135px;
    max-width: 100%;
    object-fit: cover;
    border-radius: 7px;
    border: 1px solid #e1e7ef;
}

.no-image {
    color: #8a98a8;
    font-size: 0.85rem;
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

    .page-header,
    .table-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .page-header h1 {
        font-size: 1.45rem;
    }

    .search-box {
        width: 100%;
    }
}
</style>

<script>
    function toggleForm() {
        var container = document.getElementById('formContainer');
        if (container.style.display === 'none' || container.style.display === '') {
            container.style.display = 'block';
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            container.style.display = 'none';
        }
    }
    
    function searchTable() {
        var input = document.getElementById('searchInput');
        var filter = input.value.toUpperCase();
        var table = document.getElementById('dataTable');
        var rows = table.getElementsByTagName('tr');
        
        for (var i = 1; i < rows.length; i++) {
            var cells = rows[i].getElementsByTagName('td');
            var found = false;
            for (var j = 0; j < cells.length; j++) {
                var cell = cells[j];
                if (cell) {
                    var text = cell.textContent || cell.innerText;
                    if (text.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            rows[i].style.display = found ? '' : 'none';
        }
    }
    
    <?php if ($edit_data): ?>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('formContainer').style.display = 'block';
    });
    <?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>