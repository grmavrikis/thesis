<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

if (!$_GET['id'] || !is_numeric($_GET['id']))
{
    header('Location: ' . $seoUrl->generate('administration/dietitian.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_DIETITIANS, $seoUrl->generate('administration/dietitian.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$sql = "SELECT * FROM dietitian d
        JOIN user u ON d.user_id = u.user_id
        WHERE d.dietitian_id = ?";

$dietitian = $db->query($sql, [$_GET['id']])->fetch();

if (!$dietitian)
{
    header("HTTP/1.0 404 Not Found");
    exit();
}

$form_schema = [
    'mainFields' => [
        'dietitian_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $dietitian['dietitian_id']
        ],
        'user_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $dietitian['user_id']
        ],
        'firstname' => [
            'type'     => 'text',
            'label'    => LABEL_FIRSTNAME,
            'required' => true,
            'default'  => $dietitian['first_name']
        ],
        'lastname' => [
            'type'     => 'text',
            'label'    => LABEL_LASTNAME,
            'required' => true,
            'default'  => $dietitian['last_name']
        ],
        'email' => [
            'type'     => 'email',
            'label'    => LABEL_EMAIL,
            'required' => true,
            'default'  => $dietitian['email']
        ],
        'phone' => [
            'type'     => 'tel',
            'label'    => LABEL_PHONE,
            'required' => true,
            'default'  => $dietitian['phone']
        ],
        'username' => [
            'type'     => 'text',
            'label'    => LABEL_USERNAME,
            'required' => true,
            'default'  => $dietitian['username']
        ],
        'password' => [
            'type'     => 'password',
            'label'    => LABEL_NEW_PASSWORD,
            'required' => false,
            'default'  => ''
        ],
        'confirm_password' => [
            'type'     => 'password',
            'label'    => LABEL_CONFIRM_NEW_PASSWORD,
            'required' => false,
            'default'  => ''
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/dietitian.php'),
    'creation_date' => (new DateTime($dietitian['creation_date']))->format('d/m/Y H:i:s'),
    'last_modified_date' => (new DateTime($dietitian['last_modified_date']))->format('d/m/Y H:i:s')
];
