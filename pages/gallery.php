<?php
include '../includes/header.php';

try {
    // Ambil semua data galeri
    $stmt = $pdo->query("SELECT * FROM gallery ORDER BY id DESC");
    $galleries = $stmt->fetchAll();
} catch (PDOException $e) {
    $galleries = [];
}

// Get unique categories
$categories = [];
foreach ($galleries as $item) {
    if ($item['category']) {
        $categories[] = $item['category'];
    }
}
$categories = array_unique($categories);
?>

<div class="page-content">
    <div class="container">
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-header">
                <h2><i class="fas fa-filter"></i> Filter Galeri</h2>
                <button class="btn-filter-toggle" onclick="toggleFilter()">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div class="filter-body" id="filterBody">
                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="fas fa-folder"></i> Kategori</label>
                        <select id="categoryFilter" onchange="applyFilters()">
                            <option value="all">Semua</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo strtolower($category); ?>"><?php echo ucfirst($category); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Cari</label>
                        <input type="text" id="searchFilter" placeholder="Cari foto..." onkeyup="applyFilters()">
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Count -->
        <div class="results-count">
            <span id="resultCount"><?php echo count($galleries); ?></span> Foto ditemukan
        </div>

        <!-- Gallery Grid -->
        <div class="gallery-grid" id="galleryGrid">
            <?php if (count($galleries) > 0): ?>
                <?php foreach ($galleries as $item): ?>
                    <div class="gallery-item" 
                         data-category="<?php echo strtolower($item['category']); ?>"
                         data-title="<?php echo strtolower(htmlspecialchars($item['title'])); ?>">
                        <div class="gallery-image" onclick="openLightbox('<?php echo $item['image']; ?>', '<?php echo htmlspecialchars($item['title']); ?>')">
                            <?php 
                            $image_path = '../uploads/' . $item['image'];
                            if (file_exists($image_path)): 
                            ?>
                                <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                            <?php else: ?>
                                <img src="../assets/images/placeholder-image.jpg" alt="Image not found">
                            <?php endif; ?>
                            <div class="gallery-overlay">
                                <div class="gallery-overlay-content">
                                    <i class="fas fa-search-plus"></i>
                                    <span>Lihat</span>
                                </div>
                            </div>
                        </div>
                        <div class="gallery-info">
                            <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                            <?php if ($item['category']): ?>
                                <span class="category-badge">
                                    <i class="fas fa-folder"></i> <?php echo htmlspecialchars($item['category']); ?>
                                </span>
                            <?php endif; ?>
                            <small>
                                <i class="fas fa-calendar-alt"></i> 
                                <?php echo date('d M Y', strtotime($item['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-images"></i>
                    <h3>Belum Ada Foto</h3>
                    <p>Belum ada foto yang diupload ke galeri.</p>
                    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                        <a href="../admin/gallery.php" class="btn-upload">
                            <i class="fas fa-upload"></i> Upload Foto Sekarang
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Lightbox Modal -->
<div id="lightbox" class="lightbox" onclick="closeLightbox()">
    <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
    <img class="lightbox-content" id="lightboxImg">
    <div id="lightboxCaption" class="lightbox-caption"></div>
    <div class="lightbox-nav">
        <button class="lightbox-nav-btn" onclick="prevImage(event)">&#10094;</button>
        <button class="lightbox-nav-btn" onclick="nextImage(event)">&#10095;</button>
    </div>
</div>

<style>
    body:has(.gallery-grid) {
        background: url('../assets/images/wini.jpg') center / cover fixed no-repeat;
    }

    body:has(.gallery-grid) main {
        max-width: none;
        padding: 0;
    }

    .page-content {
        padding: 20px 0;
        background: rgba(248, 249, 250, 0.7);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
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
    
    .results-count {
        color: #666;
        font-size: 0.95rem;
        margin-bottom: 20px;
    }
    
    .results-count span {
        font-weight: 600;
        color: #1a3a5c;
    }
    
    /* Gallery Grid */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
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
        padding-top: 75%;
        background: #f0f0f0;
        overflow: hidden;
        cursor: pointer;
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
        background: rgba(0,0,0,0.6);
        opacity: 0;
        transition: opacity 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .gallery-item:hover .gallery-overlay {
        opacity: 1;
    }
    
    .gallery-overlay-content {
        color: white;
        text-align: center;
    }
    
    .gallery-overlay-content i {
        font-size: 2rem;
        display: block;
        margin-bottom: 5px;
    }
    
    .gallery-overlay-content span {
        font-size: 0.9rem;
    }
    
    .gallery-info {
        padding: 15px;
    }
    
    .gallery-info h4 {
        margin: 0 0 8px 0;
        color: #1a3a5c;
        font-size: 1rem;
    }
    
    .category-badge {
        display: inline-block;
        padding: 3px 12px;
        background: #f0f4f8;
        color: #1a3a5c;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .category-badge i {
        margin-right: 5px;
    }
    
    .gallery-info small {
        display: block;
        color: #999;
        font-size: 0.8rem;
    }
    
    .gallery-info small i {
        margin-right: 5px;
    }
    
    /* Lightbox */
    .lightbox {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .lightbox-content {
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 80%;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        border-radius: 8px;
        animation: zoomIn 0.3s ease;
        object-fit: contain;
    }
    
    @keyframes zoomIn {
        from { transform: translate(-50%, -50%) scale(0.8); }
        to { transform: translate(-50%, -50%) scale(1); }
    }
    
    .lightbox-close {
        position: absolute;
        top: 20px;
        right: 40px;
        color: white;
        font-size: 3rem;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.3s;
        z-index: 1;
    }
    
    .lightbox-close:hover {
        color: #ffd700;
    }
    
    .lightbox-caption {
        position: absolute;
        bottom: 80px;
        left: 0;
        right: 0;
        text-align: center;
        color: white;
        font-size: 1.2rem;
        padding: 15px;
        background: rgba(0,0,0,0.5);
        max-width: 80%;
        margin: 0 auto;
        border-radius: 8px;
    }
    
    .lightbox-nav {
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        transform: translateY(-50%);
        display: flex;
        justify-content: space-between;
        padding: 0 20px;
        pointer-events: none;
    }
    
    .lightbox-nav-btn {
        background: rgba(255,255,255,0.2);
        color: white;
        border: none;
        padding: 15px 20px;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        transition: all 0.3s;
        pointer-events: auto;
    }
    
    .lightbox-nav-btn:hover {
        background: rgba(255,255,255,0.3);
        transform: scale(1.1);
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
        margin-bottom: 20px;
    }
    
    .btn-upload {
        display: inline-block;
        background: #1a3a5c;
        color: white;
        padding: 10px 25px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-upload:hover {
        background: #2d6a8f;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(26, 58, 92, 0.3);
    }
    
    .btn-upload i {
        margin-right: 8px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .gallery-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .filter-row {
            grid-template-columns: 1fr;
        }
        
        .filter-section {
            padding: 15px;
        }
        
        .lightbox-content {
            max-width: 95%;
            max-height: 70%;
        }
        
        .lightbox-close {
            top: 15px;
            right: 20px;
            font-size: 2rem;
        }
        
        .lightbox-caption {
            font-size: 1rem;
            bottom: 70px;
        }
        
        .lightbox-nav-btn {
            padding: 10px 15px;
            font-size: 1rem;
        }
    }
    
    @media (max-width: 480px) {
        .gallery-grid {
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .gallery-info h4 {
            font-size: 0.85rem;
        }
        
        .lightbox-nav {
            padding: 0 10px;
        }
    }
</style>

<script>
    // Gallery data for navigation
    let galleryImages = [];
    let currentIndex = 0;
    
    <?php if (count($galleries) > 0): ?>
        galleryImages = [
            <?php foreach ($galleries as $item): ?>
                {
                    image: '<?php echo $item['image']; ?>',
                    title: '<?php echo addslashes(htmlspecialchars($item['title'])); ?>'
                },
            <?php endforeach; ?>
        ];
    <?php endif; ?>
    
    function toggleFilter() {
        var body = document.getElementById('filterBody');
        var toggle = document.querySelector('.btn-filter-toggle');
        body.classList.toggle('show');
        toggle.classList.toggle('active');
    }
    
    function applyFilters() {
        var categoryFilter = document.getElementById('categoryFilter').value;
        var searchFilter = document.getElementById('searchFilter').value.toLowerCase();
        
        var items = document.querySelectorAll('.gallery-item');
        var visibleCount = 0;
        
        items.forEach(function(item) {
            var category = item.getAttribute('data-category');
            var title = item.getAttribute('data-title');
            
            var categoryMatch = categoryFilter === 'all' || category === categoryFilter;
            var searchMatch = title.indexOf(searchFilter) > -1;
            
            if (categoryMatch && searchMatch) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        document.getElementById('resultCount').textContent = visibleCount;
    }
    
    function openLightbox(image, title) {
        var lightbox = document.getElementById('lightbox');
        var img = document.getElementById('lightboxImg');
        var caption = document.getElementById('lightboxCaption');
        
        // Find index
        currentIndex = galleryImages.findIndex(item => item.image === image);
        if (currentIndex === -1) currentIndex = 0;
        
        img.src = '../uploads/' + image;
        caption.textContent = title;
        lightbox.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    function closeLightbox() {
        var lightbox = document.getElementById('lightbox');
        lightbox.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function nextImage(e) {
        if (e) e.stopPropagation();
        if (galleryImages.length === 0) return;
        
        currentIndex = (currentIndex + 1) % galleryImages.length;
        var img = document.getElementById('lightboxImg');
        var caption = document.getElementById('lightboxCaption');
        
        img.src = '../uploads/' + galleryImages[currentIndex].image;
        caption.textContent = galleryImages[currentIndex].title;
    }
    
    function prevImage(e) {
        if (e) e.stopPropagation();
        if (galleryImages.length === 0) return;
        
        currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
        var img = document.getElementById('lightboxImg');
        var caption = document.getElementById('lightboxCaption');
        
        img.src = '../uploads/' + galleryImages[currentIndex].image;
        caption.textContent = galleryImages[currentIndex].title;
    }
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        var lightbox = document.getElementById('lightbox');
        if (lightbox.style.display === 'block') {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowRight') nextImage(e);
            if (e.key === 'ArrowLeft') prevImage(e);
        }
    });
    
    // Show filter by default on desktop
    if (window.innerWidth > 768) {
        document.getElementById('filterBody').classList.add('show');
    }
</script>

<?php include '../includes/footer.php'; ?>