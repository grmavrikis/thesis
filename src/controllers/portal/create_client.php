<?php
if (empty($auth->getUser()['client_id']))
{
    header('Location: ' . $seoUrl->generate('index.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_CLIENTS, $seoUrl->generate('portal/clients.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$form_schema = [
    'mainFields' => [
        'manager_client_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $auth->getUser()['client_id']
        ],
        'active' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => 1
        ],
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
            'required' => false,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'one-time-code'
            ]
        ],
        'phone' => [
            'type'     => 'tel',
            'label'    => LABEL_PHONE,
            'required' => false,
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
        ]
    ],
    'cancel_url' => $seoUrl->generate('portal/clients.php')
];
