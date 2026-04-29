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

    $inData = $db->prepareIn($data['ids'], 'appointment_id');
    $fileManager = new FileManager();

    // The admin cannot delete appointments created by other
    $count_sql = "SELECT COUNT(a.appointment_id) as total 
            FROM appointment a
            WHERE a.appointment_id IN ({$inData['placeholders']}) 
            AND a.dietitian_id <> :admin_id";

    $count_params = [':admin_id' => $auth->getUser()['admin_id']];
    $count_params = array_merge($count_params, $inData['params']);

    $total_records = $db->query($count_sql, $count_params)->fetch()['total'];

    if ($total_records > 0)
    {
        sendResponse(false, ERROR_CANNOT_DELETE_OTHER_ADMIN_ENTRIES);
    }

    $sql = "SELECT q.file_path AS questionnaire_file,
                   n.file_path AS plan_file
            FROM appointment a
            LEFT JOIN questionnaire q ON a.appointment_id = q.appointment_id
            LEFT JOIN nutrition_plan n ON a.appointment_id = n.appointment_id
            WHERE a.appointment_id IN ({$inData['placeholders']})";
    $files = $db->query($sql, $inData['params'])->fetchAll();

    $deleted_count = $db->delete(
        'appointment',
        "appointment_id IN ({$inData['placeholders']})",
        $inData['params']
    );

    // On successfull delete on the database clear the files
    if ($deleted_count > 0)
    {
        foreach ($files as $f)
        {
            if (!empty($f['questionnaire_file']))
            {
                $fileManager->deleteFile($f['questionnaire_file']);
            }

            if (!empty($f['plan_file']))
            {
                $fileManager->deleteFile($f['plan_file']);
            }
        }
    }

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
