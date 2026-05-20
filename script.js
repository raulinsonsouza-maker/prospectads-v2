// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        var href = this.getAttribute('href');
        if (!href || href === '#') {
            return;
        }
        var target = document.querySelector(href);
        if (target) {
            var headerOffset = 80;
            var elementPosition = target.getBoundingClientRect().top;
            var offsetPosition = elementPosition + window.pageYOffset - headerOffset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// Header scroll effect (classe CSS, sem escrita inline de estilo)
(function () {
    var header = document.querySelector('.header');
    if (!header) {
        return;
    }

    var ticking = false;

    function updateHeader() {
        header.classList.toggle('header--scrolled', window.pageYOffset > 100);
        ticking = false;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            requestAnimationFrame(updateHeader);
            ticking = true;
        }
    }, { passive: true });

    updateHeader();
})();

// Animações ao rolar — após idle/load para não competir com LCP
(function () {
    function initScrollAnimations() {
        var observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.service__card, .case__card, .platform__item, .differentiator__list li').forEach(function (el) {
            observer.observe(el);
        });
    }

    var schedule = window.requestIdleCallback
        ? function (fn) { requestIdleCallback(fn, { timeout: 2500 }); }
        : function (fn) { window.addEventListener('load', fn, { once: true }); };

    schedule(initScrollAnimations);
})();

// Modal functionality
var modalTriggers = document.querySelectorAll('.modal-trigger');
var modals = document.querySelectorAll('.modal');
var modalCloses = document.querySelectorAll('.modal__close');
var modalOverlays = document.querySelectorAll('.modal__overlay');

modalTriggers.forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
        e.preventDefault();
        var modalId = trigger.getAttribute('data-modal');
        var modal = document.getElementById('modal-' + modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    });
});

function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

modalCloses.forEach(function (close) {
    close.addEventListener('click', function () {
        var modal = close.closest('.modal');
        if (modal) {
            closeModal(modal);
        }
    });
});

modalOverlays.forEach(function (overlay) {
    overlay.addEventListener('click', function () {
        var modal = overlay.closest('.modal');
        if (modal) {
            closeModal(modal);
        }
    });
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        modals.forEach(function (modal) {
            if (modal.classList.contains('active')) {
                closeModal(modal);
            }
        });
    }
});

// FAQ Accordion
document.addEventListener('DOMContentLoaded', function () {
    var faqItems = document.querySelectorAll('.faq__item');
    faqItems.forEach(function (item) {
        var question = item.querySelector('.faq__question');
        if (!question) {
            return;
        }
        question.addEventListener('click', function () {
            var isActive = item.classList.contains('active');

            faqItems.forEach(function (faqItem) {
                faqItem.classList.remove('active');
            });

            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
});
