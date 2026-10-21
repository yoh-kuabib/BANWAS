<?php
include '../includes/header.php';

$reservation_message = '';
$reservation_message_type = 'success';
$whatsapp_redirect_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_kios_reservation') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $kios_id = filter_input(INPUT_POST, 'kios_id', FILTER_VALIDATE_INT);
    $reservation_date = $_POST['reservation_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if ($customer_name === '' || $customer_phone === '' || !$kios_id || $reservation_date === '') {
        $reservation_message = 'Lengkapi nama, nomor WhatsApp, kios, dan tanggal pemesanan.';
        $reservation_message_type = 'error';
    } elseif (strtotime($reservation_date) < strtotime(date('Y-m-d'))) {
        $reservation_message = 'Tanggal pemesanan tidak boleh sebelum hari ini.';
        $reservation_message_type = 'error';
    } else {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS reservations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                customer_name VARCHAR(120) NOT NULL,
                customer_phone VARCHAR(30) NOT NULL,
                service_type VARCHAR(120) NOT NULL,
                reservation_date DATE NOT NULL,
                guest_count INT NOT NULL DEFAULT 1,
                notes TEXT,
                status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec('ALTER TABLE reservations MODIFY service_type VARCHAR(120) NOT NULL');

            $stmt = $pdo->prepare("SELECT name, status FROM kios WHERE id = ? LIMIT 1");
            $stmt->execute([$kios_id]);
            $selected_kios = $stmt->fetch();

            if (!$selected_kios || $selected_kios['status'] !== 'available') {
                $reservation_message = 'Kios yang dipilih sudah tidak tersedia.';
                $reservation_message_type = 'error';
            } else {
                $stmt = $pdo->prepare("INSERT INTO reservations (customer_name, customer_phone, service_type, reservation_date, guest_count, notes) VALUES (?, ?, ?, ?, 1, ?)");
                $service_name = 'Kios Pasar: ' . $selected_kios['name'];
                $stmt->execute([$customer_name, $customer_phone, $service_name, $reservation_date, $notes]);
                $whatsapp_message = "Halo, saya ingin memesan layanan berikut:\n\nNama: {$customer_name}\nNomor WhatsApp: {$customer_phone}\nLayanan: {$service_name}\nTanggal: {$reservation_date}\nCatatan: " . ($notes !== '' ? $notes : '-') . "\n\nMohon konfirmasi reservasi saya.";
                $whatsapp_redirect_url = 'https://wa.me/6281238951714?text=' . rawurlencode($whatsapp_message);
                $reservation_message = 'Reservasi kios berhasil dikirim. Pengelola akan menghubungi Anda untuk konfirmasi.';
            }
        } catch (PDOException $e) {
            $reservation_message = 'Reservasi belum dapat dikirim. Silakan coba lagi.';
            $reservation_message_type = 'error';
        }
    }
}

try {
    $stmt = $pdo->query("SELECT * FROM kios ORDER BY id DESC");
    $kios = $stmt->fetchAll();
} catch (PDOException $e) {
    $kios = [];
}

$rented_count = count(array_filter($kios, function ($item) {
    return in_array($item['status'], ['rented', 'occupied'], true);
}));
$available_count = count(array_filter($kios, function ($item) {
    return $item['status'] === 'available';
}));
?>

<?php if ($whatsapp_redirect_url !== ''): ?>
    <script>window.location.href = <?php echo json_encode($whatsapp_redirect_url); ?>;</script>
<?php endif; ?>

