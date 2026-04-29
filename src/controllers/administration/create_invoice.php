<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_INVOICE, $seoUrl->generate('administration/invoice.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$form_schema = [
    'mainFields' => [
        'dietitian_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $auth->getUser()['admin_id']
        ],
        'client_id' => [
            'type'     => 'select',
            'label'    => LABEL_CLIENT,
            'required' => true,
            'default'  => 0,
            'options'  => getClientsOptions()
        ],
        'invoice_charges' => [
            'type'     => 'custom_code',
            'label'    => LABEL_INVOICE_CHARGES,
            'required' => false,
            'default'  => '<a href="#" id="addInvoiceCharge" class="add-invoice-charge invoice-action">' . TEXT_ADD_INVOICE_CHARGE . '</a>'
        ],
        'total_clean_amount' => [
            'type'     => 'custom_text',
            'label'    => LABEL_INVOICE_TOTAL_CLEAN_AMOUNT,
            'required' => false,
            'default'  => ''
        ],
        'total_tax_amount' => [
            'type'     => 'custom_text',
            'label'    => LABEL_INVOICE_TOTAL_TAX_AMOUNT,
            'required' => false,
            'default'  => ''
        ],
        'total_amount' => [
            'type'     => 'custom_text',
            'label'    => LABEL_INVOICE_TOTAL_AMOUNT,
            'required' => false,
            'default'  => ''
        ]
    ],
    'cancel_url' => $seoUrl->generate('administration/invoice.php')
];
