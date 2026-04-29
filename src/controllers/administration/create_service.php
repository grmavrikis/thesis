<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_SERVICE, $seoUrl->generate('administration/service.php'));
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
        ],
        'clean_cost' => [
            'type'     => 'number',
            'label'    => TEXT_ITEMS_CLEAN_PRICE,
            'required' => true,
            'default'  => 0
        ],
        'tax_id' => [
            'type'     => 'select',
            'label'    => TEXT_ITEMS_TAX,
            'required' => true,
            'default'  => $default_tax_option,
            'options'  => $tax_options
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/service.php')
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