<div class="page-content">
    <div class="container">
        <section class="kios-intro">
            <div class="kios-intro-copy">
                <span class="section-kicker">Pasar Wini</span>
                <h1>Kios usaha di kawasan PLBN Wini</h1>
                <p>Lihat gambar kios dan tarif sewanya. Pilih ruang usaha yang sesuai untuk kebutuhan Anda.</p>
            </div>
            <div class="kios-summary" aria-label="Ringkasan kios">
                <div>
                    <strong><?php echo $rented_count; ?></strong>
                    <span>Sudah disewa</span>
                </div>
                <div>
                    <strong><?php echo $available_count; ?></strong>
                    <span>Masih tersedia</span>
                </div>
            </div>
        </section>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-header">
                <h2><i class="fas fa-filter"></i> Filter Kios</h2>
                <button class="btn-filter-toggle" onclick="toggleFilter()">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div class="filter-body" id="filterBody">
                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-tag"></i> Status</label>
                        <select id="statusFilter" onchange="applyFilters()">
                            <option value="all">Semua</option>
                            <option value="available">Tersedia</option>
                            <option value="rented">Disewa</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Cari</label>
                        <input type="text" id="searchFilter" placeholder="Cari kios..." onkeyup="applyFilters()">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-sort"></i> Urutkan</label>
                        <select id="sortFilter" onchange="applyFilters()">
                            <option value="newest">Terbaru</option>
                            <option value="oldest">Terlama</option>
                            <option value="price_low">Harga Terendah</option>
                            <option value="price_high">Harga Tertinggi</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Count -->
        <div class="results-count">
            <span id="resultCount"><?php echo count($kios); ?></span> Kios ditemukan
        </div>

        <!-- Kios Grid -->
        <div class="kios-grid" id="kiosGrid">
            <?php if (count($kios) > 0): ?>
                <?php foreach ($kios as $item): ?>
                    <?php $is_rented = in_array($item['status'], ['rented', 'occupied'], true); ?>
                    <div class="kios-card" 
                        data-status="<?php echo $is_rented ? 'rented' : htmlspecialchars($item['status']); ?>"
                         data-name="<?php echo strtolower(htmlspecialchars($item['name'])); ?>"
                         data-price="<?php echo $item['price']; ?>"
                         data-date="<?php echo $item['created_at']; ?>">
                        <div class="kios-image">
                            <?php if ($item['image'] && file_exists('../uploads/' . $item['image'])): ?>
                                <img src="../uploads/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                                <img src="../assets/images/placeholder-kios.jpg" alt="No Image">
                            <?php endif; ?>
                            <div class="kios-status status-<?php echo $is_rented ? 'rented' : htmlspecialchars($item['status']); ?>">
                                <?php echo $is_rented ? 'Disewa' : 'Tersedia'; ?>
                            </div>
                            <?php if ($item['price']): ?>
                                <div class="kios-price-badge">
                                    Rp <?php echo number_format($item['price'], 0, ',', '.'); ?> / hari
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="kios-body">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <?php if ($item['size']): ?>
                                <div class="kios-size">
                                    <i class="fas fa-arrows-alt"></i> <?php echo htmlspecialchars($item['size']); ?>
                                </div>
                            <?php endif; ?>
                            <p class="kios-description">
                                <?php echo htmlspecialchars(substr($item['description'], 0, 120)) . (strlen($item['description']) > 120 ? '...' : ''); ?>
                            </p>
                            <div class="kios-footer">
                                <span class="kios-rental-label"><i class="fas fa-money-bill-wave"></i> Tarif sewa</span>
                                <strong class="kios-rental-price">
                                    <?php echo $item['price'] ? 'Rp ' . number_format($item['price'], 0, ',', '.') . ' / hari' : 'Hubungi pengelola'; ?>
                                </strong>
                            </div>
                            <?php if (!$is_rented && $item['status'] === 'available'): ?>
                                <a class="kios-reserve-link" href="#reservasi-kios" onclick="document.getElementById('kios_id').value='<?php echo $item['id']; ?>';">
                                    <i class="fas fa-calendar-check"></i> Pesan kios ini
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-store"></i>
                    <h3>Belum Ada Kios</h3>
                    <p>Belum ada data kios yang tersedia saat ini.</p>
                </div>
            <?php endif; ?>
        </div>

        <section class="kios-reservation" id="reservasi-kios">
            <div class="kios-reservation-copy">
                <span class="section-kicker">Reservasi kios</span>
                <h2>Pesan kios langsung dari halaman ini</h2>
                <p>Pilih kios yang tersedia dan kirim permintaan kepada pengelola tanpa kembali ke beranda.</p>
            </div>
            <?php if ($reservation_message !== ''): ?>
                <div class="kios-reservation-alert kios-reservation-alert-<?php echo $reservation_message_type; ?>" role="status">
                    <i class="fas <?php echo $reservation_message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                    <?php echo htmlspecialchars($reservation_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($available_count > 0): ?>
                <form class="kios-reservation-form" method="POST" action="kios.php#reservasi-kios">
                    <input type="hidden" name="action" value="create_kios_reservation">
                    <div class="kios-reservation-field">
                        <label for="kios_customer_name">Nama lengkap</label>
                        <input id="kios_customer_name" name="customer_name" type="text" maxlength="120" required>
                    </div>
                    <div class="kios-reservation-field">
                        <label for="kios_customer_phone">Nomor WhatsApp</label>
                        <input id="kios_customer_phone" name="customer_phone" type="tel" maxlength="30" required>
                    </div>
                    <div class="kios-reservation-field">
                        <label for="kios_id">Pilih kios</label>
                        <select id="kios_id" name="kios_id" required>
                            <option value="">Pilih kios tersedia</option>
                            <?php foreach ($kios as $item): ?>
                                <?php if ($item['status'] === 'available'): ?>
                                    <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="kios-reservation-field">
                        <label for="kios_reservation_date">Tanggal pemesanan</label>
                        <input id="kios_reservation_date" name="reservation_date" type="date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="kios-reservation-field kios-reservation-field-wide">
                        <label for="kios_notes">Catatan tambahan <span>(opsional)</span></label>
                        <textarea id="kios_notes" name="notes" rows="3" maxlength="1000" placeholder="Tuliskan kebutuhan atau pertanyaan Anda"></textarea>
                    </div>
                    <button class="kios-reservation-submit" type="submit"><i class="fas fa-paper-plane"></i> Kirim Reservasi Kios</button>
                </form>
            <?php else: ?>
                <div class="kios-reservation-empty">Saat ini belum ada kios yang tersedia untuk dipesan.</div>
            <?php endif; ?>
        </section>
    </div>
