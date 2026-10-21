<?php
include '../includes/header.php';

$reservation_message = '';
$reservation_message_type = 'success';
$whatsapp_redirect_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_gedung_reservation') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $reservation_date = $_POST['reservation_date'] ?? '';
    $guest_count = max(1, (int) ($_POST['guest_count'] ?? 1));
    $notes = trim($_POST['notes'] ?? '');

    if ($customer_name === '' || $customer_phone === '' || $reservation_date === '') {
        $reservation_message = 'Lengkapi nama, nomor WhatsApp, dan tanggal pemesanan.';
        $reservation_message_type = 'error';
    } elseif (strtotime($reservation_date) < strtotime(date('Y-m-d'))) {
        $reservation_message = 'Tanggal pemesanan tidak boleh sebelum hari ini.';
        $reservation_message_type = 'error';
    } else {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS reservations (id INT PRIMARY KEY AUTO_INCREMENT, customer_name VARCHAR(120) NOT NULL, customer_phone VARCHAR(30) NOT NULL, service_type VARCHAR(120) NOT NULL, reservation_date DATE NOT NULL, guest_count INT NOT NULL DEFAULT 1, notes TEXT, status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $pdo->exec('ALTER TABLE reservations MODIFY service_type VARCHAR(120) NOT NULL');
            $stmt = $pdo->prepare('INSERT INTO reservations (customer_name, customer_phone, service_type, reservation_date, guest_count, notes) VALUES (?, ?, ?, ?, ?, ?)');
            $service_name = 'Gedung Serbaguna';
            $stmt->execute([$customer_name, $customer_phone, $service_name, $reservation_date, $guest_count, $notes]);
            $whatsapp_message = "Halo, saya ingin memesan layanan berikut:\n\nNama: {$customer_name}\nNomor WhatsApp: {$customer_phone}\nLayanan: {$service_name}\nTanggal: {$reservation_date}\nJumlah: {$guest_count}\nCatatan: " . ($notes !== '' ? $notes : '-') . "\n\nMohon konfirmasi reservasi saya.";
            $whatsapp_redirect_url = 'https://wa.me/6281238951714?text=' . rawurlencode($whatsapp_message);
            $reservation_message = 'Reservasi gedung berhasil dikirim. Pengelola akan menghubungi Anda untuk konfirmasi.';
        } catch (PDOException $e) {
            $reservation_message = 'Reservasi belum dapat dikirim. Silakan coba lagi.';
            $reservation_message_type = 'error';
        }
    }
}

try {
    // Get the single gedung
    $gedung = $pdo->query("SELECT * FROM gedung LIMIT 1")->fetch();
    
    if ($gedung) {
        // Get all facilities for this gedung
        $facilities = $pdo->query("SELECT * FROM gedung_facilities WHERE gedung_id = " . $gedung['id'] . " ORDER BY id ASC")->fetchAll();
    } else {
        $gedung = null;
        $facilities = [];
    }
} catch (PDOException $e) {
    $gedung = null;
    $facilities = [];
}

?>

<?php if ($whatsapp_redirect_url !== ''): ?>
    <script>window.location.href = <?php echo json_encode($whatsapp_redirect_url); ?>;</script>
<?php endif; ?>

