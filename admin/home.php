<?php
session_start();
if (!isset($_SESSION['user_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$defaults = [
    'hero_kicker' => 'Pos Lintas Batas Negara',
    'hero_title' => 'Selamat Datang di PLBN Wini',
    'hero_description' => '',
    'hero_button_text' => 'Jelajahi Layanan',
    'about_kicker' => 'Tentang PLBN Wini',
    'about_title' => 'Gerbang perbatasan yang siap melayani perjalanan Anda',
    'about_description' => '',
    'location_title' => 'Lokasi Strategis',
    'location_description' => 'Kecamatan Insana Utara, Kabupaten Timor Tengah Utara, NTT',
    'background_image' => 'wini.jpg'
];

$pdo->exec("CREATE TABLE IF NOT EXISTS home_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hero_kicker VARCHAR(150) NOT NULL,
    hero_title VARCHAR(255) NOT NULL,
    hero_description TEXT NOT NULL,
    hero_button_text VARCHAR(100) NOT NULL,
    about_kicker VARCHAR(150) NOT NULL,
    about_title VARCHAR(255) NOT NULL,
    about_description TEXT NOT NULL,
    location_title VARCHAR(150) NOT NULL,
    location_description VARCHAR(255) NOT NULL,
    background_image VARCHAR(255) NOT NULL
)");

$home = $pdo->query('SELECT * FROM home_settings ORDER BY id LIMIT 1')->fetch() ?: $defaults;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    foreach (array_keys($defaults) as $field) {
        $data[$field] = trim($_POST[$field] ?? '');
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (isset($_FILES['background_upload']) && $_FILES['background_upload']['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($_FILES['background_upload']['name'], PATHINFO_EXTENSION));
        if (in_array($extension, $allowed, true)) {
            $filename = time() . '_' . uniqid() . '.' . $extension;
            if (!is_dir('../assets/images')) {
                mkdir('../assets/images', 0777, true);
            }
            if (move_uploaded_file($_FILES['background_upload']['tmp_name'], '../assets/images/' . $filename)) {
                $data['background_image'] = $filename;
            }
        }
    }

    $existing = $pdo->query('SELECT id FROM home_settings ORDER BY id LIMIT 1')->fetchColumn();
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE home_settings SET hero_kicker=?, hero_title=?, hero_description=?, hero_button_text=?, about_kicker=?, about_title=?, about_description=?, location_title=?, location_description=?, background_image=? WHERE id=?');
        $stmt->execute(array_merge(array_values($data), [$existing]));
    } else {
        $stmt = $pdo->prepare('INSERT INTO home_settings (hero_kicker, hero_title, hero_description, hero_button_text, about_kicker, about_title, about_description, location_title, location_description, background_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute(array_values($data));
    }

    $_SESSION['message'] = 'Beranda berhasil diperbarui.';
    $_SESSION['message_type'] = 'success';
    header('Location: home.php');
    exit();
}

include '../includes/header.php';
?>

<div class="admin-content home-admin-page">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-house"></i> Kelola Beranda</h1>
            <p>Edit teks utama dan background yang tampil di halaman beranda.</p>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?>">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['message']); ?>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data">
            <h3><i class="fas fa-panorama"></i> Hero Beranda</h3>
            <div class="form-group"><label>Label kecil</label><input type="text" name="hero_kicker" value="<?php echo htmlspecialchars($home['hero_kicker']); ?>" required></div>
            <div class="form-group"><label>Judul utama</label><input type="text" name="hero_title" value="<?php echo htmlspecialchars($home['hero_title']); ?>" required></div>
            <div class="form-group"><label>Deskripsi</label><textarea name="hero_description" rows="4" required><?php echo htmlspecialchars($home['hero_description']); ?></textarea></div>
            <div class="form-group"><label>Teks tombol</label><input type="text" name="hero_button_text" value="<?php echo htmlspecialchars($home['hero_button_text']); ?>" required></div>

            <h3><i class="fas fa-circle-info"></i> Tentang PLBN</h3>
            <div class="form-group"><label>Label kecil</label><input type="text" name="about_kicker" value="<?php echo htmlspecialchars($home['about_kicker']); ?>" required></div>
            <div class="form-group"><label>Judul</label><input type="text" name="about_title" value="<?php echo htmlspecialchars($home['about_title']); ?>" required></div>
            <div class="form-group"><label>Deskripsi</label><textarea name="about_description" rows="4" required><?php echo htmlspecialchars($home['about_description']); ?></textarea></div>

            <h3><i class="fas fa-location-dot"></i> Lokasi</h3>
            <div class="form-group"><label>Judul lokasi</label><input type="text" name="location_title" value="<?php echo htmlspecialchars($home['location_title']); ?>" required></div>
            <div class="form-group"><label>Alamat lokasi</label><input type="text" name="location_description" value="<?php echo htmlspecialchars($home['location_description']); ?>" required></div>

            <h3><i class="fas fa-image"></i> Background</h3>
            <div class="form-group"><label>Upload foto background baru</label><input type="file" name="background_upload" accept="image/*"></div>
            <p class="current-background">Background saat ini: <strong><?php echo htmlspecialchars($home['background_image']); ?></strong></p>
            <button class="btn btn-success" type="submit"><i class="fas fa-save"></i> Simpan Perubahan</button>
        </form>
    </div>
</div>

<style>
    .home-admin-page .page-header p { margin: 0.35rem 0 0; color: #6b7b88; }
    .home-admin-page .form-card { max-width: 900px; }
    .home-admin-page h3 { margin: 1.5rem 0 1rem; padding-bottom: 0.6rem; border-bottom: 1px solid #e1e8ed; color: #153b5b; }
    .home-admin-page h3:first-child { margin-top: 0; }
    .home-admin-page h3 i { margin-right: 0.4rem; color: #21827c; }
    .current-background { color: #6b7b88; font-size: 0.9rem; margin-bottom: 1rem; }
</style>

<?php include '../includes/footer.php'; ?>
