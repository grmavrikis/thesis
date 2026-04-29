<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

if (!$_GET['id'] || !is_numeric($_GET['id']))
{
    header('Location: ' . $seoUrl->generate('administration/tax.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_TAXES, $seoUrl->generate('administration/tax.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$sql = "SELECT * FROM tax t
        WHERE t.tax_id = ?";

$tax = $db->query($sql, [$_GET['id']])->fetch();

if (!$tax)
{
    header("HTTP/1.0 404 Not Found");
    exit();
}

$form_schema = [
    'mainFields' => [
        'tax_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $tax['tax_id']
        ],
        'factor' => [
            'type'     => 'number',
            'label'    => TEXT_ITEMS_FACTOR,
            'required' => true,
            'default'  => round($tax['factor'] * 100, 2),
            'attributes' => [
                'min' => '0',
                'max' => '100',
                'step' => '1'
            ]
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/tax.php'),
    'creation_date' => (new DateTime($tax['creation_date']))->format('d/m/Y H:i:s'),
    'last_modified_date' => (new DateTime($tax['last_modified_date']))->format('d/m/Y H:i:s')
];
