<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Get image path first
        $stmt = $pdo->prepare("SELECT image FROM wisma WHERE id = ?");
        $stmt->execute([$id]);
        $wisma = $stmt->fetch();
        
        if ($wisma && $wisma['image']) {
            $image_path = '../uploads/' . $wisma['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM wisma WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['message'] = 'Wisma berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Gagal menghapus wisma!';
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: wisma.php');
    exit();
}

// Handle Add/Edit
$edit_data = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM wisma WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = !empty($_POST['price']) ? $_POST['price'] : null;
    $facilities = trim($_POST['facilities']);
    $status = $_POST['status'];
    $edit_id = isset($_POST['edit_id']) ? $_POST['edit_id'] : null;
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed)) {
            $_SESSION['message'] = 'Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WEBP.';
            $_SESSION['message_type'] = 'danger';
        } else {
            $target_dir = '../uploads/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $image = time() . '_' . uniqid() . '.' . $file_ext;
            $target_file = $target_dir . $image;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // Delete old image if editing
                if ($edit_id) {
                    $stmt = $pdo->prepare("SELECT image FROM wisma WHERE id = ?");
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
                $stmt = $pdo->prepare("UPDATE wisma SET title = ?, description = ?, price = ?, facilities = ?, image = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $description, $price, $facilities, $image, $status, $edit_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE wisma SET title = ?, description = ?, price = ?, facilities = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $description, $price, $facilities, $status, $edit_id]);
            }
            $_SESSION['message'] = 'Wisma berhasil diperbarui!';
            $_SESSION['message_type'] = 'success';
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO wisma (title, description, price, facilities, image, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $price, $facilities, $image, $status]);
            $_SESSION['message'] = 'Wisma berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: wisma.php');
    exit();
}

// Get all wisma
$wismas = $pdo->query("SELECT * FROM wisma ORDER BY id DESC")->fetchAll();

