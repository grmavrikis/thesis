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
    <script id="form-schema-json" type="application/json">
        <?php echo json_encode($form_schema, JSON_UNESCAPED_UNICODE); ?>
    </script>
    <section class="content-section block account-page">
        <div class="mainwrapper">
            <div class="maincontent">
                <h1><?php echo HEADING_TITLE; ?></h1>
                <div class="content-box">
                    <?php require_once '../html/admin_account_menu.php'; ?>
                    <div class="page-content">
                        <div class="account-container">
                            <div class="info-box">
                                <div class="listing-toolbar">
                                    <h2><?php echo HEADING_EDIT_INVOICE; ?></h2>
                                </div>
                                <div id="form-container">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
    require_once '../html/main_footer.php';
    ?>
    <script>
        const schemaEl = document.getElementById('form-schema-json');

        if (schemaEl) {
            const schema = JSON.parse(schemaEl.textContent);
            buildForm('form-container', '', schema);
        }
    </script>
</body>

</html>