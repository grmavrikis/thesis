<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['admin_id']))
{
    exit();
}

$response = ['success' => false, 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $main = $_POST['main'] ?? [];

    $sql = "SELECT *
            FROM invoice_settings
            WHERE invoice_settings_id = 1";

    $results = $db->query($sql, [])->fetch();

    if (!$results)
    {
        sendResponse(false, ERROR_MISSING_INVOICE_ISSUER_DETAILS, ['redirect_url' => $seoUrl->generate('administration/invoice_settings_edit.php')]);
    }

    // Required invoice issuer details
    $required_issuer_fields = [
        'company_name',
        'address_street',
        'address_number',
        'postal_code',
        'city',
        'vat_number',
        'tax_office'
    ];

    foreach ($required_issuer_fields as $field)
    {
        if (empty($results[$field]))
        {
            sendResponse(false, ERROR_MISSING_INVOICE_ISSUER_DETAILS, ['redirect_url' => $seoUrl->generate('administration/invoice_settings_edit.php')]);
        }
    }

    if (empty($main['client_id']))
    {
        $response['errors']['main-client_id'] = ERROR_REQUIRED_FIELD;
    }
    if (empty($main['dietitian_id']))
    {
        $response['errors']['main-dietitian_id'] = ERROR_REQUIRED_FIELD;
    }

    // Prepare Charges Array
    $charges_to_insert = [];
    if (!empty($_POST['charge_description']) && is_array($_POST['charge_description']))
    {
        foreach ($_POST['charge_description'] as $index => $description)
        {
            // Skip empty rows if any
            if (empty($description)) continue;

            $clean_amount = (float)($_POST['charge_clean_amount'][$index] ?? 0);
            $total_amount = (float)($_POST['charge_total_display'][$index] ?? 0);
            $tax_id       = (int)($_POST['charge_tax_id'][$index] ?? 0);

            // Calculate tax_amount
            $tax_amount = $total_amount - $clean_amount;

            $charges_to_insert[] = [
                'description'  => $description,
                'clean_amount' => $clean_amount,
                'tax_amount'   => $tax_amount,
                'tax_id'       => $tax_id
            ];
        }
    }

    // At least one charge is required
    if (empty($charges_to_insert))
    {
        $response['errors']['main-invoice_charges'] = ERROR_NO_INVOICE_CHARGES;
    }

    // If no validation errors, proceed to insert
    if (empty($response['errors']))
    {
        $invoiceObj = new Invoice();

        $invoice_payload = [
            'client_id'    => $main['client_id'],
            'dietitian_id' => $main['dietitian_id'],
            'charges'      => $charges_to_insert
        ];

        $invoice_id = $invoiceObj->createInvoice($invoice_payload);

        if ($invoice_id)
        {
            $response['success'] = true;
            $response['message'] = SUCCESS_CREATE_INVOICE;
            $response['redirect_url'] = $seoUrl->generate('administration/invoice.php');
        }
        else
        {
            $response['success'] = false;
            $response['message'] = ERROR_CREATE_INVOICE;
        }
    }
}

echo json_encode($response);
exit;
