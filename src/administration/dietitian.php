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
                                        <h2><?php echo TEXT_LIST_ITEMS; ?></h2>
                                    </div>
                                    <div class="toolbar-right">
                                        <div class="search-container">
                                            <input type="text" id="searchInput" placeholder="<?php echo TEXT_SEARCH; ?>..." class="search-input">
                                            <div id="searchTrigger" class="search-icon-wrapper" data-api-url="/ajax/get_dietitians.php">
                                                <img src="/images/search.svg" id="searchIcon" class="search-icon" alt="Search">
                                                <img src="/images/spinner.svg" id="spinner" class="search-icon" alt="Loading" style="display: none;">
                                            </div>
                                        </div>
                                        <button type="button" id="filterTrigger" class="action-btn btn-filter-icon" title="<?php echo TEXT_FILTERS; ?>">
                                            <img src="/images/filter.svg" class="filter-icon" alt="Filters">
                                        </button>
                                        <button type="button" id="btn-bulk-delete" class="action-btn btn-outline-danger" data-delete-url="/ajax/delete_dietitians.php" disabled>
                                            <?php echo TEXT_DELETE; ?> (<span id="selected-count">0</span>)
                                        </button>
                                        <a href="<?php echo $seoUrl->generate('administration/create_dietitian.php'); ?>" id="btn-add-new" class="action-btn btn-primary">
                                            <?php echo TEXT_ADD; ?>
                                        </a>

                                        <div id="filterPopup" class="filter-popup-container">
                                            <div class="filter-popup-content">
                                                <span id="closeFilter" class="close-popup">&times;</span>
                                                <h3 class="filter-title"><?php echo TEXT_FILTERS; ?></h3>
                                                <hr class="filter-divider">
                                                <div id="filterFields">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="table-toolbar-root"></div>
                                <div id="table-root"></div>
                                <div id="pagination-root"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initDynamicTable('table-root', '/ajax/get_dietitians.php');
        });
    </script>
    <?php
    require_once '../html/main_footer.php';
    ?>
</body>

</html>