</div>

<style>
    body:has(.kios-intro) {
        background: url('../assets/images/wini.jpg') center / cover fixed no-repeat;
    }

    body:has(.kios-intro) main {
        max-width: none;
        padding: 0;
    }

    .page-content {
        min-height: 0;
        padding: 42px 0 64px;
        background:
            radial-gradient(circle at 12% 8%, rgba(255, 212, 71, 0.12), transparent 20rem),
            linear-gradient(180deg, rgba(244, 248, 250, 0.78) 0%, rgba(238, 244, 245, 0.72) 100%);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }
    
    .container {
        max-width: 1180px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .kios-intro {
        display: flex;
        justify-content: space-between;
        align-items: end;
        gap: 30px;
        margin-bottom: 26px;
        padding: 38px 40px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 18px;
        background: linear-gradient(118deg, #123a5b 0%, #1b6075 62%, #25847b 100%);
        color: white;
        box-shadow: 0 18px 36px rgba(19, 62, 86, 0.18);
        overflow: hidden;
        position: relative;
    }

    .kios-intro::after {
        content: '';
        position: absolute;
        width: 330px;
        height: 330px;
        right: -110px;
        top: -190px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        box-shadow: 0 0 0 22px rgba(255, 255, 255, 0.04), 0 0 0 48px rgba(255, 255, 255, 0.03);
        pointer-events: none;
    }

    .kios-intro-copy {
        max-width: 650px;
        position: relative;
        z-index: 1;
    }

    .kios-intro .section-kicker {
        color: #ffd447;
    }

    .kios-intro h1 {
        margin: 8px 0 10px;
        max-width: 600px;
        font-size: clamp(2rem, 4vw, 3rem);
        letter-spacing: -0.03em;
        line-height: 1.04;
    }

    .kios-intro p {
        margin: 0;
        color: rgba(255, 255, 255, 0.85);
    }

    .kios-summary {
        display: flex;
        flex-shrink: 0;
        gap: 24px;
        padding: 14px 0 2px;
        position: relative;
        z-index: 1;
    }

    .kios-summary div {
        min-width: 105px;
        padding-left: 18px;
        border-left: 1px solid rgba(255, 255, 255, 0.35);
    }

    .kios-summary strong,
    .kios-summary span {
        display: block;
    }

    .kios-summary strong {
        font-size: 1.8rem;
        line-height: 1;
    }

    .kios-summary span {
        margin-top: 6px;
        color: rgba(255, 255, 255, 0.75);
        font-size: 0.82rem;
    }
    
    /* Filter Section */
    .filter-section {
        background: white;
        border: 1px solid #e3ecef;
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 10px 24px rgba(24, 67, 86, 0.07);
        margin-bottom: 24px;
    }
    
    .filter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }
    
    .filter-header h2 {
        font-size: 1rem;
        color: #1a3a5c;
        margin: 0;
        letter-spacing: 0.01em;
    }
    
    .filter-header h2 i {
        margin-right: 8px;
        color: #ffd700;
    }
    
    .btn-filter-toggle {
        background: none;
        border: none;
        font-size: 1.2rem;
        color: #1a3a5c;
        cursor: pointer;
        transition: transform 0.3s;
    }
    
    .btn-filter-toggle.active {
        transform: rotate(180deg);
    }
    
    .filter-body {
        margin-top: 18px;
        display: none;
        padding-top: 17px;
        border-top: 1px solid #edf1f2;
    }
    
    .filter-body.show {
        display: block;
    }
    
    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .filter-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }
    
    .filter-group label i {
        color: #1a3a5c;
        margin-right: 5px;
    }
    
    .filter-group select,
    .filter-group input {
        width: 100%;
        min-height: 42px;
        padding: 8px 12px;
        border: 1px solid #d9e4e8;
        border-radius: 9px;
        font-size: 0.95rem;
        background: #f8f9fa;
        transition: all 0.3s;
    }
    
    .filter-group select:focus,
    .filter-group input:focus {
        border-color: #1a3a5c;
        outline: none;
        background: white;
        box-shadow: 0 0 0 3px rgba(26, 58, 92, 0.1);
    }
    
    .results-count {
        color: #71818b;
        font-size: 0.95rem;
        margin: 0 0 14px 2px;
    }
    
    .results-count span {
        font-weight: 600;
        color: #1a3a5c;
    }
    
    /* Kios Grid */
    .kios-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
    }
    
    .kios-card {
        min-width: 0;
        background: white;
        border: 1px solid #e3ecef;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 8px 20px rgba(24, 67, 86, 0.07);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .kios-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 16px 30px rgba(24, 67, 86, 0.14);
    }
    
    .kios-image {
        position: relative;
        padding-top: 64%;
        background: #f0f0f0;
        overflow: hidden;
    }
    
    .kios-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }
    
    .kios-card:hover .kios-image img {
        transform: scale(1.05);
    }
    
    .kios-status {
        position: absolute;
        top: 14px;
        left: 14px;
        padding: 6px 12px;
        border-radius: 7px;
        font-size: 0.8rem;
        font-weight: 600;
        color: white;
        z-index: 1;
    }
    
    .status-available {
        background: #16866f;
    }
    
    .status-rented {
        background: #c14f4b;
    }
    
    .kios-price-badge {
        position: absolute;
        bottom: 14px;
        right: 14px;
        background: #ffd447;
        color: #173c5a;
        color: white;
        padding: 7px 11px;
        border-radius: 7px;
        font-weight: 600;
        font-size: 0.9rem;
        z-index: 1;
    }
    
    .kios-body {
        padding: 21px;
    }
    
    .kios-body h3 {
        margin: 0 0 5px 0;
        color: #1a3a5c;
        font-size: 1.25rem;
        letter-spacing: -0.01em;
    }
    
    .kios-size {
        color: #666;
        font-size: 0.85rem;
        margin-bottom: 10px;
    }
    
    .kios-size i {
        color: #1a3a5c;
        margin-right: 5px;
    }
    
    .kios-description {
        min-height: 46px;
        color: #71818b;
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    
    .kios-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding-top: 16px;
        border-top: 1px solid #e7eef0;
    }
    
    .kios-date {
        font-size: 0.8rem;
        color: #999;
    }
    
    .kios-date i {
        margin-right: 5px;
    }

    .kios-rental-label {
        color: #607080;
        font-size: 0.82rem;
    }

    .kios-rental-label i {
        margin-right: 5px;
        color: #21827c;
    }

    .kios-rental-price {
        color: #1a3a5c;
        font-size: 0.88rem;
        text-align: right;
    }

    .kios-reserve-link {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin-top: 1rem;
        color: #16866f;
        font-size: 0.85rem;
        font-weight: 700;
        text-decoration: none;
    }

    .kios-reserve-link:hover {
        color: #125f51;
    }

    .kios-reservation {
        max-width: 960px;
        margin: 4rem auto 0;
        padding: 2.5rem;
        border: 1px solid rgba(220, 232, 232, 0.9);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.78);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        box-shadow: 0 12px 28px rgba(24, 67, 86, 0.08);
    }

    .kios-reservation-copy h2 {
        margin: 0.35rem 0 0.5rem;
        color: #153b5b;
        font-size: clamp(1.6rem, 3vw, 2.3rem);
    }

    .kios-reservation-copy p {
        margin: 0 0 1.4rem;
        color: #607080;
    }

    .kios-reservation-form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        padding: 1.5rem;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.68);
    }

    .kios-reservation-field {
        display: grid;
        gap: 0.4rem;
    }

    .kios-reservation-field-wide {
        grid-column: 1 / -1;
    }

    .kios-reservation-field label {
        color: #153b5b;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .kios-reservation-field label span {
        color: #8a98a8;
        font-weight: 400;
    }

    .kios-reservation-field input,
    .kios-reservation-field select,
    .kios-reservation-field textarea {
        width: 100%;
        padding: 0.75rem 0.85rem;
        border: 1px solid #cbd9df;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.9);
        color: #273b4d;
        font: inherit;
    }

    .kios-reservation-field textarea {
        resize: vertical;
    }

    .kios-reservation-submit {
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

    .kios-reservation-submit:hover {
        background: #21827c;
    }

    .kios-reservation-alert {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        margin-bottom: 1rem;
        padding: 0.85rem 1rem;
        border-radius: 6px;
        font-weight: 600;
    }

    .kios-reservation-alert-success {
        background: #e4f5ed;
        color: #176c4c;
    }

    .kios-reservation-alert-error {
        background: #fdebea;
        color: #9a3030;
    }

    .kios-reservation-empty {
        padding: 1rem;
        border-radius: 6px;
        background: rgba(255, 243, 205, 0.75);
        color: #856404;
    }
    
    .btn-detail {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #1a3a5c;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-detail:hover {
        background: #2d6a8f;
        transform: translateX(3px);
    }
    
    .btn-detail i {
        transition: transform 0.3s;
    }
    
    .btn-detail:hover i {
        transform: translateX(3px);
    }
    
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        color: #1a3a5c;
        margin-bottom: 10px;
    }
    
    .empty-state p {
        color: #999;
    }
    
    @media (max-width: 768px) {
        .page-content {
            padding-top: 24px;
        }

        .kios-intro {
            align-items: start;
            flex-direction: column;
            gap: 20px;
            padding: 28px 24px;
        }

        .kios-summary {
            width: 100%;
            gap: 12px;
        }

        .kios-summary div {
            flex: 1;
        }

        .kios-grid {
            grid-template-columns: 1fr;
        }

        .kios-reservation {
            margin-top: 3rem;
            padding: 1.25rem;
        }

        .kios-reservation-form {
            grid-template-columns: 1fr;
            padding: 1rem;
        }

        .kios-reservation-field-wide,
        .kios-reservation-submit {
            grid-column: auto;
        }
        
        .filter-row {
            grid-template-columns: 1fr;
        }
        
        .filter-section {
            padding: 15px;
        }
    }
