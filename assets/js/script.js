document.addEventListener('DOMContentLoaded', function () {
    // 1. HERO PROMO SLIDER OTOMATIS
    const slides = document.querySelectorAll('.hero-slide');
    if (slides.length > 1) {
        let currentSlide = 0;
        setInterval(function () {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, 4000);
    }

    // 2. KONFIRMASI POP-UP SAAT KLIK LOGOUT
    const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
    logoutLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            if (!confirm('Anda yakin ingin logout?')) {
                e.preventDefault();
            }
        });
    });

    // 3. ANIMASI MASUK OTOMATIS (MENU, DETAIL, CART, CHECKOUT, HISTORY)
    const animatedTargets = document.querySelectorAll(
        '.page-top-bar, ' +
        '.figma-section-bar, ' +
        '.category-list, ' +
        '.detail-card-grid, ' +
        '.product-card, ' +
        '.figma-cart-item, ' +
        '.checkout-card, ' +
        '.figma-summary-card, ' +
        '.figma-history-card, ' +
        '.qris-box, ' +
        '.figma-empty-box, ' +
        '.cafe-info-banner'
    );

    animatedTargets.forEach(function (el, index) {
        // Lewati jika sudah memakai animasi manual home-animate
        if (el.classList.contains('home-animate')) return;

        // Batasi delay maksimal agar halaman dengan banyak item tetap terasa cepat
        const delay = Math.min(index * 0.06, 0.55);
        el.style.animationDelay = delay + 's';
        el.classList.add('anim-fade-up');
    });
});