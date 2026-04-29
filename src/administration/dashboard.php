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
                                        <h2><?php echo TEXT_LIST_TODAY_APPOINTMENTS; ?></h2>
                                    </div>
                                </div>
                                <div id="dashboard-appointments-root"></div>
                            </div>
                        </div>
                        <div class="info-box" style="margin-top: 30px;">
                            <div class="account-container">
                                <div class="listing-toolbar">
                                    <div class="toolbar-left">
                                        <h2><?php echo TEXT_LIST_PENDING_PLANS; ?></h2>
                                    </div>
                                </div>
                                <div id="dashboard-pending-plans-root"></div>
                            </div>
                        </div>
                        <div class="info-box" style="margin-top: 30px;">
                            <div class="account-container">
                                <div class="listing-toolbar">
                                    <div class="toolbar-left">
                                        <h2><?php echo TEXT_LIST_REVENUE; ?></h2>
                                    </div>
                                </div>
                                <div id="dashboard-revenue-root"></div>
                            </div>
                        </div>
                        <div class="info-box" style="margin-top: 30px;">
                            <div class="account-container">
                                <div class="listing-toolbar">
                                    <div class="toolbar-left">
                                        <h2><?php echo TEXT_LIST_RECENT_CUSTOMERS; ?></h2>
                                    </div>
                                    <div class="toolbar-right">
                                        <a href="<?php echo $seoUrl->generate('administration/clients.php'); ?>" class="btn-small"><?php echo TEXT_LIST_VIEW_ALLS_CUSTOMERS; ?></a>
                                    </div>
                                </div>
                                <div id="dashboard-latest-clients-root"></div>
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
        document.addEventListener('DOMContentLoaded', function() {
            var loggedInDietitian = <?php echo $auth->getUser()['admin_id']; ?>;
            initDashboardAppointments('dashboard-appointments-root', loggedInDietitian);
            initDashboardPendingPlans('dashboard-pending-plans-root', loggedInDietitian);
            initDashboardRevenue('dashboard-revenue-root');
            initDashboardLatestClients('dashboard-latest-clients-root');
        });
    </script>

</body>

</html>