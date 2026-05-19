<?php

declare(strict_types=1);

function site_render_gtag(): void
{
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-6Y2YB8KS0F"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-6Y2YB8KS0F');
    </script>
    <?php
}
