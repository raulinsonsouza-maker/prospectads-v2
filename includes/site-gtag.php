<?php

declare(strict_types=1);

function site_gtag_id(): string
{
    return 'G-6Y2YB8KS0F';
}

function site_gtag_markup(): string
{
    $id = site_gtag_id();
    ob_start();
    ?>
    <!-- Google tag (gtag.js) — carregamento adiado -->
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    (function () {
      var loaded = false;
      var id = <?= json_encode($id, JSON_UNESCAPED_UNICODE) ?>;
      function loadGtag() {
        if (loaded) return;
        loaded = true;
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
        s.onload = function () {
          gtag('js', new Date());
          gtag('config', id);
        };
        document.head.appendChild(s);
      }
      window.addEventListener('load', function () {
        setTimeout(loadGtag, 2000);
      }, { once: true });
      ['scroll', 'click', 'keydown'].forEach(function (evt) {
        document.addEventListener(evt, loadGtag, { once: true, passive: true });
      });
    })();
    </script>
    <?php
    return (string) ob_get_clean();
}

function site_render_gtag(): void
{
    echo site_gtag_markup();
}
