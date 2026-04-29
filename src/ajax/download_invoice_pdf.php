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
            $response['errors'][] = 'Μη έγκυρο ID παραστατικού.';
            echo json_encode($response);
            exit;
        }

        $invoice_obj = new Invoice();
        // Ανάκτηση πραγματικών δεδομένων από τη βάση
        $invoice_data = $invoice_obj->getInvoiceData($invoice_id);

        if (!$invoice_data)
        {
            throw new Exception('Το παραστατικό δεν βρέθηκε.');
        }

        // Δημιουργία PDF με τα πραγματικά δεδομένα
        $pdf = new InvoicePDF($invoice_data, $invoice_data['totals']);

        $pdf->generate();

        $upload_dir = __DIR__ . '/../uploads/invoices/';
        if (!is_dir($upload_dir))
        {
            mkdir($upload_dir, 0755, true);
        }

        // Ονοματοδοσία αρχείου για αυτόματη υπεργραφή (overwrite)
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
            $response['errors'][] = 'Αποτυχία δημιουργίας αρχείου στον server.';
        }
    }
    catch (Exception $e)
    {
        $response['errors'][] = $e->getMessage();
    }
}

echo json_encode($response);
exit;
