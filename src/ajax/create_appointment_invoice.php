<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['admin_id']))
{
    exit();
}

$response = ['success' => false, 'data' => [], 'message' => ''];
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    sendResponse(false, TEXT_INVALID_REQUEST_METHOD);
}

// Get the JSON input
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

try
{
    if (empty($data['appointment_id']))
    {
        sendResponse(false, ERROR_NO_APPOINTMENT_ID_PROVIDED);
    }

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

    $inv = new Invoice();
    $invoice_id = $inv->createAppointmentInvoice($data['appointment_id']);
    $invoice_output = viewAppointmentInvoiceHtml($invoice_id, $data['appointment_id']);
    sendResponse(true, '', ['html' => $invoice_output]);
}
catch (Exception $e)
{
    // In case of a database error
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
