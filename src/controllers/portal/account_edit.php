<?php
if (empty($auth->getUser()['client_id']))
{
    header('Location: index.php');
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_ACCOUNT, $seoUrl->generate('portal/account.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$genderOptions = [
    'M' => TEXT_MALE,
    'F' => TEXT_FEMALE,
    'O' => TEXT_OTHER
];

// Prepare options for select field
$gender_options = [];
foreach ($genderOptions as $val => $text)
{
    $gender_options[] = [
        'value' => $val,
        'label' => $text
    ];
}

$sql = "SELECT u.*, c.*
        FROM user u
        JOIN client c ON u.user_id = c.user_id
        WHERE c.client_id = :client_id";
$account = $db->query($sql, ['client_id' => $auth->getUser()['client_id']])->fetch();

$form_schema = [
    'mainFields' => [
        'client_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $account['client_id']
        ],
        'user_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $account['user_id']
        ],
        'firstname' => [
            'type'     => 'text',
            'label'    => LABEL_FIRSTNAME,
            'required' => true,
            'default'  => $account['first_name'],
            'attributes' => [
                'autocomplete' => 'given-name'
            ]
        ],
        'lastname' => [
            'type'     => 'text',
            'label'    => LABEL_LASTNAME,
            'required' => true,
            'default'  => $account['last_name'],
            'attributes' => [
                'autocomplete' => 'family-name'
            ]
        ],
        'email' => [
            'type'     => 'text',
            'label'    => LABEL_EMAIL,
            'required' => true,
            'default'  => $account['email'],
            'attributes' => [
                'autocomplete' => 'email'
            ]
        ],
        'phone' => [
            'type'     => 'tel',
            'label'    => LABEL_PHONE,
            'required' => true,
            'default'  => $account['phone'],
            'attributes' => [
                'autocomplete' => 'tel'
            ]
        ],
        'dob' => [
            'type'     => 'date',
            'label'    => LABEL_DOB,
            'required' => true,
            'default'  => $account['dob'],
            'attributes' => [
                'autocomplete' => 'bday'
            ]
        ],
        'gender' => [
            'type'     => 'select',
            'label'    => LABEL_GENDER,
            'required' => true,
            'default'  => $account['gender'] ?? 'M', // Same default as in database
            'options'  => $gender_options
        ],
        'username' => [
            'type'     => 'text',
            'label'    => LABEL_USERNAME,
            'required' => true,
            'default'  => $account['username'],
            'attributes' => [
                'autocomplete' => 'username'
            ]
        ],
        'password' => [
            'type'     => 'password',
            'label'    => LABEL_NEW_PASSWORD,
            'required' => false,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'new-password'
            ]
        ],
        'confirm_password' => [
            'type'     => 'password',
            'label'    => LABEL_CONFIRM_NEW_PASSWORD,
            'required' => false,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'new-password'
            ]
        ]
    ],
    'cancel_url' => $seoUrl->generate('portal/account.php')
];
