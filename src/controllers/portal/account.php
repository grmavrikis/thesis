<?php
if (empty($auth->getUser()['client_id']))
{
    header('Location: index.php');
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_ACCOUNT, $seoUrl->generate('portal/account.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$sql = "SELECT u.*, c.*
        FROM user u
        JOIN client c ON u.user_id = c.user_id
        WHERE c.client_id = :client_id";
$account = $db->query($sql, ['client_id' => $auth->getUser()['client_id']])->fetch();

$gender_options = [
    'M' => TEXT_MALE,
    'F' => TEXT_FEMALE,
    'O' => TEXT_OTHER
];
