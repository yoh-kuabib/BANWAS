<?php
// Close the main tag that was opened in header
?>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <!-- Location Map -->
            <div class="footer-location">
                <h4>
                    <i class="fas fa-location-dot"></i> Lokasi PLBN Wini
                </h4>
                <div class="footer-map-frame">
                    <iframe
                        src="https://www.google.com/maps?q=PLBN+Wini,+Insana,+Timor+Tengah+Utara&output=embed"
                        title="Lokasi PLBN Wini di Google Maps"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen></iframe>
                </div>
                <a class="footer-map-link" href="https://www.google.com/maps/search/?api=1&query=PLBN+Wini%2C+Insana%2C+Timor+Tengah+Utara" target="_blank" rel="noopener">
                    <i class="fas fa-diamond-turn-right"></i>
                    <span>Buka petunjuk arah di Google Maps</span>
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>

            <!-- Contact Info -->
            <div class="footer-contact">
                <h4>
                    <i class="fas fa-address-card"></i> Kontak
                </h4>
                <?php if (isset($settings) && $settings): ?>
                    <ul>
                        <?php if (isset($settings['phone']) && $settings['phone']): ?>
                        <li>
                            <i class="fab fa-whatsapp"></i>
                            <a href="https://wa.me/6281238951714" target="_blank" rel="noopener">
                                +62 812-3895-1714
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($settings['email']) && $settings['email']): ?>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>">
                                <?php echo htmlspecialchars($settings['email']); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <div class="social-links" aria-label="Media sosial PLBN Wini">
                        <a href="#" aria-label="Facebook" title="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" aria-label="Instagram" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="YouTube" title="YouTube"><i class="fab fa-youtube"></i></a>
                        <a href="#" aria-label="WhatsApp" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    </div>
                <?php else: ?>
                    <ul>
                        <li>
                            <i class="fab fa-whatsapp"></i>
                            <a href="https://wa.me/6281238951714" target="_blank" rel="noopener">+62 812-3895-1714</a>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:info@plbnwini.go.id">info@plbnwini.go.id</a>
                        </li>
                    </ul>
                    <div class="social-links" aria-label="Media sosial PLBN Wini">
                        <a href="#" aria-label="Facebook" title="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" aria-label="Instagram" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="YouTube" title="YouTube"><i class="fab fa-youtube"></i></a>
                        <a href="#" aria-label="WhatsApp" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Operating Hours -->
            <div class="footer-hours-card">
                <div class="footer-hours-icon">
                    <i class="far fa-clock"></i>
                </div>
                <div class="footer-hours-content">
                    <strong>JAM OPERASIONAL</strong>
                    <p>Senin - Sabtu: pukul 07:30 - 17:00 WITA<br class="footer-hours-break">Minggu: pukul 09:00 - 17:00 WITA</p>
                </div>
            </div>

        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p>
                &copy; <?php echo date('Y'); ?> 
                <?php echo isset($settings['site_name']) ? htmlspecialchars($settings['site_name']) : 'PLBN Wini'; ?>.
                All rights reserved.
            </p>
            <p>
                <i class="fas fa-code"></i> Developed with <i class="fas fa-heart"></i> by 
                <a href="#">Team PKL PLBN WINI 2026</a>
            </p>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button onclick="scrollToTop()" id="backToTop" title="Kembali ke atas">
        <i class="fas fa-arrow-up"></i>
    </button>

    <style>
        /* Footer Styles */
        footer {
            background: #1a3a5c;
            color: #ecf0f1;
            padding: 3rem 2rem 1rem;
            margin-top: 2rem;
            text-align: left;
        }
        
        .footer-container {
            max-width: 1120px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(220px, 0.72fr) minmax(250px, 0.9fr);
            gap: 2.5rem;
            align-items: start;
        }

        .footer-location,
        .footer-contact {
            min-width: 0;
            text-align: left;
        }
        
        .footer-container h4 {
            color: #ffd700;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .footer-container h4 i {
            margin-right: 8px;
        }

        .footer-location h4 {
            width: min(100%, 430px);
            text-align: center;
        }
        
        .footer-map-link {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            width: 100%;
            max-width: 480px;
            margin-top: 0.75rem;
            color: #ffd700;
            line-height: 1.6;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .footer-map-link > i:first-child {
            flex: 0 0 auto;
            margin-top: 4px;
        }

        .footer-map-link > i:last-child {
            flex: 0 0 auto;
            margin-top: 5px;
            color: #ffd700;
            font-size: 0.75rem;
        }

        .footer-map-link:hover span {
            text-decoration: underline;
        }

        .footer-map-frame {
            width: min(100%, 430px);
            height: 165px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 10px;
            background: #d9e5e8;
            box-shadow: 0 8px 20px rgba(4, 23, 38, 0.18);
        }

        .footer-map-frame iframe {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .social-links {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 1.3rem;
        }

        .social-links a {
            color: #d7e3eb;
            font-size: 1.4rem;
            line-height: 1;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .social-links a:hover {
            color: #ffd700;
            transform: translateY(-2px);
        }
        
        .footer-links ul,
        .footer-contact ul,
        .footer-hours ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-links ul li,
        .footer-contact ul li,
        .footer-hours ul li {
            margin-bottom: 10px;
        }
        
        .footer-links ul li a {
            color: #ecf0f1;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.3s;
        }
        
        .footer-links ul li a:hover {
            opacity: 1;
            color: #ffd700;
        }
        
        .footer-links ul li a i {
            color: #ffd700;
            font-size: 10px;
            margin-right: 8px;
        }
        
        .footer-contact ul li {
            display: flex;
            align-items: flex-start;
        }
        
        .footer-contact ul li i {
            color: #ffd700;
            margin-right: 10px;
            margin-top: 4px;
            min-width: 20px;
        }

        .footer-contact .fa-whatsapp {
            font-size: 1.1rem;
        }
        
        .footer-contact ul li a {
            color: #ecf0f1;
            text-decoration: none;
        }
        
        .footer-contact ul li a:hover {
            color: #ffd700;
        }

        .footer-hours-card {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-top: 0;
            max-width: 430px;
        }

        .footer-hours-icon {
            display: grid;
            place-items: center;
            flex: 0 0 60px;
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: rgba(232, 244, 243, 0.92);
            color: #28796e;
            font-size: 1.55rem;
        }

        .footer-hours-content {
            min-width: 0;
        }

        .footer-hours-content strong {
            display: block;
            margin: 2px 0 7px;
            color: #ffd700;
            font-size: 0.95rem;
            letter-spacing: 0.03em;
        }

        .footer-hours-content p {
            margin: 0;
            color: #ecf0f1;
            font-size: 0.95rem;
            line-height: 1.55;
        }
        
        .footer-hours ul li {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        
        .footer-hours ul li span:last-child {
            color: #ffd700;
        }
        
        .emergency-info {
            margin-top: 1rem;
            padding: 0.5rem;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 5px;
            border-left: 3px solid #ffd700;
        }
        
        .emergency-info i {
            color: #ffd700;
            margin-right: 5px;
        }
        
        .emergency-info small {
            opacity: 0.8;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 2rem;
            padding-top: 1.5rem;
            text-align: center;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }
        
        .footer-bottom p {
            margin-bottom: 0.5rem;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .footer-bottom p:last-child {
            opacity: 0.6;
            font-size: 0.8rem;
        }
        
        .footer-bottom .fa-heart {
            color: #ff6b6b;
        }
        
        .footer-bottom a {
            color: #ffd700;
            text-decoration: none;
        }
        
        .footer-bottom a:hover {
            text-decoration: underline;
        }

        /* Back to Top Button */
        #backToTop {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #ffd700;
            color: #1a3a5c;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            transition: all 0.3s;
            z-index: 999;
        }
        
        #backToTop:hover {
            transform: scale(1.1);
            background: #ffe44d;
        }

        @media (max-width: 768px) {
            .footer-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .footer-map-frame {
                height: 220px;
            }

            .footer-hours-card {
                max-width: none;
            }
            
            footer {
                padding: 2rem 1rem 1rem;
            }
            
            #backToTop {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
                bottom: 15px;
                right: 15px;
            }
        }
    </style>

    <script>
        // Back to top functionality
        window.onscroll = function() {
            var button = document.getElementById('backToTop');
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                button.style.display = 'block';
            } else {
                button.style.display = 'none';
            }
        };

        function scrollToTop() {
            window.scrollTo({top: 0, behavior: 'smooth'});
        }

        // Add smooth scrolling for all anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>

    <!-- jQuery (optional, untuk AJAX) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom Script -->
    <script src="<?php echo isset($is_admin) && $is_admin ? '../assets/js/script.js' : 'assets/js/script.js'; ?>"></script>
</body>
</html>