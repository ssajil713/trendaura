// ============================================
// ShopEase eCommerce - Main JavaScript
// ============================================

(function() {
    'use strict';

    // Toastr Configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-bottom-right',
        timeOut: 3000,
        extendedTimeOut: 1000,
    };

    // ====== HEADER SCROLL ======
    const header = document.querySelector('.main-header');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // ====== BACK TO TOP ======
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 400) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });
        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ====== MOBILE MENU ======
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navCloseBtn = document.getElementById('navCloseBtn');
    const mainNav = document.getElementById('mainNav');
    const navOverlay = document.getElementById('navOverlay');

    function openMenu() {
        if (mainNav) mainNav.classList.add('open');
        if (navOverlay) navOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeMenu() {
        if (mainNav) mainNav.classList.remove('open');
        if (navOverlay) navOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openMenu);
    if (navCloseBtn) navCloseBtn.addEventListener('click', closeMenu);
    if (navOverlay) navOverlay.addEventListener('click', closeMenu);

    // ====== HERO SLIDER ======
    const heroSliderEl = document.querySelector('.hero-slider');
    if (heroSliderEl) {
        new Swiper('.hero-slider', {
            loop: true,
            autoplay: { delay: 5000, disableOnInteraction: false },
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            effect: 'fade',
            fadeEffect: { crossFade: true },
        });
    }

    // ====== PRODUCT CAROUSELS ======
    document.querySelectorAll('.product-carousel').forEach(function(el) {
        new Swiper(el, {
            slidesPerView: 2,
            spaceBetween: 15,
            breakpoints: {
                576: { slidesPerView: 2 },
                768: { slidesPerView: 3 },
                992: { slidesPerView: 4 },
                1200: { slidesPerView: 4 }
            },
            navigation: {
                nextEl: el.querySelector('.swiper-button-next'),
                prevEl: el.querySelector('.swiper-button-prev'),
            },
        });
    });

    // ====== TESTIMONIAL CAROUSEL ======
    const testimonialSlider = document.querySelector('.testimonial-carousel');
    if (testimonialSlider) {
        new Swiper('.testimonial-carousel', {
            slidesPerView: 1,
            spaceBetween: 20,
            autoplay: { delay: 4000 },
            breakpoints: {
                768: { slidesPerView: 2 },
                992: { slidesPerView: 3 }
            },
        });
    }

    // ====== AJAX ADD TO CART ======
    $(document).on('click', '.btn-add-cart, .btn-add-cart-lg', function(e) {
        e.preventDefault();
        const btn = $(this);
        const productId = btn.data('product-id');
        const qty = btn.closest('.product-body, .product-info-card').find('.qty-input').val() || 1;

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: SITE_URL + '/api/cart.php',
            method: 'POST',
            data: {
                action: 'add',
                product_id: productId,
                quantity: qty,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#cartCount').text(res.cart_count);
                    toastr.success('Added to cart!');
                } else {
                    if (res.redirect) {
                        window.location.href = res.redirect;
                    } else {
                        toastr.error(res.error || 'Failed to add to cart');
                    }
                }
            },
            error: function() {
                toastr.error('Something went wrong');
            },
            complete: function() {
                btn.prop('disabled', false);
                const isLg = btn.hasClass('btn-add-cart-lg');
                btn.html(isLg ? '<i class="fas fa-shopping-cart me-2"></i> Add to Cart' : '<i class="fas fa-shopping-cart me-1"></i> Add to Cart');
            }
        });
    });

    // ====== AJAX WISHLIST TOGGLE ======
    $(document).on('click', '.btn-wishlist, .btn-wishlist-lg', function(e) {
        e.preventDefault();
        const btn = $(this);
        const productId = btn.data('product-id');

        $.ajax({
            url: SITE_URL + '/api/wishlist.php',
            method: 'POST',
            data: {
                action: 'toggle',
                product_id: productId,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    btn.toggleClass('active');
                    if (res.added) {
                        toastr.success('Added to wishlist!');
                        btn.find('i').removeClass('far').addClass('fas');
                    } else {
                        toastr.info('Removed from wishlist');
                        btn.find('i').removeClass('fas').addClass('far');
                    }
                } else if (res.redirect) {
                    window.location.href = res.redirect;
                } else {
                    toastr.error(res.error || 'Failed');
                }
            }
        });
    });

    // ====== QUANTITY SELECTOR ======
    $(document).on('click', '.qty-minus, .qty-plus', function() {
        const input = $(this).closest('.quantity-selector').find('.qty-input');
        let val = parseInt(input.val()) || 1;
        const max = parseInt(input.attr('max')) || 99;

        if ($(this).hasClass('qty-minus') && val > 1) {
            input.val(val - 1);
        } else if ($(this).hasClass('qty-plus') && val < max) {
            input.val(val + 1);
        }
        input.trigger('change');
    });

    // ====== CART QUANTITY UPDATE ======
    $(document).on('change', '.cart-qty-input', function() {
        const input = $(this);
        const row = input.closest('tr');
        const itemId = input.data('item-id');
        const qty = parseInt(input.val()) || 1;

        $.ajax({
            url: SITE_URL + '/api/cart.php',
            method: 'POST',
            data: {
                action: 'update',
                item_id: itemId,
                quantity: qty,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    row.find('.item-total').text(res.item_total);
                    $('.cart-subtotal').text(res.subtotal);
                    $('.cart-total').text(res.total);
                    $('#cartCount').text(res.cart_count);
                    if (res.shipping !== undefined) {
                        $('.cart-shipping').text(res.shipping_display);
                    }
                    if (res.discount !== undefined) {
                        $('.cart-discount').text(res.discount_display);
                    }
                } else {
                    toastr.error(res.error || 'Failed to update');
                    location.reload();
                }
            }
        });
    });

    // ====== REMOVE FROM CART ======
    $(document).on('click', '.btn-remove-cart', function(e) {
        e.preventDefault();
        const itemId = $(this).data('item-id');
        const row = $(this).closest('tr');

        $.ajax({
            url: SITE_URL + '/api/cart.php',
            method: 'POST',
            data: {
                action: 'remove',
                item_id: itemId,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    row.fadeOut(300, function() { $(this).remove(); });
                    $('.cart-subtotal').text(res.subtotal);
                    $('.cart-total').text(res.total);
                    $('#cartCount').text(res.cart_count);
                    if (res.cart_empty) {
                        location.reload();
                    }
                    toastr.info('Item removed');
                }
            }
        });
    });

    // ====== APPLY COUPON ======
    $(document).on('click', '.btn-apply-coupon', function() {
        const code = $('.coupon-input').val();
        if (!code) { toastr.warning('Enter coupon code'); return; }

        $.ajax({
            url: SITE_URL + '/api/coupon.php',
            method: 'POST',
            data: {
                code: code,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    toastr.success('Coupon applied!');
                    $('.cart-discount').text(res.discount_display);
                    $('.cart-total').text(res.total);
                    $('.coupon-input').val('');
                } else {
                    toastr.error(res.error);
                }
            }
        });
    });

    // ====== LIVE SEARCH ======
    let searchTimeout;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val().trim();
        const suggestions = $('#searchSuggestions');

        if (query.length < 2) {
            suggestions.removeClass('show').empty();
            return;
        }

        searchTimeout = setTimeout(function() {
            $.ajax({
                url: SITE_URL + '/api/search.php',
                method: 'GET',
                data: { q: query },
                dataType: 'json',
                success: function(res) {
                    suggestions.empty();
                    if (res.length > 0) {
                        res.forEach(function(product) {
                            const price = product.sale_price ? product.sale_price : product.regular_price;
                            const img = product.primary_image || 'assets/uploads/placeholder.svg';
                            suggestions.append(
                                '<a href="' + SITE_URL + '/product.php?slug=' + product.slug + '" class="search-suggestion-item">' +
                                    '<img src="' + SITE_URL + '/' + img + '" alt="' + product.name + '">' +
                                    '<div>' +
                                        '<div class="suggestion-name">' + product.name + '</div>' +
                                        '<div class="suggestion-price">' + formatCurrency(price) + '</div>' +
                                    '</div>' +
                                '</a>'
                            );
                        });
                        suggestions.addClass('show');
                    } else {
                        suggestions.removeClass('show');
                    }
                }
            });
        }, 300);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-group').length) {
            $('#searchSuggestions').removeClass('show');
        }
    });

    // ====== AJAX FILTERING FOR SHOP PAGE ======
    $(document).on('change', '.filter-checkbox, .filter-radio, .filter-select', function() {
        applyFilters();
    });

    $(document).on('click', '.btn-filter-price', function(e) {
        e.preventDefault();
        applyFilters();
    });

    function applyFilters() {
        const form = $('#filterForm');
        const data = form.serialize();

        $('#productsContainer').addClass('opacity-50');
        $('.loading-spinner').show();

        $.ajax({
            url: window.location.pathname,
            method: 'GET',
            data: data + '&ajax=1',
            dataType: 'json',
            success: function(res) {
                $('#productsContainer').html(res.html).removeClass('opacity-50');
                $('#paginationContainer').html(res.pagination);
                $('#resultCount').text(res.count);
                $('.loading-spinner').hide();
                history.pushState(null, '', res.url);
            },
            error: function() {
                location.reload();
            }
        });
    }

    // ====== VIEW TOGGLE ======
    $(document).on('click', '.view-toggle button', function() {
        const view = $(this).data('view');
        $('.view-toggle button').removeClass('active');
        $(this).addClass('active');
        if (view === 'list') {
            $('#productsContainer').addClass('products-list-view');
        } else {
            $('#productsContainer').removeClass('products-list-view');
        }
        $.cookie('product_view', view, { path: '/' });
    });

    // ====== NEWSLETTER ======
    $('#newsletterForm').on('submit', function(e) {
        e.preventDefault();
        const email = $(this).find('input[type="email"]').val();

        $.ajax({
            url: SITE_URL + '/api/newsletter.php',
            method: 'POST',
            data: {
                email: email,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    toastr.success('Subscribed successfully!');
                    $('#newsletterForm')[0].reset();
                } else {
                    toastr.error(res.error);
                }
            }
        });
    });

    // ====== REMOVE COUPON ======
    $(document).on('click', '.remove-coupon', function() {
        $.ajax({
            url: SITE_URL + '/api/coupon.php',
            method: 'POST',
            data: {
                action: 'remove',
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    location.reload();
                }
            }
        });
    });

})();

const SITE_URL = 'http://localhost/e-com-ai';

function formatCurrency(amount) {
    return '\u20B9 ' + parseInt(amount).toLocaleString('en-IN');
}
