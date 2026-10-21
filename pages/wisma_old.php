<?php
// Include header
include '../includes/header.php';

// Get all wisma data
try {
    $stmt = $pdo->query("SELECT * FROM wisma ORDER BY id DESC");
    $wismas = $stmt->fetchAll();
} catch (PDOException $e) {
    $wismas = [];
}
?>

<div class="page-content">
    <div class="container">
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-header">
                <h2><i class="fas fa-filter"></i> Filter Wisma</h2>
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
                            <option value="booked">Dipesan</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Cari</label>
                        <input type="text" id="searchFilter" placeholder="Cari wisma..." onkeyup="applyFilters()">
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
            <span id="resultCount"><?php echo count($wismas); ?></span> Wisma ditemukan
        </div>

        <!-- Wisma Grid -->
        <div class="wisma-grid" id="wismaGrid">
            <?php if (count($wismas) > 0): ?>
                <?php foreach ($wismas as $wisma): ?>
                    <div class="wisma-card" 
                         data-status="<?php echo $wisma['status']; ?>"
                         data-title="<?php echo strtolower(htmlspecialchars($wisma['title'])); ?>"
                         data-price="<?php echo $wisma['price']; ?>"
                         data-date="<?php echo $wisma['created_at']; ?>">
                        <div class="wisma-image">
                            <?php if ($wisma['image'] && file_exists('../uploads/' . $wisma['image'])): ?>
                                <img src="../uploads/<?php echo $wisma['image']; ?>" alt="<?php echo htmlspecialchars($wisma['title']); ?>">
                            <?php else: ?>
                                <img src="../assets/images/placeholder.jpg" alt="No Image">
                            <?php endif; ?>
                            <div class="wisma-status status-<?php echo $wisma['status']; ?>">
                                <?php echo $wisma['status'] == 'available' ? 'Tersedia' : 'Dipesan'; ?>
                            </div>
                            <?php if ($wisma['price']): ?>
                                <div class="wisma-price-badge">
                                    Rp <?php echo number_format($wisma['price'], 0, ',', '.'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="wisma-body">
                            <h3><?php echo htmlspecialchars($wisma['title']); ?></h3>
                            <p class="wisma-description">
                                <?php echo htmlspecialchars(substr($wisma['description'], 0, 120)) . (strlen($wisma['description']) > 120 ? '...' : ''); ?>
                            </p>
                            <?php if ($wisma['facilities']): ?>
                                <div class="wisma-facilities">
                                    <?php 
                                    $facilities = array_map('trim', explode(',', $wisma['facilities']));
                                    foreach ($facilities as $facility): 
                                    ?>
                                        <span class="facility-tag">
                                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($facility); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="wisma-footer">
                                <span class="wisma-date">
                                    <i class="fas fa-calendar-alt"></i> 
                                    <?php echo date('d M Y', strtotime($wisma['created_at'])); ?>
                                </span>
                                <a href="wisma-detail.php?id=<?php echo $wisma['id']; ?>" class="btn-detail">
                                    Lihat Detail <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-hotel"></i>
                    <h3>Belum Ada Wisma</h3>
                    <p>Belum ada data wisma yang tersedia saat ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .page-content {
        padding: 20px 0;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* Filter Section */
    .filter-section {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 25px;
    }
    
    .filter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }
    
    .filter-header h2 {
        font-size: 1.1rem;
        color: #1a3a5c;
        margin: 0;
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
        margin-top: 15px;
        display: none;
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
        padding: 8px 12px;
        border: 2px solid #e1e5ea;
        border-radius: 8px;
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
    
    /* Results Count */
    .results-count {
        color: #666;
        font-size: 0.95rem;
        margin-bottom: 20px;
    }
    
    .results-count span {
        font-weight: 600;
        color: #1a3a5c;
    }
    
    /* Wisma Grid */
    .wisma-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
    }
    
    .wisma-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: all 0.3s;
    }
    
    .wisma-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    
    .wisma-image {
        position: relative;
        padding-top: 60%;
        background: #f0f0f0;
        overflow: hidden;
    }
    
    .wisma-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }
    
    .wisma-card:hover .wisma-image img {
        transform: scale(1.05);
    }
    
    .wisma-status {
        position: absolute;
        top: 15px;
        left: 15px;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        color: white;
        z-index: 1;
    }
    
    .status-available {
        background: #28a745;
    }
    
    .status-booked {
        background: #dc3545;
    }
    
    .wisma-price-badge {
        position: absolute;
        bottom: 15px;
        right: 15px;
        background: rgba(26, 58, 92, 0.9);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
        z-index: 1;
    }
    
    .wisma-body {
        padding: 20px;
    }
    
    .wisma-body h3 {
        margin: 0 0 10px 0;
        color: #1a3a5c;
        font-size: 1.2rem;
    }
    
    .wisma-description {
        color: #666;
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    
    .wisma-facilities {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 15px;
    }
    
    .facility-tag {
        background: #f0f4f8;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        color: #333;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .facility-tag i {
        color: #28a745;
        font-size: 0.7rem;
    }
    
    .wisma-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
    }
    
    .wisma-date {
        font-size: 0.8rem;
        color: #999;
    }
    
    .wisma-date i {
        margin-right: 5px;
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
    
    /* Empty State */
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
    
    /* Responsive */
    @media (max-width: 768px) {
        .wisma-grid {
            grid-template-columns: 1fr;
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
    // Toggle filter
    function toggleFilter() {
        var body = document.getElementById('filterBody');
        var toggle = document.querySelector('.btn-filter-toggle');
        body.classList.toggle('show');
        toggle.classList.toggle('active');
    }
    
    // Apply filters
    function applyFilters() {
        var statusFilter = document.getElementById('statusFilter').value;
        var searchFilter = document.getElementById('searchFilter').value.toLowerCase();
        var sortFilter = document.getElementById('sortFilter').value;
        
        var cards = document.querySelectorAll('.wisma-card');
        var visibleCount = 0;
        
        // Create array for sorting
        var cardArray = Array.from(cards);
        
        // Filter
        cardArray.forEach(function(card) {
            var status = card.getAttribute('data-status');
            var title = card.getAttribute('data-title');
            
            var statusMatch = statusFilter === 'all' || status === statusFilter;
            var searchMatch = title.indexOf(searchFilter) > -1;
            
            if (statusMatch && searchMatch) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Update count
        document.getElementById('resultCount').textContent = visibleCount;
        
        // Sort
        var grid = document.getElementById('wismaGrid');
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
        
        // Re-append sorted cards
        sortedCards.forEach(function(card) {
            grid.appendChild(card);
        });
    }
    
    // Show filter by default on desktop
    if (window.innerWidth > 768) {
        document.getElementById('filterBody').classList.add('show');
    }
</script>

<?php include '../includes/footer.php'; ?>