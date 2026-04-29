<?php
$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(PAGE_TITLE, null);

$form_schema = [
    'mainFields' => [
        'firstname' => [
            'type'     => 'text',
            'label'    => LABEL_FIRSTNAME,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'given-name'
            ]
        ],
        'lastname' => [
            'type'     => 'text',
            'label'    => LABEL_LASTNAME,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'family-name'
            ]
        ],
        'email' => [
            'type'     => 'email',
            'label'    => LABEL_EMAIL,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'email'
            ]
        ],
        'phone' => [
            'type'     => 'tel',
            'label'    => LABEL_PHONE,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'tel'
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
        'username' => [
            'type'     => 'text',
            'label'    => LABEL_USERNAME,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'username'
            ]
        ],
        'password' => [
            'type'     => 'password',
            'label'    => LABEL_PASSWORD,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'new-password'
            ]
        ],
        'confirm_password' => [
            'type'     => 'password',
            'label'    => LABEL_CONFIRM_PASSWORD,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'new-password'
            ]
        ]
    ],
    'cancel_url' => $seoUrl->generate('index.php')
];
