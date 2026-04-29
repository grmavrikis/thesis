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
    if (empty($data['ids']) || !is_array($data['ids']))
    {
        sendResponse(false, ERROR_NO_IDS_PROVIDED);
    }

    $inData = $db->prepareIn($data['ids']);
    $deleted_count = $db->delete(
        'client',
        "client_id IN ({$inData['placeholders']})",
        $inData['params']
    );

    sendResponse(true, sprintf(SUCCESS_BULK_DELETE, $deleted_count));
}
catch (Exception $e)
{
    // In case of a database error
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
