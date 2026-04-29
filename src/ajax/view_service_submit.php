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

    // Check Required Fields
    foreach ($main as $key => $value)
    {
        if (empty($value))
        {
            $response['errors']["main-$key"] = ERROR_REQUIRED_FIELD;
        }
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

    // If no validation errors, proceed to update the database
    if (empty($response['errors']))
    {
        try
        {
            // Update service record
            $records = [
                'sort_order' => $main['sort_order'],
                'clean_cost' => $main['clean_cost'],
                'tax_id' => $main['tax_id']
            ];
            $updated_status = $db->update('service', $records, 'service_id = :service_id', ['service_id' => $main['service_id']]);

            // Update translations
            foreach ($translations as $langId => $fields)
            {
                $translation_records = [
                    'title' => trim($fields['title'])
                ];
                $db->update('service_description', $translation_records, 'service_id = :service_id AND language_id = :langId', ['service_id' => $main['service_id'], 'langId' => $langId]);
            }

            $response['success'] = true;
            $response['message'] = SUCCESS_SAVE_SERVICE;
            $response['redirect_url'] = $seoUrl->generate('administration/service.php');
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
