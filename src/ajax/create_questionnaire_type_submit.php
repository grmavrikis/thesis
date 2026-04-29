<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['admin_id']))
{
    exit();
}

$response = ['success' => false, 'errors' => []];
$upload_dir = '../uploads/';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $main = $_POST['main'] ?? [];
    $translations = $_POST['translations'] ?? [];

    // --- Validation ---
    // Check Required Text Fields
    if (empty($main['sort_order']) && $main['sort_order'] !== '0')
    {
        $response['errors']["main-sort_order"] = ERROR_REQUIRED_FIELD;
    }

    $languagesAll = $languages->getLanguages();
    foreach ($languagesAll as $lang)
    {
        $langId = $lang['language_id'];
        if (empty($translations[$langId]['title']))
        {
            $response['errors']["lang-{$langId}-title"] = ERROR_REQUIRED_FIELD;
        }
    }

    // --- Processing ---
    if (empty($response['errors']))
    {
        try
        {
            // 1. Insert Main Record
            $records = [
                'sort_order' => (int)$main['sort_order']
            ];
            $inserted_questionnaire_type = $db->insert('questionnaire_type', $records);
            $questionnaire_type_id = $inserted_questionnaire_type[0] ?? null;

            if (!$questionnaire_type_id) throw new Exception("Failed to get Insert ID");

            // 3. Insert Translations & Local Files
            foreach ($translations as $langId => $fields)
            {
                $template_filename = '';

                // Handle Translation File (template_file)
                if (
                    isset($_FILES['translations']['name'][$langId]['template_file']) &&
                    $_FILES['translations']['error'][$langId]['template_file'] === UPLOAD_ERR_OK
                )
                {
                    $file_tmp  = $_FILES['translations']['tmp_name'][$langId]['template_file'];
                    $file_name = $_FILES['translations']['name'][$langId]['template_file'];
                    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    $new_filename = "template_file_" . $questionnaire_type_id . "_" . $langId . "." . $extension;

                    if (move_uploaded_file($file_tmp, $upload_dir . $new_filename))
                    {
                        $template_filename = $new_filename;
                    }
                }

                $translation_records = [
                    'questionnaire_type_id' => $questionnaire_type_id,
                    'language_id'           => (int)$langId,
                    'title'                 => trim($fields['title'] ?? ''),
                    'template_file'         => $template_filename
                ];
                $db->insert('questionnaire_type_description', $translation_records);
            }

            $response['success'] = true;
            $response['message'] = SUCCESS_CREATE_QUESTIONNAIRE_TYPE;
            $response['redirect_url'] = $seoUrl->generate('administration/questionnaire_type.php');
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
