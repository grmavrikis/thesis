<?php
require_once '../includes/init.php';
require_once $pageController->getControllerFile();
?>
<!DOCTYPE html>
<html lang="<?php echo $language['code']; ?>">

<?php
require_once '../html/admin_head.php';
?>

<body>
    <?php
    require_once '../html/main_header.php';
    require_once '../html/breadcrumb.php';
    ?>
    <section class="content-section login-section">
        <div class="mainwrapper">
            <div class="flex-container-center">
                <div class="wrapper">
                    <div class="login-box">
                        <form id="adminLoginForm">
                            <h2><?php echo HEADING_TITLE; ?></h2>
                            <input type="text" id="adminUsername" name="adminUsername" placeholder="<?php echo TEXT_USERNAME; ?>" autocomplete="username" required>
                            <input type="password" id="adminPassword" name="adminPassword" placeholder="<?php echo TEXT_PASSWORD; ?>" autocomplete="new-password" required>

                            <button type="submit" class="submit-button"><?php echo TEXT_LOGIN; ?></button>

                            <p id="adminLoginMessage" class="loginMessage"></p>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </section>
    <?php
    require_once '../html/main_footer.php';
    ?>
</body>

</html>