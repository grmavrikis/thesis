<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_APPOINTMENT_STATUS, $seoUrl->generate('administration/appointment_status.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$form_schema = [
    'mainFields' => [
        'is_default' => [
            'type'     => 'select',
            'label'    => TEXT_ITEMS_DEFAULT,
            'required' => false,
            'default'  => 0,
            'options'  => getBooleanOptions()
        ],
        'color' => [
            'type'     => 'color',
            'label'    => TEXT_ITEMS_COLOR,
            'required' => true,
            'default'  => '#3498db'
        ],
        'sort_order' => [
            'type'     => 'number',
            'label'    => TEXT_ITEMS_SORT_ORDER,
            'required' => false,
            'default'  => 0
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/appointment_status.php')
];

$languagesAll = $languages->getLanguages();
foreach ($languagesAll as $lang)
{
    $lang_id = $lang['language_id'];
    $form_schema['localFields'][$lang_id] = [
        'title' => [
            'type'     => 'text',
            'label'    => TEXT_ITEMS_TITLE,
            'required' => true,
            'default'  => '',
            'attributes' => [
                'autocomplete' => 'one-time-code'
            ]
        ]
    ];
}
