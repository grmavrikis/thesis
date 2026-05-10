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

    $inData = $db->prepareIn($data['ids'], 'plan_id');
    $fileManager = new FileManager();

    // The admin cannot delete nutrition plans created by other
    $count_sql = "SELECT COUNT(p.plan_id) as total 
            FROM nutrition_plan p
            JOIN appointment a ON p.appointment_id = a.appointment_id
            WHERE p.plan_id IN ({$inData['placeholders']}) 
            AND a.dietitian_id <> :admin_id";

    $count_params = [':admin_id' => $auth->getUser()['admin_id']];
    $count_params = array_merge($count_params, $inData['params']);

    $total_records = $db->query($count_sql, $count_params)->fetch()['total'];

    if ($total_records > 0)
    {
        sendResponse(false, ERROR_CANNOT_DELETE_OTHER_ADMIN_ENTRIES);
    }

    $sql = "SELECT file_path FROM nutrition_plan WHERE plan_id IN ({$inData['placeholders']})";
    $files = $db->query($sql, $inData['params'])->fetchAll();

    $deleted_count = $db->delete(
        'nutrition_plan',
        "plan_id IN ({$inData['placeholders']})",
        $inData['params']
    );

    // On successfull delete on the database clear the files
    if ($deleted_count > 0)
    {
        foreach ($files as $f)
        {
            if (!empty($f['file_path']))
            {
                $fileManager->deleteFile($f['file_path']);
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
