<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_ACCOUNT, $seoUrl->generate('administration/invoice_settings.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$file_manager = new FileManager();

$sql = "SELECT i.*
        FROM invoice_settings i
        WHERE i.invoice_settings_id = 1";

$settings = $db->query($sql, [])->fetch();
