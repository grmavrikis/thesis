<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['admin_id']))
{
    exit();
}

$response = ['success' => false, 'errors' => []];
$fileManager = new FileManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $main = $_POST['main'] ?? [];
    $translations = $_POST['translations'] ?? [];

    foreach ($main as $key => $value)
    {
        if (empty($value))
        {
            $response['errors']["main-$key"] = ERROR_REQUIRED_FIELD;
        }
    }

    $languagesAll = $languages->getLanguages();
    foreach ($languagesAll as $lang)
    {
        $langId = (int)$lang['language_id'];
        if (empty($translations[$langId]['title']))
        {
            $response['errors']["lang-{$langId}-title"] = ERROR_REQUIRED_FIELD;
        }

        // Template File validation
        if (
            isset($_FILES['translations']['name'][$langId]['template_file']) &&
            $_FILES['translations']['error'][$langId]['template_file'] !== UPLOAD_ERR_NO_FILE
        )
        {

            $v_result = $fileManager->validate(
                $_FILES['translations']['name'][$langId]['template_file'],
                $_FILES['translations']['size'][$langId]['template_file'],
                $_FILES['translations']['error'][$langId]['template_file']
            );
            if ($v_result !== true)
            {
                $response['errors']["lang-{$langId}-template_file"] = $v_result;
            }
        }
    }

    // Processing
    if (empty($response['errors']))
    {
        try
        {
            $main_records = [
                'sort_order' => (int)$main['sort_order']
            ];

            $db->update('questionnaire_type', $main_records, 'questionnaire_type_id = :id', ['id' => $main['questionnaire_type_id']]);

            foreach ($translations as $langId => $fields)
            {
                $langId = (int)$langId;
                $translation_records = [
                    'title' => trim($fields['title'] ?? '')
                ];

                $new_template = $fileManager->handleUpload(
                    $_FILES['translations']['name'][$langId]['template_file'] ?? null,
                    $_FILES['translations']['tmp_name'][$langId]['template_file'] ?? null,
                    $_FILES['translations']['error'][$langId]['template_file'] ?? UPLOAD_ERR_NO_FILE,
                    "template_file_{$main['questionnaire_type_id']}_{$langId}"
                );

                if ($new_template)
                {
                    $translation_records['template_file'] = $new_template;
                }

                $db->update(
                    'questionnaire_type_description',
                    $translation_records,
                    'questionnaire_type_id = :id AND language_id = :langId',
                    ['id' => $main['questionnaire_type_id'], 'langId' => $langId]
                );
            }

            $response['success'] = true;
            $response['message'] = SUCCESS_SAVE_QUESTIONNAIRE_TYPE;
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
