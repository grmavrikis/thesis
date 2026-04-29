<?php
if (empty($auth->getUser()['client_id']))
{
    header('Location: ' . $seoUrl->generate('index.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(PAGE_TITLE, null);
