<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_DIETITIANS, $seoUrl->generate('administration/dietitian.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

// Skip the autocomplee attributes for new dietitian as introduced in client create account form,
// as in a real scenario, dietitians would be created by admins and not by the dietitians themselves, so no need for autocomplete in this case.
// autocomplet = one-time-code is used for chrome fix to prevent autofill of admin data when creating a new dietitian.
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
        'username' => [
            'type'     => 'text',
            'label'    => LABEL_USERNAME,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'one-time-code'
            ]
        ],
        'password' => [
            'type'     => 'password',
            'label'    => LABEL_PASSWORD,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'one-time-code'
            ]
        ],
        'confirm_password' => [
            'type'     => 'password',
            'label'    => LABEL_CONFIRM_PASSWORD,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'one-time-code'
            ]
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/dietitian.php')
];
