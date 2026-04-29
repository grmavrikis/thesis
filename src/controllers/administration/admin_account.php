<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_ACCOUNT, $seoUrl->generate('administration/admin_account.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$sql = "SELECT u.*, d.*
        FROM user u
        JOIN dietitian d ON u.user_id = d.user_id
        WHERE d.dietitian_id = :admin_id";
$account = $db->query($sql, ['admin_id' => $auth->getUser()['admin_id']])->fetch();