</style>

<script>
    function toggleFilter() {
        var body = document.getElementById('filterBody');
        var toggle = document.querySelector('.btn-filter-toggle');
        body.classList.toggle('show');
        toggle.classList.toggle('active');
    }
    
    function applyFilters() {
        var statusFilter = document.getElementById('statusFilter').value;
        var searchFilter = document.getElementById('searchFilter').value.toLowerCase();
        var sortFilter = document.getElementById('sortFilter').value;
        
        var cards = document.querySelectorAll('.kios-card');
        var visibleCount = 0;
        var cardArray = Array.from(cards);
        
        cardArray.forEach(function(card) {
            var status = card.getAttribute('data-status');
            var name = card.getAttribute('data-name');
            
            var statusMatch = statusFilter === 'all' || status === statusFilter;
            var searchMatch = name.indexOf(searchFilter) > -1;
            
            if (statusMatch && searchMatch) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        document.getElementById('resultCount').textContent = visibleCount;
        
        var grid = document.getElementById('kiosGrid');
        var sortedCards = cardArray.filter(card => card.style.display !== 'none');
        
        sortedCards.sort(function(a, b) {
            var aPrice = parseInt(a.getAttribute('data-price')) || 0;
            var bPrice = parseInt(b.getAttribute('data-price')) || 0;
            var aDate = new Date(a.getAttribute('data-date'));
            var bDate = new Date(b.getAttribute('data-date'));
            
            switch(sortFilter) {
                case 'newest':
                    return bDate - aDate;
                case 'oldest':
                    return aDate - bDate;
                case 'price_low':
                    return aPrice - bPrice;
                case 'price_high':
                    return bPrice - aPrice;
                default:
                    return 0;
            }
        });
        
        sortedCards.forEach(function(card) {
            grid.appendChild(card);
        });
    }
    
    if (window.innerWidth > 768) {
        document.getElementById('filterBody').classList.add('show');
    }
</script>

<?php include '../includes/footer.php'; ?>