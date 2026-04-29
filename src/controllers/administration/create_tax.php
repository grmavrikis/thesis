<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_TAXES, $seoUrl->generate('administration/tax.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$form_schema = [
    'mainFields' => [
        'factor' => [
            'type'     => 'number',
            'label'    => TEXT_ITEMS_FACTOR,
            'required' => true,
            'default'  => 0,
            'attributes' => [
                'min' => '0',
                'max' => '100',
                'step' => '1'
            ]
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/tax.php')
];
