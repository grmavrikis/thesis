<?php
if (!empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/dashboard.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(PAGE_TITLE, null);
