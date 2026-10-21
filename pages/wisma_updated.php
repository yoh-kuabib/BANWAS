<?php
// Include header
include '../includes/header.php';

// Get wisma data
try {
    $stmt = $pdo->query("SELECT * FROM wisma LIMIT 1");
    $wisma = $stmt->fetch();
    
    if ($wisma) {
        $stmt = $pdo->prepare("SELECT * FROM wisma_rooms WHERE wisma_id = ? ORDER BY room_number");
        $stmt->execute([$wisma['id']]);
        $rooms = $stmt->fetchAll();
    } else {
        $wisma = null;
        $rooms = [];
    }
} catch (PDOException $e) {
    $wisma = null;
    $rooms = [];
}
?>

<div class="page-content">
    <div class="container">
        
        <?php if ($wisma): ?>
            <!-- Wisma Header -->
            <div class="wisma-header">
                <div class="wisma-hero">
                    <?php if ($wisma['image'] && file_exists('../uploads/' . $wisma['image'])): ?>
                        <img src="../uploads/<?php echo $wisma['image']; ?>" alt="<?php echo htmlspecialchars($wisma['name']); ?>">
                    <?php else: ?>
                        <div class="hero-placeholder">
                            <i class="fas fa-hotel"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="wisma-info">
                    <h1><?php echo htmlspecialchars($wisma['name']); ?></h1>
                    <p class="wisma-description"><?php echo htmlspecialchars($wisma['description']); ?></p>
                    
                    <!-- Fasilitas Umum -->
                    <div class="facilities-section">
                        <h3><i class="fas fa-star"></i> Fasilitas Umum</h3>
                        <div class="facilities-grid">
                            <?php if ($wisma['lobby']): ?>
                                <div class="facility-item">
                                    <i class="fas fa-couch"></i>
                                    <h4>Lobi</h4>
                                    <p><?php echo htmlspecialchars($wisma['lobby']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($wisma['dining_room']): ?>
                                <div class="facility-item">
                                    <i class="fas fa-utensils"></i>
                                    <h4>Ruang Makan</h4>
                                    <p><?php echo htmlspecialchars($wisma['dining_room']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="facility-item">
                                <i class="fas fa-motorcycle"></i>
                                <h4>Parkiran Motor</h4>
                                <p><?php echo $wisma['parking_2wheel']; ?> unit tersedia</p>
                            </div>
                            
                            <div class="facility-item">
                                <i class="fas fa-car"></i>
                                <h4>Parkiran Mobil</h4>
                                <p><?php echo $wisma['parking_4wheel']; ?> unit tersedia</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rooms Section -->
            <div class="rooms-section">
                <h2><i class="fas fa-door-open"></i> Kamar-Kamar Kami</h2>
                <p class="section-subtitle">Pilih kamar yang sesuai dengan kebutuhan Anda</p>
                
                <div class="rooms-grid">
                    <?php if (count($rooms) > 0): ?>
                        <?php foreach ($rooms as $room): ?>
                            <div class="room-card">
                                <div class="room-image">
                                    <?php if ($room['image'] && file_exists('../uploads/' . $room['image'])): ?>
                                        <img src="../uploads/<?php echo $room['image']; ?>" alt="<?php echo htmlspecialchars($room['room_name']); ?>">
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="room-number-badge">
                                        Kamar <?php echo htmlspecialchars($room['room_number']); ?>
                                    </div>
                                    <div class="room-status-badge status-<?php echo $room['status']; ?>">
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
                                
                                <div class="room-body">
                                    <h3><?php echo htmlspecialchars($room['room_name']); ?></h3>
                                    
                                    <div class="room-specs">
                                        <span class="spec">
                                            <i class="fas fa-user-friends"></i> 
                                            <?php echo $room['capacity']; ?> Tamu
                                        </span>
                                        <span class="spec">
                                            <i class="fas fa-tag"></i> 
                                            Rp <?php echo number_format($room['price'], 0, ',', '.'); ?>/malam
                                        </span>
                                    </div>
                                    
                                    <?php if ($room['description']): ?>
                                        <p class="room-description">
                                            <?php echo htmlspecialchars($room['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($room['facilities']): ?>
                                        <div class="room-facilities">
                                            <h4>Fasilitas Kamar:</h4>
                                            <div class="facilities-list">
                                                <?php 
                                                $facilities = array_map('trim', explode(',', $room['facilities']));
                                                foreach ($facilities as $facility): 
                                                ?>
                                                    <span class="facility-chip">
                                                        <i class="fas fa-check"></i> <?php echo htmlspecialchars($facility); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="grid-column: 1/-1;">
                            <i class="fas fa-door-open"></i>
                            <h3>Belum Ada Kamar</h3>
                            <p>Kamar sedang dalam proses setup.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-hotel"></i>
                <h3>Wisma Tidak Ditemukan</h3>
                <p>Data wisma sedang dalam proses setup.</p>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<style>
    .page-content {
        padding: 40px 0;
        background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        min-height: 100vh;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* Wisma Header */
    .wisma-header {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 40px;
    }
    
    .wisma-hero {
        width: 100%;
        height: 300px;
        overflow: hidden;
        background: #f0f0f0;
    }
    
    .wisma-hero img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .hero-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #1a3a5c 0%, #2d6a8f 100%);
        font-size: 4rem;
        color: rgba(255,255,255,0.5);
    }
    
    .wisma-info {
        padding: 40px;
    }
    
    .wisma-info h1 {
        font-size: 2.5rem;
        color: #1a3a5c;
        margin: 0 0 15px 0;
    }
    
    .wisma-description {
        font-size: 1.1rem;
        color: #555;
        line-height: 1.6;
        margin-bottom: 30px;
    }
    
    /* Facilities Section */
    .facilities-section h3 {
        font-size: 1.4rem;
        color: #1a3a5c;
        margin-bottom: 20px;
    }
    
    .facilities-section h3 i {
        color: #ffd700;
        margin-right: 10px;
    }
    
    .facilities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .facility-item {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #1a3a5c;
    }
    
    .facility-item i {
        font-size: 2rem;
        color: #1a3a5c;
        margin-bottom: 10px;
    }
    
    .facility-item h4 {
        font-size: 1rem;
        color: #1a3a5c;
        margin: 10px 0 5px 0;
    }
    
    .facility-item p {
        font-size: 0.9rem;
        color: #666;
        margin: 0;
    }
    
    /* Rooms Section */
    .rooms-section {
        margin-top: 40px;
        margin-bottom: 40px;
    }
    
    .rooms-section h2 {
        font-size: 2rem;
        color: #1a3a5c;
        margin-bottom: 10px;
    }
    
    .rooms-section h2 i {
        color: #ffd700;
        margin-right: 10px;
    }
    
    .section-subtitle {
        color: #666;
        font-size: 1.05rem;
        margin-bottom: 30px;
    }
    
    .rooms-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }
    
    /* Room Card */
    .room-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .room-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    }
    
    .room-image {
        position: relative;
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
    
    .image-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        color: #999;
        font-size: 3rem;
    }
    
    .room-number-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(26, 58, 92, 0.9);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .room-status-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 6px 12px;
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
    
    .room-body {
        padding: 20px;
    }
    
    .room-body h3 {
        font-size: 1.3rem;
        color: #1a3a5c;
        margin: 0 0 15px 0;
    }
    
    .room-specs {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
        font-size: 0.95rem;
        flex-wrap: wrap;
    }
    
    .spec {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #555;
    }
    
    .spec i {
        color: #1a3a5c;
        font-size: 1rem;
    }
    
    .room-description {
        color: #666;
        font-size: 0.9rem;
        line-height: 1.5;
        margin: 12px 0;
    }
    
    .room-facilities {
        margin: 15px 0;
        padding-top: 15px;
        border-top: 1px solid #e8ecf0;
    }
    
    .room-facilities h4 {
        font-size: 0.9rem;
        color: #1a3a5c;
        margin: 0 0 10px 0;
    }
    
    .facilities-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .facility-chip {
        background: #e7f3ff;
        color: #0066cc;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .facility-chip i {
        font-size: 0.75rem;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        font-size: 1.5rem;
        color: #666;
        margin: 0 0 10px 0;
    }
    
    .empty-state p {
        font-size: 1rem;
        margin: 0;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .wisma-info {
            padding: 25px;
        }
        
        .wisma-info h1 {
            font-size: 1.8rem;
        }
        
        .facilities-grid {
            grid-template-columns: 1fr;
        }
        
        .rooms-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>
