<?php
require_once 'config/database.php';

$home_defaults = [
    'hero_kicker' => 'Pos Lintas Batas Negara',
    'hero_title' => 'Selamat Datang di PLBN Wini',
    'hero_description' => 'PLBN Wini hadir sebagai pintu gerbang perbatasan negara yang representatif dan tertata, menyediakan pelayanan lintas batas serta mendukung berbagai kebutuhan perjalanan dan kegiatan masyarakat di kawasan perbatasan Indonesia–Timor Leste.',
    'hero_button_text' => 'Jelajahi Layanan',
    'about_kicker' => 'Tentang PLBN Wini',
    'about_title' => 'Gerbang perbatasan yang siap melayani perjalanan Anda',
    'about_description' => 'PLBN Wini menghadirkan layanan perbatasan, akomodasi, ruang kegiatan, dan kebutuhan usaha dalam satu kawasan yang nyaman dan tertata.',
    'location_title' => 'Lokasi Strategis',
    'location_description' => 'Kecamatan Insana Utara, Kabupaten Timor Tengah Utara, NTT',
    'background_image' => 'wini.jpg'
];

try {
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
    $home_settings = $pdo->query('SELECT * FROM home_settings ORDER BY id LIMIT 1')->fetch();
    if (!$home_settings) {
        $stmt = $pdo->prepare('INSERT INTO home_settings (hero_kicker, hero_title, hero_description, hero_button_text, about_kicker, about_title, about_description, location_title, location_description, background_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute(array_values($home_defaults));
        $home_settings = $home_defaults;
    }
} catch (PDOException $e) {
    $home_settings = $home_defaults;
}

include 'includes/header.php';

?>

<div class="home-hero">
    <div class="home-hero-content">
        <span class="home-kicker"><?php echo htmlspecialchars($home_settings['hero_kicker']); ?></span>
        <h1><?php echo htmlspecialchars($home_settings['hero_title']); ?></h1>
        <p><?php echo htmlspecialchars($home_settings['hero_description']); ?></p>
        <a href="#layanan" class="home-hero-button"><?php echo htmlspecialchars($home_settings['hero_button_text']); ?> <i class="fas fa-arrow-down"></i></a>
    </div>
</div>

<section class="home-services" id="layanan">
    <div class="section-heading">
        <span class="section-kicker">Layanan PLBN Wini</span>
        <h2>Semua kebutuhan Anda dalam satu tempat</h2>
        <p>Pilih layanan yang ingin Anda lihat lebih lanjut.</p>
    </div>
    <div class="home-service-grid">
    <article class="home-service-card">
        <div class="service-image">
            <img src="assets/images/wisma/wisma.jpeg" alt="Wisma Indonesia PLBN Wini">
        </div>
        <div class="service-icon"><i class="fas fa-hotel"></i></div>
        <h3>Wisma Indonesia</h3>
        <p>Wisma Indonesia PLBN Wini menyediakan penginapan yang bersih, aman, dan tenang di dalam kawasan PLBN Wini, menjadikannya pilihan tepat bagi tamu dinas maupun wisatawan yang membutuhkan tempat istirahat yang nyaman selama berada di kawasan perbatasan.</p>
        <a href="pages/wisma.php" class="home-service-link">Lihat Detail <i class="fas fa-arrow-right"></i></a>
    </article>
    <article class="home-service-card">
        <div class="service-image">
            <img src="assets/images/gsg/gsg.jpeg" alt="Gedung Serbaguna PLBN Wini">
        </div>
        <div class="service-icon"><i class="fas fa-building"></i></div>
        <h3>Gedung Serbaguna</h3>
        <p>Gedung Serbaguna PLBN Wini hadir sebagai ruang acara dan pertemuan yang nyaman dan representatif, dilengkapi kursi, videotron, dan sound sistem berkualitas untuk mendukung kelancaran berbagai jenis kegiatan, mulai dari rapat, seminar, hingga acara resmi.</p>
        <a href="pages/gedung.php" class="home-service-link">Lihat Detail <i class="fas fa-arrow-right"></i></a>
    </article>
    <article class="home-service-card">
        <div class="service-image">
            <img src="assets/images/kios/kios.jpg" alt="Kios Pasar PLBN Wini">
        </div>
        <div class="service-icon"><i class="fas fa-store"></i></div>
        <h3>Kios Pasar</h3>
        <p>Kios Pasar merupakan fasilitas ruang usaha di kawasan Pos Lintas Batas Negara (PLBN) Wini yang disewakan bagi pelaku UMKM untuk mendukung aktivitas ekonomi masyarakat perbatasan.</p>
        <a href="pages/kios.php" class="home-service-link">Lihat Detail <i class="fas fa-arrow-right"></i></a>
    </article>
    </div>
</section>

<section class="home-about">
    <div class="home-about-inner">
        <div class="home-about-copy">
            <span class="section-kicker"><?php echo htmlspecialchars($home_settings['about_kicker']); ?></span>
            <h2><?php echo htmlspecialchars($home_settings['about_title']); ?></h2>
            <p><?php echo htmlspecialchars($home_settings['about_description']); ?></p>
            <a href="pages/gallery.php" class="home-about-link">Lihat Galeri <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="home-about-note">
            <i class="fas fa-location-dot"></i>
            <strong><?php echo htmlspecialchars($home_settings['location_title']); ?></strong>
            <span><?php echo htmlspecialchars($home_settings['location_description']); ?></span>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<style>
    body:has(.home-hero) {
        background: url('assets/images/<?php echo htmlspecialchars($home_settings['background_image']); ?>') center / cover fixed no-repeat;
    }

    body:has(.home-hero) main {
        width: 100%;
        max-width: none;
        padding: 0;
    }

    .home-hero {
        position: relative;
        min-height: 520px;
        padding: 0.55rem 0.9rem;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.88);
        color: #123a5b;
        align-items: center;
        padding: 5rem 1.5rem 6rem;
        text-align: center;
        isolation: isolate;
        background: transparent;
        color: white;
    }

    .home-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: -1;
        background: linear-gradient(180deg, rgba(8, 31, 47, 0.28), rgba(8, 31, 47, 0.48));
    }

    .home-hero::after {
        display: none;
    }

    .home-hero-content {
        width: min(100%, 860px);
        margin: 0 auto;
    }

    .home-hero h1,
    .home-hero p {
        margin-left: auto;
        margin-right: auto;
    }

    .home-hero h1 {
        max-width: 760px;
        font-size: clamp(2.3rem, 5.4vw, 4.2rem);
    }

    .home-hero-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        margin-top: 0.5rem;
        padding: 0.85rem 1.2rem;
        border: 0;
        border-radius: 5px;
        background: #ffd447;
        color: #153b5b;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 8px 18px rgba(8, 31, 47, 0.2);
        transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
    }

    .home-hero-button:hover {
        background: #ffe477;
        color: #153b5b;
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(8, 31, 47, 0.28);
    }

    .home-services {
        position: relative;
        isolation: isolate;
        width: 100%;
        max-width: none;
        margin: 0 auto;
        padding: 5.5rem 2rem 6rem;
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
        overflow: hidden;
    }

    .home-services::before {
        display: none;
    }

    .home-services::after {
        content: '';
        position: absolute;
        inset: 0;
        z-index: -1;
        background: rgba(255, 255, 255, 0.62);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }

    .section-heading {
        max-width: 760px;
        margin: 0 auto 2.4rem;
        padding: 0;
        text-align: center;
    }

    .section-heading h2 {
        margin: 0.3rem 0;
        color: #153b5b;
        font-size: clamp(1.7rem, 3vw, 2.4rem);
        line-height: 1.15;
    }

    .section-heading p {
        color: #607080;
    }

    .home-service-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1.25rem;
        width: min(100%, 1180px);
        margin: 0 auto;
    }

    .home-service-card {
        display: flex;
        flex-direction: column;
        min-height: 395px;
        padding: 0 1.8rem 1.8rem;
        border: 1px solid #e1e8ed;
        border-radius: 8px;
        background: #153b5b;
        box-shadow: 0 10px 24px rgba(21, 59, 91, 0.08);
        text-align: left;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .service-image {
        height: 170px;
        margin: 0 -1.8rem 1.5rem;
        overflow: hidden;
        background: #dfeaec;
    }

    .service-image img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .home-service-card:hover .service-image img {
        transform: scale(1.05);
    }

    .service-icon {
        display: grid;
        place-items: center;
        width: 3rem;
        height: 3rem;
        margin-bottom: 1rem;
        border-radius: 50%;
        background: #e8f4f3;
        color: #21827c;
        font-size: 1.25rem;
    }

    .home-service-card h3 {
        margin-bottom: 0.5rem;
        color: #fff;
        font-size: 1.3rem;
    }

    .home-service-card p {
        min-height: 3.2rem;
        color: rgba(255, 255, 255, 0.82);
    }

    .home-service-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 16px 30px rgba(21, 59, 91, 0.14);
    }

    .home-service-link {
        margin-top: auto;
        padding-top: 1.2rem;
        color: #ffd447;
    }

    .home-about {
        position: relative;
        isolation: isolate;
        padding: 5.5rem 2rem;
        background: transparent;
    }

    .home-about::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: -1;
        background: rgba(255, 255, 255, 0.62);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }

    .home-about-inner {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(260px, 0.8fr);
        align-items: center;
        gap: 4rem;
        width: min(100%, 1040px);
        margin: 0 auto;
    }

    .home-about-copy h2 {
        margin: 0.45rem 0 0.8rem;
        color: #153b5b;
        font-size: clamp(1.8rem, 3vw, 2.6rem);
        line-height: 1.12;
    }

    .home-about-copy p {
        max-width: 650px;
        color: #607080;
        line-height: 1.7;
    }

    .home-about-link {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        margin-top: 1rem;
        color: #176c78;
        font-weight: 700;
        text-decoration: none;
    }

    .home-about-note {
        display: grid;
        gap: 0.55rem;
        padding: 2rem;
        border-left: 4px solid #ffd447;
        border-radius: 0 12px 12px 0;
        background: #153b5b;
        color: rgba(255, 255, 255, 0.82);
    }

    .home-about-note i {
        color: #21827c;
        font-size: 1.7rem;
    }

    .home-about-note strong {
        color: #fff;
        font-size: 1.15rem;
    }

    .home-about-note span {
        line-height: 1.55;
    }

    @media (max-width: 760px) {
        .home-hero {
            min-height: 560px;
            padding: 4rem 1.25rem 6rem;
        }

        .home-services {
            width: 100%;
            padding: 4rem 1rem 4.5rem;
        }

        .home-service-grid {
            grid-template-columns: 1fr;
        }

        .home-service-card {
            min-height: 0;
        }

        .home-about {
            padding: 4rem 1.25rem;
        }

        .home-about-inner {
            grid-template-columns: 1fr;
            gap: 2rem;
        }

    }
</style>