<?php
session_start();
if ((!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)
    && (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true)) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("SELECT image FROM gallery WHERE id = ?");
        $stmt->execute([$id]);
        $gallery = $stmt->fetch();
        
        if ($gallery && $gallery['image']) {
            $image_path = '../uploads/' . $gallery['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['message'] = 'Foto berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Gagal menghapus foto!';
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: gallery.php');
    exit();
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    
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
                try {
                    $stmt = $pdo->prepare("INSERT INTO gallery (title, image, category) VALUES (?, ?, ?)");
                    $stmt->execute([$title, $image, $category]);
                    
                    $_SESSION['message'] = 'Foto berhasil ditambahkan!';
                    $_SESSION['message_type'] = 'success';
                } catch (PDOException $e) {
                    $_SESSION['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
                    $_SESSION['message_type'] = 'danger';
                }
            } else {
                $_SESSION['message'] = 'Gagal mengupload gambar!';
                $_SESSION['message_type'] = 'danger';
            }
        }
    } else {
        $_SESSION['message'] = 'Silakan pilih gambar!';
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: gallery.php');
    exit();
}

$galleries = $pdo->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll();

include '../includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-images"></i> Kelola Galeri</h1>
        <button class="btn btn-primary" onclick="toggleForm()">
            <i class="fas fa-plus"></i> Tambah Foto
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
    <div id="formContainer" style="display: none;">
        <div class="form-card">
            <h3>Tambah Foto Baru</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Judul <span class="required">*</span></label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-folder"></i> Kategori</label>
                        <select name="category">
                            <option value="">Pilih Kategori</option>
                            <option value="wisma">Wisma</option>
                            <option value="gedung">Gedung Serbaguna</option>
                            <option value="kios">Kios Pasar</option>
                            <option value="kegiatan">Kegiatan</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-image"></i> Gambar <span class="required">*</span></label>
                    <input type="file" name="image" accept="image/*" required>
                    <p style="font-size: 0.8rem; color: #666; margin-top: 5px;">Format: JPG, PNG, GIF, WEBP. Maksimal 5MB.</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Gallery Grid -->
    <div class="gallery-grid">
        <?php if (count($galleries) > 0): ?>
            <?php foreach ($galleries as $item): ?>
            <div class="gallery-item">
                <div class="gallery-image">
                    <img src="../uploads/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <div class="gallery-overlay">
                        <div class="gallery-actions">
                            <a href="?delete=<?php echo $item['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus foto ini?')" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="gallery-info">
                    <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                    <?php if ($item['category']): ?>
                        <span class="category-badge"><?php echo htmlspecialchars($item['category']); ?></span>
                    <?php endif; ?>
                    <small><?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-gallery">
                <i class="fas fa-images"></i>
                <p>Belum ada foto di galeri</p>
                <button class="btn btn-primary" onclick="toggleForm()">
                    <i class="fas fa-plus"></i> Tambah Foto Pertama
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .gallery-item {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: all 0.3s;
    }
    
    .gallery-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    
    .gallery-image {
        position: relative;
        overflow: hidden;
        padding-top: 75%;
        background: #f0f0f0;
    }
    
    .gallery-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }
    
    .gallery-item:hover .gallery-image img {
        transform: scale(1.05);
    }
    
    .gallery-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        opacity: 0;
        transition: opacity 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .gallery-item:hover .gallery-overlay {
        opacity: 1;
    }
    
    .gallery-actions {
        display: flex;
        gap: 10px;
    }
    
    .gallery-info {
        padding: 15px;
    }
    
    .gallery-info h4 {
        margin: 0 0 5px 0;
        color: #1a3a5c;
        font-size: 1rem;
    }
    
    .gallery-info small {
        color: #999;
        font-size: 0.8rem;
        display: block;
        margin-top: 5px;
    }
    
    .category-badge {
        display: inline-block;
        padding: 2px 10px;
        background: #e9ecef;
        color: #1a3a5c;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .empty-gallery {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .empty-gallery i {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .empty-gallery p {
        color: #999;
        font-size: 1.1rem;
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .gallery-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
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
</script>

<?php include '../includes/footer.php'; ?>