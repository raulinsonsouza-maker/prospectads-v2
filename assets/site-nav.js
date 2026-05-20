(function () {
    var toggle = document.querySelector('.nav__toggle');
    var menu = document.getElementById('site-nav-menu') || document.getElementById('nav-menu');
    if (!toggle || !menu) {
        return;
    }

    var mq = window.matchMedia('(max-width: 900px)');

    function setOpen(open) {
        document.body.classList.toggle('nav-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Fechar menu de navegação' : 'Abrir menu de navegação');
    }

    function closeMenu() {
        setOpen(false);
    }

    toggle.addEventListener('click', function () {
        setOpen(!document.body.classList.contains('nav-open'));
    });

    menu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (mq.matches) {
                closeMenu();
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMenu();
        }
    });

    mq.addEventListener('change', function () {
        if (!mq.matches) {
            closeMenu();
        }
    });
})();
