<?php
// Include header
include '../includes/header.php';

$reservation_message = '';
$reservation_message_type = 'success';
$whatsapp_redirect_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_wisma_reservation') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
    $reservation_date = $_POST['reservation_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if ($customer_name === '' || $customer_phone === '' || !$room_id || $reservation_date === '') {
        $reservation_message = 'Lengkapi nama, nomor WhatsApp, kamar, dan tanggal pemesanan.';
        $reservation_message_type = 'error';
    } elseif (strtotime($reservation_date) < strtotime(date('Y-m-d'))) {
        $reservation_message = 'Tanggal pemesanan tidak boleh sebelum hari ini.';
        $reservation_message_type = 'error';
    } else {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS reservations (id INT PRIMARY KEY AUTO_INCREMENT, customer_name VARCHAR(120) NOT NULL, customer_phone VARCHAR(30) NOT NULL, service_type VARCHAR(120) NOT NULL, reservation_date DATE NOT NULL, guest_count INT NOT NULL DEFAULT 1, notes TEXT, status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec('ALTER TABLE reservations MODIFY service_type VARCHAR(120) NOT NULL');
            $stmt = $pdo->prepare('SELECT room_name, room_number, status FROM wisma_rooms WHERE id = ? LIMIT 1');
            $stmt->execute([$room_id]);
            $selected_room = $stmt->fetch();

            if (!$selected_room || $selected_room['status'] !== 'available') {
                $reservation_message = 'Kamar yang dipilih sudah tidak tersedia.';
                $reservation_message_type = 'error';
            } else {
                $stmt = $pdo->prepare('INSERT INTO reservations (customer_name, customer_phone, service_type, reservation_date, guest_count, notes) VALUES (?, ?, ?, ?, 1, ?)');
                $service_name = 'Wisma Indonesia: ' . $selected_room['room_name'] . ' (Kamar ' . $selected_room['room_number'] . ')';
                $stmt->execute([$customer_name, $customer_phone, $service_name, $reservation_date, $notes]);
                $whatsapp_message = "Halo, saya ingin memesan layanan berikut:\n\nNama: {$customer_name}\nNomor WhatsApp: {$customer_phone}\nLayanan: {$service_name}\nTanggal: {$reservation_date}\nCatatan: " . ($notes !== '' ? $notes : '-') . "\n\nMohon konfirmasi reservasi saya.";
                $whatsapp_redirect_url = 'https://wa.me/6281238951714?text=' . rawurlencode($whatsapp_message);
                $reservation_message = 'Reservasi kamar berhasil dikirim. Pengelola akan menghubungi Anda untuk konfirmasi.';
            }
        } catch (PDOException $e) {
            $reservation_message = 'Reservasi belum dapat dikirim. Silakan coba lagi.';
            $reservation_message_type = 'error';
        }
    }
}

// Get wisma data
try {
    $stmt = $pdo->query("SELECT * FROM wisma LIMIT 1");
    $wisma = $stmt->fetch();
    
    if ($wisma) {
        $stmt = $pdo->prepare("SELECT * FROM wisma_rooms WHERE wisma_id = ? ORDER BY room_number");
        $stmt->execute([$wisma['id']]);
        $rooms = $stmt->fetchAll();
        $general_facilities = json_decode($wisma['general_facilities'] ?? '[]', true);
        $general_facilities = is_array($general_facilities) ? $general_facilities : [];
    } else {
        $wisma = null;
        $rooms = [];
        $general_facilities = [];
    }
} catch (PDOException $e) {
    $wisma = null;
    $rooms = [];
    $general_facilities = [];
}
?>

<?php if ($whatsapp_redirect_url !== ''): ?>
    <script>window.location.href = <?php echo json_encode($whatsapp_redirect_url); ?>;</script>
<?php endif; ?>

