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
    // Get the submitted data
    $main = $_POST['main'] ?? [];
    $translations = $_POST['translations'] ?? [];
    $status_id = $main['appointment_status_id'] ?? null;

    // Validation for main required field
    if (empty($main['color']))
    {
        $response['errors']['main-color'] = ERROR_REQUIRED_FIELD;
    }

    // Validation for translations
    $languagesAll = $languages->getLanguages();
    foreach ($languagesAll as $lang)
    {
        $langId = $lang['language_id'];
        if (empty($translations[$langId]['title']))
        {
            $response['errors']["lang-{$langId}-title"] = ERROR_REQUIRED_FIELD;
        }
        else
        {
            // Check for duplicate title in the same language
            $existing = $db->query("SELECT * FROM appointment_status_description WHERE language_id = :langId AND title = :title and appointment_status_id != :statusId", [
                'langId' => $langId,
                'title' => trim($translations[$langId]['title']),
                'statusId' => $status_id ?? null
            ])->fetch();
            if ($existing)
            {
                $response['errors']["lang-{$langId}-title"] = ERROR_TITLE_EXISTS;
            }
        }
    }

    // If no validation errors, proceed to update the database
    if (empty($response['errors']))
    {
        try
        {
            // Update appointment_status record
            if (!empty($main['is_default']))
            {
                $records = ['is_default' => 0];
                $updated_status = $db->update('appointment_status', $records, 'appointment_status_id <> :id', ['id' => $status_id]);
            }
            $records = [
                'is_default' => $main['is_default'] ?? 0,
                'color' => $main['color'],
                'sort_order'  => (int)($main['sort_order'] ?? 0)
            ];
            $updated_status = $db->update('appointment_status', $records, 'appointment_status_id = :id', ['id' => $status_id]);

            // Update translations
            foreach ($translations as $langId => $fields)
            {
                $translation_records = [
                    'title' => trim($fields['title'] ?? '')
                ];
                $db->update('appointment_status_description', $translation_records, 'appointment_status_id = :statusId AND language_id = :langId', ['statusId' => $status_id, 'langId' => $langId]);
            }

            $response['success'] = true;
            $response['message'] = SUCCESS_SAVE_APPOINTMENT_STATUS;
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
