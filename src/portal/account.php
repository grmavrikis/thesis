<?php
require_once '../includes/init.php';
require_once $pageController->getControllerFile();
?>
<!DOCTYPE html>
<html lang="<?php echo $language['code']; ?>">
<?php
require_once '../html/portal_head.php';
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
                    <?php require_once '../html/account_menu.php'; ?>
                    <div class="page-content">
                        <div class="info-box">
                            <div class="account-container">
                                <div class="listing-toolbar">
                                    <div class="toolbar-left">
                                        <h2><?php echo HEADING_MY_PROFILE; ?></h2>
                                        <div class="edit-info">
                                            <a href="<?php echo $seoUrl->generate('portal/account_edit.php'); ?>" class="edit-link">
                                                <?php echo TEXT_EDIT; ?> <img src="/images/edit-pencil.svg" alt="Edit" class="edit-icon" height="20px">
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="field-block">
                                    <p><span><?php echo LABEL_FIRSTNAME; ?></span> <strong><?php echo $account['first_name']; ?></strong></p>
                                    <p><span><?php echo LABEL_LASTNAME; ?></span> <strong><?php echo $account['last_name']; ?></strong></p>
                                </div>
                                <div class="field-block">
                                    <p><span><?php echo LABEL_USERNAME; ?></span> <strong><?php echo $account['username']; ?></strong></p>
                                    <p><span><?php echo LABEL_EMAIL; ?></span> <strong><?php echo $account['email']; ?></strong></p>
                                </div>
                                <div class="field-block">
                                    <p><span><?php echo LABEL_PHONE; ?></span> <strong><?php echo $account['phone']; ?></strong></p>
                                    <p><span><?php echo LABEL_DOB; ?></span> <strong><?php echo date('d/m/Y', strtotime($account['dob'])); ?></strong></p>
                                </div>
                                <div class="field-block">
                                    <p><span><?php echo LABEL_GENDER; ?></span> <strong><?php echo htmlspecialchars($gender_options[$account['gender']]); ?></strong></p>
                                </div>
                                <div class="field-block">
                                    <p><span><?php echo LABEL_CREATION_DATE; ?></span> <span><?php echo date('d/m/Y H:i:s', strtotime($account['creation_date'])); ?></span></p>
                                    <p><span><?php echo LABEL_LAST_MODIFIED_DATE; ?></span> <span><?php echo date('d/m/Y H:i:s', strtotime($account['last_modified_date'])); ?></span></p>
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