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
    <section class="content-section block account-page">
        <div class="mainwrapper">
            <div class="maincontent">
                <h1><?php echo HEADING_TITLE; ?></h1>
                <div class="content-box">
                    <?php require_once '../html/admin_account_menu.php'; ?>
                    <div class="page-content">
                        <div class="info-box">
                            <div class="account-container">
                                <div class="listing-toolbar">
                                    <div class="toolbar-left">
                                        <h2><?php echo HEADING_INVOICE_SETTINGS; ?></h2>
                                        <div class="edit-info">
                                            <a href="<?php echo $seoUrl->generate('administration/invoice_settings_edit.php'); ?>" class="edit-link">
                                                <?php echo TEXT_EDIT; ?> <img src="/images/edit-pencil.svg" alt="Edit" class="edit-icon" height="20px">
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="field-block">
                                    <p><span><?php echo LABEL_COMPANY_NAME; ?></span> <strong><?php echo $settings['company_name']; ?></strong></p>
                                    <p><span><?php echo LABEL_COMPANY_TITLE; ?></span> <strong><?php echo $settings['company_title']; ?></strong></p>
                                </div>
                                <div class="field-block">
                                    <p><span><?php echo LABEL_COMPANY_VAT_NUMBER; ?></span> <strong><?php echo $settings['vat_number']; ?></strong></p>
                                    <p><span><?php echo LABEL_COMPANY_TAX_OFFICE; ?></span> <strong><?php echo $settings['tax_office']; ?></strong></p>
                                </div>
                                <div class="field-block">
                                    <p><span><?php echo LABEL_COMPANY_ADDRESS_STREET; ?></span> <strong><?php echo $settings['address_street']; ?></strong></p>
                                    <p><span><?php echo LABEL_COMPANY_ADDRESS_STREET_NUMBER; ?></span> <strong><?php echo $settings['address_number']; ?></strong></p>
                                </div>
                                <div class="field-block">
                                    <p><span><?php echo LABEL_COMPANY_CITY; ?></span> <strong><?php echo $settings['city']; ?></strong></p>
                                    <p><span><?php echo LABEL_COMPANY_POSTAL_CODE; ?></span> <strong><?php echo $settings['postal_code']; ?></strong></p>
                                </div>
                                <div class="field-block">
                                    <p><span><?php echo LABEL_PHONE; ?></span> <strong><?php echo $settings['phone']; ?></strong></p>
                                    <p><span><?php echo LABEL_EMAIL; ?></span> <strong><?php echo $settings['email']; ?></strong></p>
                                </div>
                                <div class="field-block">
                                    <p>
                                        <span><?php echo LABEL_COMPANY_LOGO; ?></span>
                                        <?php
                                        if (!empty($settings['logo_path']))
                                        {
                                            echo "<img src=\"{$file_manager->getFileUrl($settings['logo_path'])}\" alt=\"Logo\" height=\"80\">";
                                        }
                                        else
                                        {
                                            echo "<strong>-</strong>";
                                        }
                                        ?>

                                    </p>
                                </div>
                                <div class="field-block">
                                    <p><span><?php echo LABEL_LAST_MODIFIED_DATE; ?></span> <span><?php echo date('d/m/Y H:i:s', strtotime($settings['last_modified_date'])); ?></span></p>
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
</body>

</html>