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

    // If no validation errors, proceed to insert
    if (empty($response['errors']))
    {
        try
        {
            // Insert service record
            $records = [
                'clean_cost'  => $main['clean_cost'],
                'sort_order'  => $main['sort_order'],
                'tax_id'  => $main['tax_id']
            ];
            $inserted_service = $db->insert('service', $records);
            $service_id = $inserted_service[0] ?? null;

            // Insert translations
            foreach ($translations as $langId => $fields)
            {
                $translation_records = [
                    'service_id' => $service_id,
                    'language_id'           => (int)$langId,
                    'title'                 => trim($fields['title'] ?? '')
                ];
                $db->insert('service_description', $translation_records);
            }

            $response['success'] = true;
            $response['message'] = SUCCESS_CREATE_SERVICE;
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
