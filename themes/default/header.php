<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/themes/default/css/style.css">
    <link rel="stylesheet" href="/themes/default/css/footer.css">
    <title><?php echo $page_title; ?></title>
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <div class="logo">
                <a href="/index.php?page=home"><?php echo $site_name_logo; ?></a>
            </div>
            <?php echo $main_navigation_html; ?>
            <button class="mobile-nav-toggle" aria-controls="main-navigation" aria-expanded="false">
                <span class="sr-only">Menu</span>
                &#9776; <?php // Hamburger icon ?>
            </button>
        </div>
    </header>

    <div class="site-wrapper">
        <?php if ($show_sidebar): ?>
        <aside class="sidebar">
            <?php echo $sidebar_user_panel_html; ?>
            <?php echo $recent_news_sidebar_html; ?>
        </aside>
        <?php endif; ?>

        <main class="main-content">