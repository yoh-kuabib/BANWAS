<?php
session_start();
if (!isset($_SESSION['user_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    service_type VARCHAR(30) NOT NULL,
    reservation_date DATE NOT NULL,
    guest_count INT NOT NULL DEFAULT 1,
    notes TEXT,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reservation_id = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);

    if ($reservation_id && in_array($action, ['confirmed', 'cancelled'], true)) {
        $stmt = $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ?');
        $stmt->execute([$action, $reservation_id]);
        $_SESSION['message'] = $action === 'confirmed'
            ? 'Reservasi berhasil dikonfirmasi.'
            : 'Reservasi berhasil dibatalkan.';
        $_SESSION['message_type'] = 'success';
    } elseif ($reservation_id && $action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM reservations WHERE id = ?');
        $stmt->execute([$reservation_id]);
        $_SESSION['message'] = 'Reservasi berhasil dihapus.';
        $_SESSION['message_type'] = 'success';
    }

    header('Location: reservations.php');
    exit();
}

$reservations = $pdo->query('SELECT * FROM reservations ORDER BY created_at DESC')->fetchAll();

include '../includes/header.php';
?>

<div class="admin-content reservations-page">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-calendar-check"></i> Daftar Reservasi</h1>
            <p>Kelola permintaan pemesanan yang dikirim dari beranda.</p>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?>">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        </div>
    <?php endif; ?>

    <div class="reservation-summary">
        <span><strong><?php echo count($reservations); ?></strong> total reservasi</span>
        <span><strong><?php echo count(array_filter($reservations, fn($item) => $item['status'] === 'pending')); ?></strong> menunggu diproses</span>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="admin-table reservations-table">
                <thead>
                    <tr>
                        <th>Pemesan</th>
                        <th>Layanan</th>
                        <th>Tanggal</th>
                        <th>Jumlah</th>
                        <th>Catatan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reservations): ?>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($reservation['customer_name']); ?></strong>
                                    <small><?php echo htmlspecialchars($reservation['customer_phone']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($reservation['service_type']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($reservation['reservation_date'])); ?></td>
                                <td><?php echo (int) $reservation['guest_count']; ?></td>
                                <td class="reservation-notes"><?php echo $reservation['notes'] ? htmlspecialchars($reservation['notes']) : '-'; ?></td>
                                <td>
                                    <span class="reservation-status status-<?php echo htmlspecialchars($reservation['status']); ?>">
                                        <?php echo $reservation['status'] === 'pending' ? 'Menunggu' : ($reservation['status'] === 'confirmed' ? 'Dikonfirmasi' : 'Dibatalkan'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="reservation-actions">
                                        <?php if ($reservation['status'] === 'pending'): ?>
                                            <form method="POST">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <input type="hidden" name="action" value="confirmed">
                                                <button class="btn-action btn-confirm" type="submit" title="Konfirmasi"><i class="fas fa-check"></i></button>
                                            </form>
                                            <form method="POST" onsubmit="return confirm('Batalkan reservasi ini?');">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <input type="hidden" name="action" value="cancelled">
                                                <button class="btn-action btn-cancel" type="submit" title="Batalkan"><i class="fas fa-ban"></i></button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" onsubmit="return confirm('Hapus reservasi ini?');">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button class="btn-action btn-delete" type="submit" title="Hapus"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">Belum ada reservasi masuk.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .reservations-page .page-header p { margin: 0.35rem 0 0; color: #6b7b88; }
    .reservation-summary { display: flex; gap: 1.5rem; margin-bottom: 1.25rem; color: #536575; }
    .reservation-summary strong { color: #153b5b; font-size: 1.15rem; }
    .reservations-table small { display: block; margin-top: 0.25rem; color: #738391; }
    .reservation-notes { max-width: 230px; }
    .reservation-status { display: inline-block; padding: 0.35rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-confirmed { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
    .reservation-actions { display: flex; gap: 0.35rem; }
    .reservation-actions form { display: inline-flex; }
    .btn-confirm { background: #21827c; color: #fff; }
    .btn-cancel { background: #e1a11a; color: #fff; }
    @media (max-width: 760px) {
        .reservation-summary { flex-direction: column; gap: 0.35rem; }
        .reservations-table { min-width: 850px; }
    }
</style>

<?php include '../includes/footer.php'; ?>
