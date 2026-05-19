    </main>

    <!-- ====== NEWSLETTER SECTION ====== -->
    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-wrapper">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="newsletter-content">
                            <h3>Subscribe to Our Newsletter</h3>
                            <p>Get the latest updates on new products, exclusive offers, and amazing deals delivered to your inbox.</p>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <form class="newsletter-form" id="newsletterForm">
                            <?= csrfField() ?>
                            <div class="input-group">
                                <input type="email" class="form-control" placeholder="Enter your email address" required>
                                <button type="submit" class="btn btn-newsletter">Subscribe <i class="fas fa-paper-plane ms-2"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== FOOTER ====== -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="<?= SITE_URL ?>" class="footer-brand">
                        <span class="brand-icon"><i class="fas fa-shopping-bag"></i></span>
                        <span class="brand-text"><?= SITE_NAME ?></span>
                    </a>
                    <p class="footer-desc"><?= SITE_TAGLINE ?>. We provide the best products at the most competitive prices with exceptional customer service.</p>
                    <div class="footer-social">
                        <a href="<?= getSetting('facebook_url') ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="<?= getSetting('instagram_url') ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="<?= getSetting('twitter_url') ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <a href="<?= getSetting('youtube_url') ?>" target="_blank"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="<?= SITE_URL ?>">Home</a></li>
                        <li><a href="<?= SITE_URL ?>/shop.php">Shop</a></li>
                        <li><a href="<?= SITE_URL ?>/shop.php?sort=newest">New Arrivals</a></li>
                        <li><a href="<?= SITE_URL ?>/shop.php?sort=deals">Hot Deals</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Categories</h4>
                    <ul class="footer-links">
                        <?php foreach (getCategories() as $cat): ?>
                        <li><a href="<?= SITE_URL ?>/shop.php?category=<?= $cat['slug'] ?>"><?= sanitizeInput($cat['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Customer Service</h4>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Shipping Info</a></li>
                        <li><a href="#">Returns & Exchanges</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <ul class="footer-contact">
                        <li><i class="fas fa-map-marker-alt"></i> <?= getSetting('site_address') ?></li>
                        <li><i class="fas fa-phone-alt"></i> <?= getSetting('site_phone') ?></li>
                        <li><i class="fas fa-envelope"></i> <?= getSetting('site_email') ?></li>
                    </ul>
                    <div class="payment-methods mt-3">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='25'%3E%3Crect width='40' height='25' rx='3' fill='%232d343f'/%3E%3Ctext x='20' y='17' text-anchor='middle' fill='%23fff' font-size='10' font-family='Arial'%3EVISA%3C/text%3E%3C/svg%3E" alt="Visa">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='25'%3E%3Crect width='40' height='25' rx='3' fill='%232d343f'/%3E%3Ctext x='20' y='17' text-anchor='middle' fill='%23fff' font-size='8' font-family='Arial'%3EMC%3C/text%3E%3C/svg%3E" alt="Mastercard">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='25'%3E%3Crect width='40' height='25' rx='3' fill='%232d343f'/%3E%3Ctext x='20' y='17' text-anchor='middle' fill='%23fff' font-size='8' font-family='Arial'%3EUPI%3C/text%3E%3C/svg%3E" alt="UPI">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='25'%3E%3Crect width='40' height='25' rx='3' fill='%232d343f'/%3E%3Ctext x='20' y='17' text-anchor='middle' fill='%23fff' font-size='8' font-family='Arial'%3ECOD%3C/text%3E%3C/svg%3E" alt="COD">
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top -->
    <button class="back-to-top" id="backToTop"><i class="fas fa-arrow-up"></i></button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