<div class="page-content">
    <div class="container">
        
        <?php if ($wisma): ?>
            <?php
            $facility_images = [
                'lobby' => !empty($wisma['lobby_image']) && file_exists('../uploads/' . $wisma['lobby_image'])
                    ? '../uploads/' . $wisma['lobby_image']
                    : '../assets/images/wisma/loby.jpeg',
                'dining_room' => !empty($wisma['dining_room_image']) && file_exists('../uploads/' . $wisma['dining_room_image'])
                    ? '../uploads/' . $wisma['dining_room_image']
                    : '../assets/images/wisma/ruangmakan.jpeg',
                'parking_2wheel' => !empty($wisma['parking_2wheel_image']) && file_exists('../uploads/' . $wisma['parking_2wheel_image'])
                    ? '../uploads/' . $wisma['parking_2wheel_image']
                    : '../assets/images/wisma/parkirmobil.jpeg',
                'parking_4wheel' => !empty($wisma['parking_4wheel_image']) && file_exists('../uploads/' . $wisma['parking_4wheel_image'])
                    ? '../uploads/' . $wisma['parking_4wheel_image']
                    : '../assets/images/wisma/parkirmobil.jpeg'
            ];
            $hero_image = !empty($wisma['image']) && file_exists('../uploads/' . $wisma['image'])
                ? '../uploads/' . $wisma['image']
                : '../assets/images/wisma/wisma.jpeg';
            ?>
            <!-- Wisma Header -->
            <div class="wisma-header">
                <div class="wisma-hero">
                    <img src="<?php echo htmlspecialchars($hero_image); ?>" alt="<?php echo htmlspecialchars($wisma['name']); ?>">
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
                                    <div class="facility-image">
                                        <img src="<?php echo htmlspecialchars($facility_images['lobby']); ?>" alt="Fasilitas lobi Wisma PLBN Wini">
                                    </div>
                                    <div class="facility-content">
                                        <i class="fas fa-couch"></i>
                                        <h4>Lobi</h4>
                                        <p><?php echo htmlspecialchars($wisma['lobby']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($wisma['dining_room']): ?>
                                <div class="facility-item">
                                    <div class="facility-image">
                                        <img src="<?php echo htmlspecialchars($facility_images['dining_room']); ?>" alt="Fasilitas ruang makan Wisma PLBN Wini">
                                    </div>
                                    <div class="facility-content">
                                        <i class="fas fa-utensils"></i>
                                        <h4>Ruang Makan</h4>
                                        <p><?php echo htmlspecialchars($wisma['dining_room']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="facility-item">
                                <div class="facility-image">
                                    <img src="<?php echo htmlspecialchars($facility_images['parking_2wheel']); ?>" alt="Fasilitas parkir Wisma PLBN Wini">
                                </div>
                                <div class="facility-content">
                                    <i class="fas fa-motorcycle"></i>
                                    <h4>Parkiran Motor</h4>
                                    <p><?php echo $wisma['parking_2wheel']; ?> unit tersedia</p>
                                </div>
                            </div>
                            
                            <div class="facility-item">
                                <div class="facility-image">
                                    <img src="<?php echo htmlspecialchars($facility_images['parking_4wheel']); ?>" alt="Fasilitas parkir mobil Wisma PLBN Wini">
                                </div>
                                <div class="facility-content">
                                    <i class="fas fa-car"></i>
                                    <h4>Parkiran Mobil</h4>
                                    <p><?php echo $wisma['parking_4wheel']; ?> unit tersedia</p>
                                </div>
                            </div>

                            <?php foreach ($general_facilities as $facility): ?>
                                <?php
                                $facility_name = is_array($facility) ? ($facility['name'] ?? 'Fasilitas Wisma') : 'Fasilitas Wisma';
                                $facility_description = is_array($facility) ? ($facility['description'] ?? '') : $facility;
                                $facility_image = is_array($facility) ? ($facility['image'] ?? '') : '';
                                ?>
                                <?php if (trim($facility_description) !== ''): ?>
                                    <div class="facility-item">
                                        <?php if ($facility_image && file_exists('../uploads/' . $facility_image)): ?>
                                            <div class="facility-image">
                                                <img src="../uploads/<?php echo htmlspecialchars($facility_image); ?>" alt="<?php echo htmlspecialchars($facility_name); ?>">
                                            </div>
                                        <?php endif; ?>
                                        <div class="facility-content">
                                            <i class="fas fa-star"></i>
                                            <h4><?php echo htmlspecialchars($facility_name); ?></h4>
                                            <p><?php echo htmlspecialchars($facility_description); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
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

                                    <div class="room-actions">
                                        <?php if ($room['status'] === 'available'): ?>
                                            <a class="btn-book" href="#reservasi-wisma" onclick="document.getElementById('wisma_room_id').value='<?php echo $room['id']; ?>';">
                                                <i class="fas fa-calendar-check"></i> Pesan kamar ini
                                            </a>
                                        <?php else: ?>
                                            <button class="btn-book" type="button" disabled>
                                                <i class="fas fa-times-circle"></i> Tidak Tersedia
                                            </button>
                                        <?php endif; ?>
                                    </div>
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

            <section class="wisma-reservation" id="reservasi-wisma">
                <div class="wisma-reservation-copy">
                    <span class="section-kicker">Reservasi kamar</span>
                    <h2>Pesan kamar langsung di sini</h2>
                    <p>Pilih kamar yang diinginkan</p>
                </div>
                <?php if ($reservation_message !== ''): ?>
                    <div class="wisma-reservation-alert wisma-reservation-alert-<?php echo $reservation_message_type; ?>" role="status">
                        <i class="fas <?php echo $reservation_message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                        <?php echo htmlspecialchars($reservation_message); ?>
                    </div>
                <?php endif; ?>
                <?php if (count(array_filter($rooms, fn($room) => $room['status'] === 'available')) > 0): ?>
                    <form class="wisma-reservation-form" method="POST" action="wisma.php#reservasi-wisma">
                        <input type="hidden" name="action" value="create_wisma_reservation">
                        <div class="wisma-reservation-field"><label for="wisma_customer_name">Nama lengkap</label><input id="wisma_customer_name" name="customer_name" type="text" maxlength="120" required></div>
                        <div class="wisma-reservation-field"><label for="wisma_customer_phone">Nomor WhatsApp</label><input id="wisma_customer_phone" name="customer_phone" type="tel" maxlength="30" required></div>
                        <div class="wisma-reservation-field"><label for="wisma_room_id">Pilih kamar</label><select id="wisma_room_id" name="room_id" required><option value="">Pilih kamar tersedia</option><?php foreach ($rooms as $room): ?><?php if ($room['status'] === 'available'): ?><option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['room_name']); ?> (Kamar <?php echo htmlspecialchars($room['room_number']); ?>)</option><?php endif; ?><?php endforeach; ?></select></div>
                        <div class="wisma-reservation-field"><label for="wisma_reservation_date">Tanggal pemesanan</label><input id="wisma_reservation_date" name="reservation_date" type="date" min="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="wisma-reservation-field wisma-reservation-field-wide"><label for="wisma_notes">Catatan tambahan <span>(opsional)</span></label><textarea id="wisma_notes" name="notes" rows="3" maxlength="1000"></textarea></div>
                        <button class="wisma-reservation-submit" type="submit"><i class="fas fa-paper-plane"></i> Kirim Reservasi Kamar</button>
                    </form>
                <?php else: ?>
                    <div class="wisma-reservation-empty">Saat ini belum ada kamar yang tersedia untuk dipesan.</div>
                <?php endif; ?>
            </section>

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
    body:has(.wisma-header) {
        background: url('../assets/images/wini.jpg') center / cover fixed no-repeat;
    }

    body:has(.wisma-header) main {
        max-width: none;
        padding: 0;
    }

    .page-content {
        padding: 40px 0;
        background:
            linear-gradient(135deg, rgba(245, 247, 250, 0.78), rgba(233, 236, 239, 0.72)),
            url('../assets/images/wini.jpg') center / cover fixed no-repeat;
        min-height: 100vh;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* Wisma Header */
    .wisma-header {
        background: rgba(255, 255, 255, 0.78);
        backdrop-filter: blur(7px);
        -webkit-backdrop-filter: blur(7px);
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
        background: rgba(248, 249, 250, 0.76);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        overflow: hidden;
        border-radius: 8px;
        border-left: 4px solid #1a3a5c;
        box-shadow: 0 4px 12px rgba(21, 59, 91, 0.06);
    }

    .facility-image {
        width: 100%;
        height: 145px;
        background: #e9eef1;
        overflow: hidden;
    }

    .facility-image img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .facility-item:hover .facility-image img {
        transform: scale(1.05);
    }

    .facility-content {
        padding: 18px 20px 20px;
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

    @media (max-width: 768px) {
        .facility-image {
            height: 165px;
        }
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
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
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

    .room-actions {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #e8ecf0;
    }

    .btn-book {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 40px;
        padding: 12px;
        border: none;
        border-radius: 8px;
        background: linear-gradient(135deg, #1a3a5c 0%, #2d6a8f 100%);
        color: white;
        font: inherit;
        font-size: 0.95rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .btn-book:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(26, 58, 92, 0.3);
    }

    .btn-book:disabled {
        background: #ccc;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .btn-book i {
        margin-right: 8px;
    }

    .wisma-reservation {
        margin: 3rem 0 2rem;
        padding: 2.5rem;
        border: 1px solid rgba(220, 232, 232, 0.9);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.78);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        box-shadow: 0 12px 28px rgba(21, 59, 91, 0.08);
    }

    .wisma-reservation-copy h2 {
        margin: 0.35rem 0 0.5rem;
        color: #153b5b;
        font-size: clamp(1.6rem, 3vw, 2.3rem);
    }

    .wisma-reservation-copy p {
        margin: 0 0 1.4rem;
        color: #607080;
    }

    .wisma-reservation-form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        padding: 1.5rem;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.68);
    }

    .wisma-reservation-field { display: grid; gap: 0.4rem; }
    .wisma-reservation-field-wide { grid-column: 1 / -1; }
    .wisma-reservation-field label { color: #153b5b; font-size: 0.85rem; font-weight: 700; }
    .wisma-reservation-field label span { color: #8a98a8; font-weight: 400; }
    .wisma-reservation-field input,
    .wisma-reservation-field select,
    .wisma-reservation-field textarea {
        width: 100%;
        padding: 0.75rem 0.85rem;
        border: 1px solid #cbd9df;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.9);
        color: #273b4d;
        font: inherit;
    }
    .wisma-reservation-field textarea { resize: vertical; }
    .wisma-reservation-submit {
        grid-column: 1 / -1;
        justify-self: start;
        padding: 0.8rem 1.15rem;
        border: 0;
        border-radius: 6px;
        background: #153b5b;
        color: #fff;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }
    .wisma-reservation-submit:hover { background: #21827c; }
    .wisma-reservation-alert,
    .wisma-reservation-empty { margin-bottom: 1rem; padding: 0.85rem 1rem; border-radius: 6px; }
    .wisma-reservation-alert { display: flex; align-items: center; gap: 0.55rem; font-weight: 600; }
    .wisma-reservation-alert-success { background: #e4f5ed; color: #176c4c; }
    .wisma-reservation-alert-error { background: #fdebea; color: #9a3030; }
    .wisma-reservation-empty { background: rgba(255, 243, 205, 0.75); color: #856404; }
    
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

        .wisma-reservation {
            padding: 1.25rem;
        }

        .wisma-reservation-form {
            grid-template-columns: 1fr;
            padding: 1rem;
        }

        .wisma-reservation-field-wide,
        .wisma-reservation-submit {
            grid-column: auto;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>
