<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_QUESTIONNAIRE_TYPES, $seoUrl->generate('administration/questionnaire_type.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$sql = "SELECT 
            t.tax_id, 
            CONCAT(ROUND(t.factor * 100, 2), '%') AS factor_percentage
            FROM tax t
            ORDER BY t.factor DESC";

$results = $db->query($sql, [])->fetchAll();
$default_tax_option = 0;
$tax_options = [];
foreach ($results as $x)
{
    if (empty($default_tax_option))
    {
        $default_tax_option = $x['tax_id'];
    }
    $tax_options[] = [
        'value' => $x['tax_id'],
        'label' => $x['factor_percentage']
    ];
}

$form_schema = [
    'mainFields' => [
        'sort_order' => [
            'type'     => 'number',
            'label'    => TEXT_ITEMS_SORT_ORDER,
            'required' => true,
            'default'  => 0
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/questionnaire_type.php')
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
        ],
        'template_file' => [
            'type'     => 'file',
            'label'    => TEXT_TEMPLATE_FILE,
            'button_label' => TEXT_CHOOSE_TEMPLATE_FILE,
            'required' => false
        ]
    ];
}
