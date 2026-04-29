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
    if (empty($data['invoice_id']))
    {
        sendResponse(false, ERROR_NO_INVOICE_ID_PROVIDED);
    }

    $inv = new Invoice();

    if (!$inv->cancelInvoice($data['invoice_id']))
    {
        sendResponse(false, ERROR_CREATE_INVOICE);
    }

    sendResponse(true, '', ['html' => '<span>' . SUCCESS_CREATE_INVOICE . '</span>']);
}
catch (Exception $e)
{
    // In case of a database error
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
