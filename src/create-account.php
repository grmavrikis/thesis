<?php
require_once 'includes/init.php';
require_once $pageController->getControllerFile();
?>
<!DOCTYPE html>
<html lang="<?php echo $language['code']; ?>">
<?php
require_once 'html/head.php';
?>

<body>
    <?php
    require_once 'html/main_header.php';
    require_once 'html/breadcrumb.php';
    ?>
    <script id="form-schema-json" type="application/json">
        <?php echo json_encode($form_schema, JSON_UNESCAPED_UNICODE); ?>
    </script>
    <?php
    $languagesAll = $languages->getLanguages();
    ?>
    <script id="languages-json" type="application/json">
        <?php echo json_encode($languagesAll, JSON_UNESCAPED_UNICODE); ?>
    </script>
    <section class="content-section block account-page">
        <div class="mainwrapper">
            <div class="maincontent">
                <h1><?php echo HEADING_TITLE; ?></h1>
                <div class="content-box">
                    <div class="page-content">
                        <div class="account-container">
                            <div class="info-box">
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
    require_once 'html/main_footer.php';
    ?>
</body>
<script>
    const schemaEl = document.getElementById('form-schema-json');
    const langsEl = document.getElementById('languages-json');

    if (schemaEl && langsEl) {
        const schema = JSON.parse(schemaEl.textContent);
        const languages = JSON.parse(langsEl.textContent);
        buildForm('form-container', '/ajax/create_account_submit.php', schema, languages);
    }
</script>

</html>