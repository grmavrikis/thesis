<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_ACCOUNT, $seoUrl->generate('administration/admin_account.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$file_manager = new FileManager();

$sql = "SELECT i.*
        FROM invoice_settings i
        WHERE i.invoice_settings_id = 1";

$settings = $db->query($sql, [])->fetch();

$form_schema = [
    'mainFields' => [
        'company_name' => [
            'type'     => 'text',
            'label'    => LABEL_COMPANY_NAME,
            'required' => true,
            'default'  => $settings['company_name'] ?? null
        ],
        'company_title' => [
            'type'     => 'text',
            'label'    => LABEL_COMPANY_TITLE,
            'required' => true,
            'default'  => $settings['company_title'] ?? null
        ],
        'vat_number' => [
            'type'     => 'text',
            'label'    => LABEL_COMPANY_VAT_NUMBER,
            'required' => true,
            'default'  => $settings['vat_number'] ?? null
        ],
        'tax_office' => [
            'type'     => 'text',
            'label'    => LABEL_COMPANY_TAX_OFFICE,
            'required' => true,
            'default'  => $settings['tax_office'] ?? null
        ],
        'address_street' => [
            'type'     => 'text',
            'label'    => LABEL_COMPANY_ADDRESS_STREET,
            'required' => true,
            'default'  => $settings['address_street'] ?? null
        ],
        'address_number' => [
            'type'     => 'text',
            'label'    => LABEL_COMPANY_ADDRESS_STREET_NUMBER,
            'required' => true,
            'default'  => $settings['address_number'] ?? null
        ],
        'city' => [
            'type'     => 'text',
            'label'    => LABEL_COMPANY_CITY,
            'required' => true,
            'default'  => $settings['city'] ?? null
        ],
        'postal_code' => [
            'type'     => 'text',
            'label'    => LABEL_COMPANY_POSTAL_CODE,
            'required' => true,
            'default'  => $settings['postal_code'] ?? null
        ],
        'phone' => [
            'type'     => 'tel',
            'label'    => LABEL_PHONE,
            'required' => true,
            'default'  => $settings['phone'] ?? null
        ],
        'email' => [
            'type'     => 'text',
            'label'    => LABEL_EMAIL,
            'required' => true,
            'default'  => $settings['email'] ?? null
        ],
        'logo_path' => [
            'type'     => 'file',
            'label'    => LABEL_COMPANY_LOGO_JPG,
            'required' => false,
            'default'  => $file_manager->getFileUrl($settings['logo_path']),
            'filename' => $settings['logo_path'],
            'id'       => $settings['invoice_settings_id'],
            'table'    => 'invoice_settings'
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/invoice_settings.php')
];