include '../includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-hotel"></i> Kelola Wisma Indonesia</h1>
        <button class="btn btn-primary" onclick="toggleForm()">
            <i class="fas fa-plus"></i> Tambah Wisma
        </button>
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
    
    <!-- Form -->
    <div id="formContainer" style="display: <?php echo $edit_data ? 'block' : 'none'; ?>;">
        <div class="form-card">
            <h3><?php echo $edit_data ? 'Edit Wisma' : 'Tambah Wisma Baru'; ?></h3>
            <form method="POST" enctype="multipart/form-data" id="wismaForm">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Judul <span class="required">*</span></label>
                        <input type="text" name="title" value="<?php echo $edit_data ? htmlspecialchars($edit_data['title']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-money-bill"></i> Harga (Rp)</label>
                        <input type="number" name="price" step="0.01" value="<?php echo $edit_data ? $edit_data['price'] : ''; ?>" placeholder="Contoh: 500000">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Deskripsi <span class="required">*</span></label>
                    <textarea name="description" rows="4" required><?php echo $edit_data ? htmlspecialchars($edit_data['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-concierge-bell"></i> Fasilitas</label>
                    <textarea name="facilities" rows="3" placeholder="Pisahkan dengan koma, contoh: AC, TV, Wi-Fi, Kamar Mandi Dalam"><?php echo $edit_data ? htmlspecialchars($edit_data['facilities']) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Gambar</label>
                        <input type="file" name="image" accept="image/*" <?php echo $edit_data ? '' : 'required'; ?>>
                        <?php if ($edit_data && $edit_data['image']): ?>
                            <div class="current-image">
                                <img src="../uploads/<?php echo $edit_data['image']; ?>" alt="Current image" style="max-width: 100px; max-height: 100px; margin-top: 10px;">
                                <p style="font-size: 0.8rem; color: #666;">Gambar saat ini (upload gambar baru untuk mengganti)</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-circle"></i> Status <span class="required">*</span></label>
                        <select name="status" required>
                            <option value="available" <?php echo ($edit_data && $edit_data['status'] == 'available') ? 'selected' : ''; ?>>Tersedia</option>
                            <option value="booked" <?php echo ($edit_data && $edit_data['status'] == 'booked') ? 'selected' : ''; ?>>Dipesan</option>
                        </select>
                    </div>
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
                <input type="text" id="searchInput" placeholder="Cari wisma..." onkeyup="searchTable()">
            </div>
            <div class="table-info">
                Menampilkan <strong><?php echo count($wismas); ?></strong> data
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="admin-table" id="dataTable">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Gambar</th>
                        <th>Judul</th>
                        <th>Harga</th>
                        <th>Fasilitas</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($wismas) > 0): ?>
                        <?php foreach ($wismas as $index => $wisma): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php if ($wisma['image']): ?>
                                    <img src="../uploads/<?php echo $wisma['image']; ?>" alt="<?php echo htmlspecialchars($wisma['title']); ?>" class="table-image">
                                <?php else: ?>
                                    <span class="no-image">Tidak ada</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($wisma['title']); ?></strong></td>
                            <td><?php echo $wisma['price'] ? 'Rp ' . number_format($wisma['price'], 0, ',', '.') : '-'; ?></td>
                            <td><?php echo $wisma['facilities'] ? substr(htmlspecialchars($wisma['facilities']), 0, 50) . (strlen($wisma['facilities']) > 50 ? '...' : '') : '-'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $wisma['status']; ?>">
                                    <?php echo $wisma['status'] == 'available' ? 'Tersedia' : 'Dipesan'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($wisma['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?edit=<?php echo $wisma['id']; ?>" class="btn-action btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $wisma['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus wisma ini?')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Belum ada data wisma. Klik "Tambah Wisma" untuk menambahkan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .admin-content {
        padding: 20px;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .page-header h1 {
        color: #1a3a5c;
        font-size: 1.8rem;
    }
    
    .page-header h1 i {
        color: #ffd700;
        margin-right: 10px;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        gap: 8px;
    }
    
    .btn-primary {
        background: #1a3a5c;
        color: white;
    }
    
    .btn-primary:hover {
        background: #2d6a8f;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(26, 58, 92, 0.3);
    }
    
    .btn-success {
        background: #28a745;
        color: white;
    }
    
    .btn-success:hover {
        background: #218838;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
    }
    
    /* Alert */
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert i {
        margin-right: 10px;
    }
    
    .close-alert {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: inherit;
        opacity: 0.7;
    }
    
    .close-alert:hover {
        opacity: 1;
    }
    
    /* Form */
    .form-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .form-card h3 {
        color: #1a3a5c;
        margin-bottom: 20px;
        font-size: 1.2rem;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
        font-size: 0.9rem;
    }
    
    .form-group label .required {
        color: #dc3545;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e1e5ea;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s;
        background: #f8f9fa;
    }
    
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        border-color: #1a3a5c;
        outline: none;
        background: white;
        box-shadow: 0 0 0 3px rgba(26, 58, 92, 0.1);
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }
    
    .current-image {
        margin-top: 10px;
    }
    
    /* Table */
    .table-container {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .table-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .search-box {
        position: relative;
        flex: 1;
        max-width: 300px;
    }
    
    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }
    
    .search-box input {
        width: 100%;
        padding: 10px 15px 10px 40px;
        border: 2px solid #e1e5ea;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.3s;
    }
    
    .search-box input:focus {
        border-color: #1a3a5c;
        outline: none;
        box-shadow: 0 0 0 3px rgba(26, 58, 92, 0.1);
    }
    
    .table-info {
        color: #666;
        font-size: 0.9rem;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .admin-table th {
        background: #f8f9fa;
        color: #1a3a5c;
        font-weight: 600;
        padding: 12px 15px;
        text-align: left;
        border-bottom: 2px solid #e1e5ea;
    }
    
    .admin-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }
    
    .admin-table tr:hover {
        background: #f8f9fa;
    }
    
    .table-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .no-image {
        color: #999;
        font-size: 0.8rem;
    }
    
    .status-badge {
        display: inline-block;
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
        background: #f8d7da;
        color: #721c24;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-action {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .btn-edit {
        background: #ffc107;
        color: #333;
    }
    
    .btn-edit:hover {
        background: #e0a800;
        transform: scale(1.1);
    }
    
    .btn-delete {
        background: #dc3545;
        color: white;
    }
    
    .btn-delete:hover {
        background: #c82333;
        transform: scale(1.1);
    }
    
    .text-center {
        text-align: center;
        color: #999;
        padding: 30px 0;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .table-toolbar {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-box {
            max-width: 100%;
        }
        
        .admin-table {
            font-size: 0.85rem;
        }
        
        .admin-table th,
        .admin-table td {
            padding: 8px 10px;
        }
        
        .page-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .page-header .btn {
            width: 100%;
            justify-content: center;
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
    
    // Auto show form if edit mode
    <?php if ($edit_data): ?>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('formContainer').style.display = 'block';
    });
    <?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>