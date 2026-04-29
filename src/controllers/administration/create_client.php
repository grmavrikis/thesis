<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_CLIENTS, $seoUrl->generate('administration/clients.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$form_schema = [
    'mainFields' => [
        'firstname' => [
            'type'     => 'text',
            'label'    => LABEL_FIRSTNAME,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'one-time-code'
            ]
        ],
        'lastname' => [
            'type'     => 'text',
            'label'    => LABEL_LASTNAME,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'one-time-code'
            ]
        ],
        'email' => [
            'type'     => 'email',
            'label'    => LABEL_EMAIL,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'one-time-code'
            ]
        ],
        'phone' => [
            'type'     => 'tel',
            'label'    => LABEL_PHONE,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'one-time-code'
            ]
        ],
        'dob' => [
            'type'     => 'date',
            'label'    => LABEL_DOB,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'bday'
            ]
        ],
        'gender' => [
            'type'     => 'select',
            'label'    => LABEL_GENDER,
            'required' => true,
            'default'  => 'M',
            'options'  => getGendersOptions()
        ],
        'manager_client_id' => [
            'type'     => 'select',
            'label'    => LABEL_MANAGER_CLIENT,
            'required' => false,
            'default'  => 0,
            'options'  => getManagerClientsOptions()
        ],
        'active' => [
            'type'     => 'select',
            'label'    => LABEL_ACTIVE,
            'required' => false,
            'default'  => 1,
            'options'  => getBooleanOptions()
        ],
        'username' => [
            'type'     => 'text',
            'label'    => LABEL_USERNAME,
            'required' => false,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'one-time-code'
            ]
        ],
        'password' => [
            'type'     => 'password',
            'label'    => LABEL_PASSWORD,
            'required' => false,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'one-time-code'
            ]
        ],
        'confirm_password' => [
            'type'     => 'password',
            'label'    => LABEL_CONFIRM_PASSWORD,
            'required' => false,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'one-time-code'
            ]
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/clients.php')
];
