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
    $fileManager = new FileManager();

    // Get file data from translations table
    $sql_desc = "SELECT template_file FROM questionnaire_type_description WHERE questionnaire_type_id IN ({$inData['placeholders']})";
    $desc_files = $db->query($sql_desc, $inData['params'])->fetchAll();

    $deleted_count = $db->delete(
        'questionnaire_type',
        "questionnaire_type_id IN ({$inData['placeholders']})",
        $inData['params']
    );

    // On successfull delete on the database clear the files
    if ($deleted_count > 0)
    {
        foreach ($desc_files as $f)
        {
            if (!empty($f['template_file']))
            {
                $fileManager->deleteFile($f['template_file']);
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
