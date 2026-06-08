(function ($) {
    "use strict";

    // ── Spinner ──
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner();


    // ── WOW.js ──
    new WOW().init();


    // ── Sticky Navbar + Scroll-Shrink ──
    $(window).scroll(function () {
        var st = $(this).scrollTop();
        if (st > 300) {
            $('.sticky-top').addClass('shadow-sm scrolled').css('top', '0px');
        } else {
            $('.sticky-top').removeClass('shadow-sm scrolled').css('top', '-100px');
        }
    });


    // ── Scroll Progress Bar ──
    $(window).on('scroll', function () {
        var scrollTop = $(this).scrollTop();
        var docHeight = $(document).height() - $(window).height();
        var progress = (scrollTop / docHeight) * 100;
        $('.scroll-progress').css('width', progress + '%');
    });


    // ── Back to Top Button (instant) ──
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        window.scrollTo(0, 0);
        return false;
    });


    // ── Facts Counter ──
    $('[data-toggle="counter-up"]').counterUp({
        delay: 10,
        time: 2000
    });


    // ── Testimonials Carousel (with dots + FA nav icons) ──
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        autoplayTimeout: 5000,
        smartSpeed: 800,
        items: 1,
        dots: true,
        loop: true,
        nav: true,
        navText: [
            '<i class="fas fa-chevron-left"></i>',
            '<i class="fas fa-chevron-right"></i>'
        ]
    });



    // ── Form Real-Time Validation ──
    $(document).on('input blur', '.appointment-form-card input, .appointment-form-card textarea, .appointment-form-card select', function () {
        var $el = $(this);
        var val = $el.val().trim();
        var type = $el.attr('type');
        var required = $el.prop('required');

        if (!required && val === '') {
            $el.removeClass('is-valid is-invalid');
            return;
        }

        var valid = true;
        if (required && val === '') {
            valid = false;
        } else if (type === 'email') {
            valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
        } else if (type === 'tel') {
            valid = val.length >= 8;
        }

        $el.toggleClass('is-valid', valid).toggleClass('is-invalid', !valid);
    });


    // ── Form Submit with Toast ──
    $(document).on('submit', '.appointment-form-card form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var isValid = true;

        $form.find('[required]').each(function () {
            var val = $(this).val().trim();
            if (!val) {
                $(this).addClass('is-invalid').removeClass('is-valid');
                isValid = false;
            }
        });

        if (!isValid) return;

        // Show success toast
        showToast('<i class="fas fa-check-circle"></i>Demande envoyée avec succès !');

        // Reset form
        $form[0].reset();
        $form.find('.form-control').removeClass('is-valid is-invalid');
        $form.removeClass('was-validated');
    });


    // ── Newsletter Submit Feedback ──
    $(document).on('submit', '.footer form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $input = $form.find('input[type="email"]');
        var email = $input.val().trim();

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $input.addClass('is-invalid');
            return;
        }

        $input.removeClass('is-invalid');

        // Check if success message already exists
        var $success = $form.find('.newsletter-success');
        if ($success.length === 0) {
            $form.append('<div class="newsletter-success"><i class="fas fa-check-circle"></i>Merci ! Vous êtes inscrit.</div>');
            $success = $form.find('.newsletter-success');
        }

        $success.addClass('show');
        $input.val('');

        setTimeout(function () {
            $success.removeClass('show');
        }, 4000);
    });


    // ── Toast Helper ──
    function showToast(html) {
        var $toast = $('.toast-success');
        if ($toast.length === 0) {
            $('body').append('<div class="toast-success"></div>');
            $toast = $('.toast-success');
        }
        $toast.html(html);
        setTimeout(function () { $toast.addClass('show'); }, 50);
        setTimeout(function () { $toast.removeClass('show'); }, 4000);
    }


    // ── Stat Ticker Animated Flag ──
    $('[data-toggle="counter-up"]').closest('.stat-num').attr('data-animated', 'true');


})(jQuery);
