<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/init.php';
require_once '../classes/invoice_pdf.php';

if (empty($auth->getUser()['admin_id']) && empty($auth->getUser()['client_id']))
{
    exit();
}

$response = ['success' => false, 'errors' => [], 'download_url' => '', 'filename' => ''];
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    try
    {
        $invoice_id = isset($input['invoice_id']) ? (int)$input['invoice_id'] : 0;
        if ($invoice_id <= 0)
        {
            $response['errors'][] = ERROR_INVALID_INVOICE_ID;
            echo json_encode($response);
            exit;
        }

        $invoice_obj = new Invoice();
        // Get the invoice data from the database
        $invoice_data = $invoice_obj->getInvoiceData($invoice_id);

        if (!$invoice_data)
        {
            $response['errors'][] = ERROR_INVOICE_NOT_FOUND;
            echo json_encode($response);
            exit;
        }

        // Create the PDF
        $pdf = new InvoicePDF($invoice_data, $invoice_data['totals']);

        $pdf->generate();

        $upload_dir = __DIR__ . '/../uploads/invoices/';
        if (!is_dir($upload_dir))
        {
            mkdir($upload_dir, 0755, true);
        }

        // File naming for automatic overwrite if the same invoice is generated multiple times
        $filename = 'invoice_' . $invoice_data['invoice']['serial_number'] . '.pdf';
        $filepath = $upload_dir . $filename;

        if ($pdf->save($filepath))
        {
            $response['success'] = true;
            $response['download_url'] = '/uploads/invoices/' . $filename;
            $response['filename'] = $filename;
        }
        else
        {
            $response['errors'][] = ERROR_CREATE_INVOICE_PDF;
        }
    }
    catch (Exception $e)
    {
        $response['errors'][] = $e->getMessage();
    }
}

echo json_encode($response);
exit;
