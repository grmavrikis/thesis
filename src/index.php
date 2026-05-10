<?php
require_once 'includes/init.php';
require_once $pageController->getControllerFile();
?>
<!DOCTYPE html>
<html lang="<?php echo $language['code']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? PAGE_TITLE; ?></title>
    <meta name="description" content="<?php echo $page_description ?? PAGE_DESCRIPTION; ?>">
    <?php require_once 'includes/js_texts.php'; ?>
    <script src="/js/scripts.js"></script>
    <link rel="stylesheet" href="/css/main.css">
</head>

<body>
    <?php
    require_once 'html/main_header.php';
    ?>
    <div class="main-banner">
        <div class="banner-content">
            <h1><?php echo TEXT_MAIN_TITLE; ?></h1>
            <p><?php echo TEXT_MAIN_DESCRIPTION; ?></p>
        </div>
    </div>

    <section class="content-section">
        <div class="mainwrapper">
            <div class="home-section">
                <div class="content-section-image">
                    <img src="/images/dietitian_info_placeholder.png" alt="<?php echo TEXT_IMAGE_ALT_DIETITIAN_INFO; ?>">
                </div>
                <div class="content-section-text">
                    <h2><?php echo TEXT_PHILOSOPHY; ?></h2>
                    <p><?php echo TEXT_SYSTEM_PHILOSOPHY; ?></p>
                    <p><?php echo TEXT_SYSTEM_PHILOSOPHY_EXTRA; ?></p>
                </div>
            </div>
        </div>
    </section>

    <?php
    require_once 'html/main_footer.php';
    ?>
</body>

</html>