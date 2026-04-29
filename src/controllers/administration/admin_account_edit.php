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

$form_schema = [
    'mainFields' => [
        'admin_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $account['dietitian_id'] ?? null
        ],
        'user_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $account['user_id'] ?? null
        ],
        'firstname' => [
            'type'     => 'text',
            'label'    => LABEL_FIRSTNAME,
            'required' => true,
            'default'  => $account['first_name'] ?? null,
            'attributes' => [
                'autocomplete' => 'given-name'
            ]
        ],
        'lastname' => [
            'type'     => 'text',
            'label'    => LABEL_LASTNAME,
            'required' => true,
            'default'  => $account['last_name'] ?? null,
            'attributes' => [
                'autocomplete' => 'family-name'
            ]
        ],
        'email' => [
            'type'     => 'text',
            'label'    => LABEL_EMAIL,
            'required' => true,
            'default'  => $account['email'] ?? null,
            'attributes' => [
                'autocomplete' => 'email'
            ]
        ],
        'phone' => [
            'type'     => 'tel',
            'label'    => LABEL_PHONE,
            'required' => true,
            'default'  => $account['phone'] ?? null,
            'attributes' => [
                'autocomplete' => 'tel'
            ]
        ],
        'username' => [
            'type'     => 'text',
            'label'    => LABEL_USERNAME,
            'required' => true,
            'default'  => $account['username'] ?? null,
            'attributes' => [
                'autocomplete' => 'username'
            ]
        ],
        'password' => [
            'type'     => 'password',
            'label'    => LABEL_NEW_PASSWORD,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'new-password'
            ]
        ],
        'confirm_password' => [
            'type'     => 'password',
            'label'    => LABEL_CONFIRM_NEW_PASSWORD,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'new-password'
            ]
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/admin_account.php')
];
