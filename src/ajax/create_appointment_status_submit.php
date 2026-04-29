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
    $translations = $_POST['translations'] ?? [];

    // Main field validation
    if (empty($main['color']))
    {
        $response['errors']['main-color'] = ERROR_REQUIRED_FIELD;
    }

    // Translations validation
    $languagesAll = $languages->getLanguages();
    foreach ($languagesAll as $lang)
    {
        $langId = $lang['language_id'];
        if (empty($translations[$langId]['title']))
        {
            $response['errors']["lang-{$langId}-title"] = ERROR_REQUIRED_FIELD;
        }
    }

    // If no validation errors, proceed to insert
    if (empty($response['errors']))
    {
        try
        {
            // Insert appointment_status record
            $records = [
                'is_default' => $main['is_default'] ?? 0,
                'color' => $main['color'],
                'sort_order'  => (int)($main['sort_order'] ?? 0)
            ];
            $inserted_status = $db->insert('appointment_status', $records);
            $status_id = $inserted_status[0] ?? null;

            if (!empty($main['is_default']))
            {
                $records = ['is_default' => 0];
                $updated_status = $db->update('appointment_status', $records, 'appointment_status_id <> :id', ['id' => $status_id]);
            }

            // Insert translations
            foreach ($translations as $langId => $fields)
            {
                $translation_records = [
                    'appointment_status_id' => $status_id,
                    'language_id'           => (int)$langId,
                    'title'                 => trim($fields['title'] ?? '')
                ];
                $db->insert('appointment_status_description', $translation_records);
            }


            $response['success'] = true;
            $response['message'] = SUCCESS_CREATE_APPOINTMENT_STATUS;
            $response['redirect_url'] = $seoUrl->generate('administration/appointment_status.php');
        }
        catch (Exception $e)
        {
            $response['success'] = false;
            $response['errors']['general'] = "Database Error: " . $e->getMessage();
        }
    }
}

echo json_encode($response);
exit;
