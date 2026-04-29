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
    if (empty($data['file_name']))
    {
        sendResponse(false, ERROR_NO_FILE_PROVIDED);
    }

    $fileManager = new FileManager();
    $fileManager->deleteFile($data['file_name']);

    if (!empty($data['table']) && !empty($data['id']))
    {
        if ($data['table'] == 'questionnaire')
        {
            $db->delete(
                'questionnaire',
                'questionnaire_id = ?',
                [$data['id']]
            );
        }
        else if ($data['table'] == 'nutrition_plan')
        {
            $db->delete(
                'nutrition_plan',
                'plan_id = ?',
                [$data['id']]
            );
        }
        else if ($data['table'] == 'invoice_settings')
        {
            $records = [
                'logo_path' => ''
            ];
            $db->update('invoice_settings', $records, 'invoice_settings_id = 1', []);
        }
    }

    sendResponse(true, SUCCESS_FILE_DELETE);
}
catch (Exception $e)
{
    // In case of a database error
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
