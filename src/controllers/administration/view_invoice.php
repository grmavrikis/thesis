<?php
if (empty($auth->getUser()['admin_id']))
{
    header('Location: ' . $seoUrl->generate('administration/login.php'));
    exit();
}

if (!$_GET['id'] || !is_numeric($_GET['id']))
{
    header('Location: ' . $seoUrl->generate('administration/invoice.php'));
    exit();
}
$breadcrumb = new Breadcrumb();
$breadcrumb->addItem(TEXT_INVOICE, $seoUrl->generate('administration/invoice.php'));
$breadcrumb->addItem(PAGE_TITLE, null);

$sql = "SELECT i.*,
            (
                SELECT GROUP_CONCAT(
                    CONCAT(
                        '- <b>', ic.description, '</b>: ',
                        FORMAT(ic.clean_amount, 2, 'el_GR'), ' € | ',
                        '" . TEXT_TAXES . " (', FORMAT(t.factor * 100, 2, 'el_GR'), '%): ', FORMAT(ic.tax_amount, 2, 'el_GR'), ' € | ',
                        '" . LABEL_INVOICE_TOTAL . ": <b>', FORMAT(ic.clean_amount + ic.tax_amount, 2, 'el_GR'), ' €</b>'
                    ) 
                    SEPARATOR '<br>'
                )
                FROM invoice_charge ic
                JOIN tax t ON t.tax_id = ic.tax_id
                WHERE ic.invoice_id = i.invoice_id
            ) AS invoice_charges,
            (
                SELECT CONCAT(FORMAT(SUM(ic2.clean_amount), 2, 'el_GR'), ' €')                
                FROM invoice_charge ic2
                WHERE ic2.invoice_id = i.invoice_id
            ) AS total_clean_amount,
            (
                SELECT CONCAT(FORMAT(SUM(ic3.tax_amount), 2, 'el_GR'), ' €')                
                FROM invoice_charge ic3
                WHERE ic3.invoice_id = i.invoice_id
            ) AS total_tax_amount,
            (
                SELECT CONCAT(FORMAT(SUM(ic4.clean_amount + ic4.tax_amount), 2, 'el_GR'), ' €')                
                FROM invoice_charge ic4
                WHERE ic4.invoice_id = i.invoice_id
            ) AS total_amount
        FROM invoice i
        WHERE i.invoice_id = ?";

$invoice = $db->query($sql, [$_GET['id']])->fetch();

if (!$invoice)
{
    header("HTTP/1.0 404 Not Found");
    exit();
}

if (!empty($_GET['appointment_id']))
{
    $cancel_url = $seoUrl->generate('administration/view_appointment.php', ['id' => $_GET['appointment_id']]);
}
else
{
    $cancel_url = $seoUrl->generate('administration/invoice.php');
}

$form_schema = [
    'mainFields' => [
        'appointment_id' => [
            'type'     => 'hidden',
            'label'    => '',
            'required' => true,
            'default'  => $invoice['invoice_id']
        ],
        'issuer_details' => [
            'type'     => 'custom_text',
            'label'    => LABEL_INVOICE_ISSUER_DETAILS,
            'required' => false,
            'default'  => getInvoiceIssuerDetailsFromSnapshot($invoice['invoice_id'])
        ],
        'state' => [
            'type'     => 'text',
            'label'    => LABEL_INVOICE_STATE,
            'required' => false,
            'default'  => $invoice['canceled'] == 1 ? TEXT_CANCELED : TEXT_COMPLETED,
            'attributes' => ['disabled' => 'true']
        ],
        'serial_number' => [
            'type'     => 'text',
            'label'    => LABEL_SERIAL_NUMBER,
            'required' => false,
            'default'  => $invoice['serial_number'],
            'attributes' => ['disabled' => 'true']
        ],
        'issue_date' => [
            'type'     => 'date',
            'label'    => LABEL_ISSUE_DATE,
            'required' => false,
            'default'  => (new DateTime($invoice['issue_date']))->format('Y-m-d'),
            'attributes' => ['disabled' => 'true']
        ],
        'client_id' => [
            'type'     => 'select',
            'label'    => LABEL_CLIENT,
            'required' => true,
            'default'  => $invoice['client_id'],
            'options'  => getClientsOptions(),
            'attributes' => ['disabled' => 'true']
        ],
        'dietitian_id' => [
            'type'     => 'select',
            'label'    => LABEL_DIETITIAN,
            'required' => true,
            'default'  => $invoice['dietitian_id'],
            'options'  => getDietitiansOptions(),
            'attributes' => ['disabled' => 'true']
        ],
        'invoice_charges' => [
            'type'     => 'custom_text',
            'label'    => LABEL_INVOICE_CHARGES,
            'required' => false,
            'default'  => $invoice['invoice_charges']
        ],
        'total_clean_amount' => [
            'type'     => 'custom_text',
            'label'    => LABEL_INVOICE_TOTAL_CLEAN_AMOUNT,
            'required' => false,
            'default'  => $invoice['total_clean_amount']
        ],
        'total_tax_amount' => [
            'type'     => 'custom_text',
            'label'    => LABEL_INVOICE_TOTAL_TAX_AMOUNT,
            'required' => false,
            'default'  => $invoice['total_tax_amount']
        ],
        'total_amount' => [
            'type'     => 'custom_text',
            'label'    => LABEL_INVOICE_TOTAL_AMOUNT,
            'required' => false,
            'default'  => $invoice['total_amount']
        ]
    ],
    'cancel_url' => $cancel_url,
    'creation_date' => (new DateTime($invoice['creation_date']))->format('d/m/Y H:i:s'),
    'last_modified_date' => (new DateTime($invoice['last_modified_date']))->format('d/m/Y H:i:s')
];

if ($invoice['canceled'] == 0)
{
    $form_schema['mainFields']['invoice_actions'] = [
        'type'     => 'custom_code',
        'label'    => LABEL_INVOICE_ACTIONS,
        'required' => false,
        'default'  =>
        '<a href="#" id="download-invoice-pdf" class="download-invoice-pdf invoice-action" data-invoice-id="' . $invoice['invoice_id'] . '">' . TEXT_DOWNLOAD_PDF . '</a>
         <a href="#" id="cancel-invoice" class="cancel-invoice invoice-action" data-invoice-id="' . $invoice['invoice_id'] . '">' . TEXT_CANCEL_INVOICE . '</a>'

    ];
}
else
{
    $form_schema['mainFields']['invoice_actions'] = [
        'type'     => 'custom_code',
        'label'    => LABEL_INVOICE_ACTIONS,
        'required' => false,
        'default'  => '<a href="#" id="download-invoice-pdf" class="download-invoice-pdf invoice-action" data-invoice-id="' . $invoice['invoice_id'] . '">' . TEXT_DOWNLOAD_PDF . '</a>'
    ];
}