<div class="page-content gedung-page">
    <div class="container gedung-container">
        <?php if ($gedung): ?>
            <!-- Gedung Header -->
            <div class="gedung-header">
                <div class="gedung-image">
                    <?php if ($gedung['image']): ?>
                        <img src="../uploads/<?php echo $gedung['image']; ?>" alt="<?php echo htmlspecialchars($gedung['name']); ?>">
                    <?php else: ?>
                        <div class="gedung-image-empty">
                            <i class="fas fa-image"></i> Tidak ada gambar
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="gedung-info">
                    <span class="gedung-eyebrow"><i class="fas fa-landmark"></i> Ruang acara dan pertemuan</span>
                    <h1><?php echo htmlspecialchars($gedung['name']); ?></h1>
                    <p>
                        <?php echo htmlspecialchars($gedung['description']); ?>
                    </p>
                    <div class="gedung-highlights">
                        <span><i class="fas fa-check-circle"></i> Fasilitas lengkap</span>
                        <span><i class="fas fa-layer-group"></i> <?php echo count($facilities); ?> fasilitas</span>
                        <span><i class="fas fa-money-bill-wave"></i> <?php echo !empty($gedung['price']) ? 'Rp ' . number_format($gedung['price'], 0, ',', '.') : 'Hubungi pengelola'; ?> / hari</span>
                    </div>
                </div>
            </div>
            
            <!-- Facilities Section -->
            <div class="facilities-section">
                <div class="facilities-heading">
                    <div>
                        <span class="section-kicker">Yang tersedia di dalamnya</span>
                        <h2>
                    <i class="fas fa-door-open"></i> Fasilitas Gedung (<?php echo count($facilities); ?>)
                        </h2>
                    </div>
                    <span class="included-label"><i class="fas fa-check"></i> Termasuk dalam sewa gedung</span>
                </div>
                
                <?php if (count(array_filter($facilities, fn($facility) => $facility['status'] === 'baik')) > 0): ?>
                    <div class="facilities-grid">
                        <?php foreach ($facilities as $facility): ?>
                            <div class="facility-card">
                                <?php if (!empty($facility['image'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($facility['image']); ?>" alt="<?php echo htmlspecialchars($facility['name']); ?>" class="facility-image">
                                <?php endif; ?>
                                <div class="facility-title-row">
                                    <h3>
                                        <i class="fas fa-check-circle"></i>
                                        <?php echo htmlspecialchars($facility['name']); ?>
                                    </h3>
                                    <span class="status-badge <?php echo $facility['status'] == 'baik' ? 'status-good' : 'status-bad'; ?>">
                                        <?php
                                        $status_text = [
                                            'baik' => 'Baik',
                                            'rusak' => 'Rusak'
                                        ];
                                        echo $status_text[$facility['status']];
                                        ?>
                                    </span>
                                </div>
                                
                                <?php if ($facility['description']): ?>
                                    <p class="facility-description">
                                        <?php echo htmlspecialchars($facility['description']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="facility-meta">
                                    <?php if ($facility['capacity']): ?>
                                        <div>
                                            <p>Jumlah Unit</p>
                                            <strong>
                                                <i class="fas fa-layer-group"></i> <?php echo $facility['capacity']; ?> unit
                                            </strong>
                                        </div>
                                    <?php endif; ?>
                                    
                                </div>
                                <p class="facility-included"><i class="fas fa-check-circle"></i> Siap digunakan</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; color: #666;">
                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 15px; display: block;"></i>
                        <p>Belum ada fasilitas yang terdaftar.</p>
                    </div>
                <?php endif; ?>
            </div>

            <section class="gedung-reservation" id="reservasi-gedung">
                <div class="gedung-reservation-copy">
                    <span class="section-kicker">Reservasi gedung</span>
                    <h2>Pesan gedung langsung di sini</h2>
                    <p>Tarif sewa gedung: <strong><?php echo !empty($gedung['price']) ? 'Rp ' . number_format($gedung['price'], 0, ',', '.') : 'Hubungi pengelola'; ?> per hari</strong>. Fasilitas yang tersedia sudah termasuk dalam sewa gedung.</p>
                </div>
                <?php if ($reservation_message !== ''): ?>
                    <div class="gedung-reservation-alert gedung-reservation-alert-<?php echo $reservation_message_type; ?>" role="status">
                        <i class="fas <?php echo $reservation_message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                        <?php echo htmlspecialchars($reservation_message); ?>
                    </div>
                <?php endif; ?>
                <?php if ($gedung): ?>
                    <form class="gedung-reservation-form" method="POST" action="gedung.php#reservasi-gedung">
                        <input type="hidden" name="action" value="create_gedung_reservation">
                        <div class="gedung-reservation-field"><label for="gedung_customer_name">Nama lengkap</label><input id="gedung_customer_name" name="customer_name" type="text" maxlength="120" required></div>
                        <div class="gedung-reservation-field"><label for="gedung_customer_phone">Nomor WhatsApp</label><input id="gedung_customer_phone" name="customer_phone" type="tel" maxlength="30" required></div>
                        <div class="gedung-reservation-field"><label for="gedung_reservation_date">Tanggal pemesanan</label><input id="gedung_reservation_date" name="reservation_date" type="date" min="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="gedung-reservation-field"><label for="gedung_guest_count">Jumlah unit/orang</label><input id="gedung_guest_count" name="guest_count" type="number" min="1" max="999" value="1" required></div>
                        <div class="gedung-reservation-field gedung-reservation-field-wide"><label for="gedung_notes">Catatan tambahan <span>(opsional)</span></label><textarea id="gedung_notes" name="notes" rows="3" maxlength="1000"></textarea></div>
                        <button class="gedung-reservation-submit" type="submit"><i class="fas fa-paper-plane"></i> Kirim Reservasi Gedung</button>
                    </form>
                <?php else: ?>
                    <div class="gedung-reservation-empty">Saat ini belum ada fasilitas yang tersedia untuk dipesan.</div>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-building" style="font-size: 4rem; color: #ccc; margin-bottom: 20px; display: block;"></i>
                <h2 style="color: #999;">Gedung Serbaguna Belum Tersedia</h2>
                <p style="color: #aaa;">Silakan kembali lagi nanti.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    body:has(.gedung-page) {
        background: url('../assets/images/wini.jpg') center / cover fixed no-repeat;
    }

    body:has(.gedung-page) main {
        max-width: none;
        padding: 0;
    }

    .gedung-page {
        background: rgba(244, 247, 249, 0.72);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        padding: 0 0 4rem;
    }

    .gedung-container {
        max-width: 1080px;
        width: min(100%, 1080px);
        margin: 0 auto;
        padding-top: 2rem;
    }

    .gedung-header {
        display: grid;
        grid-template-columns: minmax(0, 1.55fr) minmax(280px, 0.85fr);
        min-height: 360px;
        overflow: hidden;
        border-radius: 18px;
        background: #153b5b;
        box-shadow: 0 18px 40px rgba(21, 59, 91, 0.18);
    }

    .gedung-image {
        min-height: 360px;
        background: #dce6ea;
    }

    .gedung-image img,
    .gedung-image-empty {
        display: block;
        width: 100%;
        height: 100%;
        min-height: 360px;
        object-fit: cover;
    }

    .gedung-image-empty {
        display: grid;
        place-content: center;
        gap: 0.5rem;
        color: #607080;
        text-align: center;
    }

    .gedung-info {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 2.5rem;
        color: #fff;
        background: linear-gradient(145deg, #153b5b, #246b82);
    }

    .gedung-eyebrow,
    .section-kicker {
        color: #8fe0cf;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .gedung-eyebrow i {
        margin-right: 0.35rem;
    }

    .gedung-info h1 {
        margin: 0.7rem 0 0.8rem;
        color: #fff;
        font-size: clamp(2rem, 4vw, 3.2rem);
        line-height: 1.05;
    }

    .gedung-info > p {
        margin: 0;
        color: rgba(255, 255, 255, 0.82);
        line-height: 1.7;
    }

    .gedung-highlights {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1.5rem;
    }

    .gedung-highlights span,
    .included-label {
        padding: 0.55rem 0.75rem;
        border: 1px solid rgba(143, 224, 207, 0.3);
        border-radius: 999px;
        color: #dffaf4;
        font-size: 0.78rem;
    }

    .gedung-highlights i,
    .included-label i {
        margin-right: 0.3rem;
        color: #8fe0cf;
    }

    .facilities-section {
        padding-top: 3.5rem;
    }

    .facilities-heading {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.4rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #cbd9df;
    }

    .facilities-heading h2 {
        margin: 0.35rem 0 0;
        color: #153b5b;
        font-size: clamp(1.45rem, 3vw, 2rem);
    }

    .facilities-heading h2 i {
        margin-right: 0.4rem;
        color: #21827c;
    }

    .included-label {
        border-color: #bde7dd;
        color: #176c78;
        white-space: nowrap;
    }

    .facilities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.25rem;
        animation: fadeIn 0.5s ease;
    }

    .facility-card {
        overflow: hidden;
        padding: 0 1.25rem 1.25rem;
        border: 1px solid #e1e8ed;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 8px 20px rgba(21, 59, 91, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .facility-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 16px 28px rgba(21, 59, 91, 0.14);
    }

    .facility-image {
        display: block;
        width: calc(100% + 2.5rem);
        height: 180px;
        margin: 0 -1.25rem 1.1rem;
        object-fit: cover;
    }

    .facility-title-row {
        display: flex;
        align-items: start;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .facility-title-row h3 {
        margin: 0;
        color: #153b5b;
        font-size: 1.1rem;
    }

    .facility-title-row h3 i {
        margin-right: 0.35rem;
        color: #28a745;
    }

    .status-badge {
        flex-shrink: 0;
        padding: 0.32rem 0.6rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .status-good {
        background: #d4edda;
        color: #155724;
    }

    .status-bad {
        background: #f8d7da;
        color: #721c24;
    }

    .facility-description {
        min-height: 3.2rem;
        margin: 0.85rem 0 0;
        color: #607080;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .facility-meta {
        margin-top: 1rem;
        padding-top: 0.8rem;
        border-top: 1px solid #e7edf0;
    }

    .facility-meta p {
        margin: 0 0 0.3rem;
        color: #8a98a8;
        font-size: 0.75rem;
    }

    .facility-meta strong {
        color: #153b5b;
        font-size: 1rem;
    }

    .facility-meta strong i {
        margin-right: 0.3rem;
        color: #21827c;
    }

    .facility-included {
        margin: 1rem 0 0;
        padding-top: 0.8rem;
        border-top: 1px solid #e7edf0;
        color: #21827c;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .facility-included i {
        margin-right: 0.25rem;
    }

    .gedung-reservation {
        margin: 3rem 0 2rem;
        padding: 2.5rem;
        border: 1px solid rgba(220, 232, 232, 0.9);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.78);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        box-shadow: 0 12px 28px rgba(21, 59, 91, 0.08);
    }

    .gedung-reservation-copy h2 {
        margin: 0.35rem 0 0.5rem;
        color: #153b5b;
        font-size: clamp(1.6rem, 3vw, 2.3rem);
    }

    .gedung-reservation-copy p {
        margin: 0 0 1.4rem;
        color: #607080;
    }

    .gedung-reservation-form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        padding: 1.5rem;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.68);
    }

    .gedung-reservation-field { display: grid; gap: 0.4rem; }
    .gedung-reservation-field-wide { grid-column: 1 / -1; }
    .gedung-reservation-field label { color: #153b5b; font-size: 0.85rem; font-weight: 700; }
    .gedung-reservation-field label span { color: #8a98a8; font-weight: 400; }
    .gedung-reservation-field input,
    .gedung-reservation-field select,
    .gedung-reservation-field textarea {
        width: 100%;
        padding: 0.75rem 0.85rem;
        border: 1px solid #cbd9df;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.9);
        color: #273b4d;
        font: inherit;
    }
    .gedung-reservation-field textarea { resize: vertical; }
    .gedung-reservation-submit {
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
    .gedung-reservation-submit:hover { background: #21827c; }
    .gedung-reservation-alert,
    .gedung-reservation-empty { margin-bottom: 1rem; padding: 0.85rem 1rem; border-radius: 6px; }
    .gedung-reservation-alert { display: flex; align-items: center; gap: 0.55rem; font-weight: 600; }
    .gedung-reservation-alert-success { background: #e4f5ed; color: #176c4c; }
    .gedung-reservation-alert-error { background: #fdebea; color: #9a3030; }
    .gedung-reservation-empty { background: rgba(255, 243, 205, 0.75); color: #856404; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 760px) {
        .gedung-container {
            padding: 1rem;
        }

        .gedung-reservation {
            padding: 1.25rem;
        }

        .gedung-reservation-form {
            grid-template-columns: 1fr;
            padding: 1rem;
        }

        .gedung-reservation-field-wide,
        .gedung-reservation-submit {
            grid-column: auto;
        }

        .gedung-header {
            display: block;
        }

        .gedung-image,
        .gedung-image img,
        .gedung-image-empty {
            min-height: 250px;
            height: 250px;
        }

        .gedung-info {
            padding: 1.75rem;
        }

        .facilities-heading {
            align-items: start;
            flex-direction: column;
        }

        .included-label {
            white-space: normal;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>
