<?php
session_start();
if (!isset($_SESSION['user_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Get user info from session - support both user and admin login
$user_name = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Get statistics
try {
    $wisma_count = $pdo->query("SELECT COUNT(*) FROM wisma")->fetchColumn();
    $gedung_count = $pdo->query("SELECT COUNT(*) FROM gedung")->fetchColumn();
    $kios_count = $pdo->query("SELECT COUNT(*) FROM kios")->fetchColumn();
    $gallery_count = $pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();

    // Get recent wisma
    $recent_wisma = $pdo->query("SELECT * FROM wisma ORDER BY created_at DESC LIMIT 5")->fetchAll();

    // Get recent gedung
    $recent_gedung = $pdo->query("SELECT * FROM gedung ORDER BY created_at DESC LIMIT 5")->fetchAll();

    // Get recent kios
    $recent_kios = $pdo->query("SELECT * FROM kios ORDER BY created_at DESC LIMIT 5")->fetchAll();

    // Get total items
    $total_items = $wisma_count + $gedung_count + $kios_count;

    // Get available items
    $available_wisma = $pdo->query("SELECT COUNT(*) FROM wisma WHERE status = 'available'")->fetchColumn();
    $available_gedung = $pdo->query("SELECT COUNT(*) FROM gedung WHERE status = 'available'")->fetchColumn();
    $available_kios = $pdo->query("SELECT COUNT(*) FROM kios WHERE status = 'available'")->fetchColumn();
    $total_available = $available_wisma + $available_gedung + $available_kios;
} catch (PDOException $e) {
    $wisma_count = 0;
    $gedung_count = 0;
    $kios_count = 0;
    $gallery_count = 0;
    $recent_wisma = [];
    $recent_gedung = [];
    $recent_kios = [];
    $total_items = 0;
    $available_wisma = 0;
    $available_gedung = 0;
    $available_kios = 0;
    $total_available = 0;
}

include '../includes/header.php';
?>

<div class="admin-dashboard">
    <div class="dashboard-header">
        <div>
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <p class="welcome-text">Selamat datang kembali, <strong><?php echo htmlspecialchars($user_name); ?></strong>!</p>
        </div>
        <div class="dashboard-actions">
            <a href="../index.php" target="_blank" class="btn btn-primary">
                <i class="fas fa-eye"></i> Lihat Website
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card stat-wisma">
            <div class="stat-icon">
                <i class="fas fa-hotel"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $wisma_count; ?></h3>
                <p>Total Wisma</p>
                <span class="stat-badge <?php echo $available_wisma > 0 ? 'badge-success' : 'badge-danger'; ?>">
                    <?php echo $available_wisma; ?> Tersedia
                </span>
            </div>
        </div>
        
        <div class="stat-card stat-gedung">
            <div class="stat-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $gedung_count; ?></h3>
                <p>Total Gedung</p>
                <span class="stat-badge <?php echo $available_gedung > 0 ? 'badge-success' : 'badge-danger'; ?>">
                    <?php echo $available_gedung; ?> Tersedia
                </span>
            </div>
        </div>
        
        <div class="stat-card stat-kios">
            <div class="stat-icon">
                <i class="fas fa-store"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $kios_count; ?></h3>
                <p>Total Kios</p>
                <span class="stat-badge <?php echo $available_kios > 0 ? 'badge-success' : 'badge-danger'; ?>">
                    <?php echo $available_kios; ?> Tersedia
                </span>
            </div>
        </div>
        
        <div class="stat-card stat-gallery">
            <div class="stat-icon">
                <i class="fas fa-images"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $gallery_count; ?></h3>
                <p>Total Galeri</p>
                <span class="stat-badge badge-info">
                    <i class="fas fa-photo"></i> Foto
                </span>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="quick-stat">
            <i class="fas fa-boxes"></i>
            <div>
                <span class="label">Total Item</span>
                <span class="value"><?php echo $total_items; ?></span>
            </div>
        </div>
        <div class="quick-stat">
            <i class="fas fa-check-circle"></i>
            <div>
                <span class="label">Tersedia</span>
                <span class="value"><?php echo $total_available; ?></span>
            </div>
        </div>
        <div class="quick-stat">
            <i class="fas fa-clock"></i>
            <div>
                <span class="label">Login Terakhir</span>
                <span class="value"><?php echo isset($admin['last_login']) ? date('d/m/Y H:i', strtotime($admin['last_login'])) : 'Belum pernah'; ?></span>
            </div>
        </div>
    </div>

    <!-- Recent Items -->
    <div class="recent-items">
        <div class="recent-section">
            <h3><i class="fas fa-hotel"></i> Wisma Terbaru</h3>
            <div class="recent-list">
                <?php if (count($recent_wisma) > 0): ?>
                    <?php foreach ($recent_wisma as $item): ?>
                    <div class="recent-item">
                        <span class="item-title"><?php echo htmlspecialchars($item['name'] ?? $item['title'] ?? 'Wisma'); ?></span>
                        <span class="item-status status-<?php echo $item['status']; ?>">
                            <?php echo ucfirst($item['status']); ?>
                        </span>
                        <span class="item-date"><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-items">Belum ada data wisma</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="recent-section">
            <h3><i class="fas fa-building"></i> Gedung Terbaru</h3>
            <div class="recent-list">
                <?php if (count($recent_gedung) > 0): ?>
                    <?php foreach ($recent_gedung as $item): ?>
                    <div class="recent-item">
                        <span class="item-title"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="item-status status-<?php echo $item['status']; ?>">
                            <?php echo ucfirst($item['status']); ?>
                        </span>
                        <span class="item-date"><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-items">Belum ada data gedung</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="recent-section">
            <h3><i class="fas fa-store"></i> Kios Terbaru</h3>
            <div class="recent-list">
                <?php if (count($recent_kios) > 0): ?>
                    <?php foreach ($recent_kios as $item): ?>
                    <div class="recent-item">
                        <span class="item-title"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="item-status status-<?php echo $item['status']; ?>">
                            <?php echo ucfirst($item['status']); ?>
                        </span>
                        <span class="item-date"><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-items">Belum ada data kios</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3><i class="fas fa-bolt"></i> Aksi Cepat</h3>
        <div class="action-grid">
            <a href="wisma.php" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <span>Tambah Wisma</span>
            </a>
            <a href="gedung.php" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <span>Tambah Gedung</span>
            </a>
            <a href="kios.php" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <span>Tambah Kios</span>
            </a>
            <a href="gallery.php" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <span>Tambah Galeri</span>
            </a>
        </div>
    </div>
</div>

<style>
    .admin-dashboard {
        padding: 20px;
    }
    
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .dashboard-header h1 {
        font-size: 2rem;
        color: #1a3a5c;
        margin-bottom: 5px;
    }
    
    .dashboard-header h1 i {
        color: #ffd700;
    }
    
    .welcome-text {
        color: #666;
        font-size: 1.1rem;
    }
    
    .welcome-text strong {
        color: #1a3a5c;
    }
    
    .dashboard-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .btn i {
        margin-right: 8px;
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
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 174px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: all 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: white;
    }
    
    .stat-wisma .stat-icon {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .stat-gedung .stat-icon {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    
    .stat-kios .stat-icon {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .stat-gallery .stat-icon {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }
    
    .stat-info h3 {
        font-size: 2rem;
        color: #1a3a5c;
        margin: 0;
        line-height: 1.1;
        text-align: center;
    }
    
    .stat-info p {
        color: #666;
        margin: 6px 0 8px;
        font-size: 1.1rem;
        font-weight: 600;
        text-align: center;
    }
    
    .stat-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .stat-info {
        text-align: center;
    }
    
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }
    
    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    /* Quick Stats */
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .quick-stat {
        background: white;
        border-radius: 10px;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    
    .quick-stat i {
        font-size: 1.5rem;
        color: #1a3a5c;
    }
    
    .quick-stat .label {
        display: block;
        font-size: 0.8rem;
        color: #666;
    }
    
    .quick-stat .value {
        display: block;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1a3a5c;
    }
    
    /* Recent Items */
    .recent-items {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .recent-section {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .recent-section h3 {
        color: #1a3a5c;
        margin-bottom: 15px;
        font-size: 1.1rem;
    }
    
    .recent-section h3 i {
        margin-right: 8px;
    }
    
    .recent-list {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .recent-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .recent-item:last-child {
        border-bottom: none;
    }
    
    .item-title {
        flex: 1;
        font-weight: 500;
        color: #333;
    }
    
    .item-status {
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin: 0 10px;
    }
    
    .status-available {
        background: #d4edda;
        color: #155724;
    }
    
    .status-booked {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-rented {
        background: #fff3cd;
        color: #856404;
    }
    
    .item-date {
        color: #999;
        font-size: 0.8rem;
    }
    
    .no-items {
        color: #999;
        text-align: center;
        padding: 20px 0;
    }
    
    /* Quick Actions */
    .quick-actions {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .quick-actions h3 {
        color: #1a3a5c;
        margin-bottom: 15px;
        font-size: 1.1rem;
    }
    
    .quick-actions h3 i {
        margin-right: 8px;
    }
    
    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .action-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        text-decoration: none;
        color: #1a3a5c;
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    
    .action-card:hover {
        background: #e9ecef;
        border-color: #1a3a5c;
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .action-card i {
        font-size: 2rem;
        margin-bottom: 10px;
        color: #ffd700;
    }
    
    .action-card span {
        font-weight: 500;
        font-size: 0.9rem;
        text-align: center;
    }
    
    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
        }
        
        .dashboard-actions {
            width: 100%;
            flex-direction: column;
        }
        
        .dashboard-actions .btn {
            width: 100%;
            justify-content: center;
        }
        
        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }
        
        .recent-items {
            grid-template-columns: 1fr;
        }
    }

    @media (min-width: 769px) and (max-width: 1100px) {
        .stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .action-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>