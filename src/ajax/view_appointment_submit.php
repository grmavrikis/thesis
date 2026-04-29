<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['admin_id']) && empty($auth->getUser()['client_id']))
{
    exit();
}

$response = ['success' => false, 'errors' => []];
$fileManager = new FileManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $main = $_POST['main'] ?? [];

    // Check Required Fields
    foreach ($main as $key => $value)
    {
        if (empty($value) && !in_array($key, ['questionnaire_id', 'questionnaire_title', 'plan_id', 'plan_title']))
        {
            $response['errors']["main-$key"] = ERROR_REQUIRED_FIELD;
        }
    }

    // Questionnaire File validation
    if (
        isset($_FILES['main']['name']['questionnaire_file_path']) &&
        $_FILES['main']['error']['questionnaire_file_path'] !== UPLOAD_ERR_NO_FILE
    )
    {
        $v_result = $fileManager->validate(
            $_FILES['main']['name']['questionnaire_file_path'],
            $_FILES['main']['size']['questionnaire_file_path'],
            $_FILES['main']['error']['questionnaire_file_path']
        );
        if ($v_result !== true)
        {
            $response['errors']["main-questionnaire_file_path"] = $v_result;
        }
    }

    // Nutrition Plan File validation
    if (
        isset($_FILES['main']['name']['plan_file_path']) &&
        $_FILES['main']['error']['plan_file_path'] !== UPLOAD_ERR_NO_FILE
    )
    {

        $v_result = $fileManager->validate(
            $_FILES['main']['name']['plan_file_path'],
            $_FILES['main']['size']['plan_file_path'],
            $_FILES['main']['error']['plan_file_path']
        );
        if ($v_result !== true)
        {
            $response['errors']["main-plan_file_path"] = $v_result;
        }
    }

    // If no validation errors, proceed to update the database
    if (empty($response['errors']))
    {
        try
        {
            $records = [
                'appointment_date' => $main['appointment_time'],
                'dietitian_id' => $main['dietitian_id'],
                'client_id' => $main['client_id'],
                'service_id' => $main['service_id'],
                'appointment_status_id' => $main['appointment_status_id']
            ];
            $updated_appointment = $db->update('appointment', $records, 'appointment_id = :id', ['id' => $main['appointment_id']]);

            if (
                isset($_FILES['main']['name']['questionnaire_file_path']) &&
                $_FILES['main']['error']['questionnaire_file_path'] !== UPLOAD_ERR_NO_FILE
            )
            {
                $records = [
                    'title' => trim($main['questionnaire_title'] ?? ''),
                    'appointment_id' => $main['appointment_id']
                ];

                $new_questionnaire = $fileManager->handleUpload(
                    $_FILES['main']['name']['questionnaire_file_path'] ?? null,
                    $_FILES['main']['tmp_name']['questionnaire_file_path'] ?? null,
                    $_FILES['main']['error']['questionnaire_file_path'] ?? UPLOAD_ERR_NO_FILE,
                    "questionnaire_{$main['appointment_id']}"
                );

                if ($new_questionnaire)
                {
                    $records['file_path'] = $new_questionnaire;
                }

                if (empty($main['questionnaire_id']))
                {
                    $db->insert('questionnaire', $records);
                }
                else
                {
                    $db->update('questionnaire', $records, 'questionnaire_id = ?', [$main['questionnaire_id']]);
                }
            }

            if (
                isset($_FILES['main']['name']['plan_file_path']) &&
                $_FILES['main']['error']['plan_file_path'] !== UPLOAD_ERR_NO_FILE
            )
            {
                $records = [
                    'title' => trim($main['plan_title'] ?? ''),
                    'appointment_id' => $main['appointment_id']
                ];

                $new_plan = $fileManager->handleUpload(
                    $_FILES['main']['name']['plan_file_path'] ?? null,
                    $_FILES['main']['tmp_name']['plan_file_path'] ?? null,
                    $_FILES['main']['error']['plan_file_path'] ?? UPLOAD_ERR_NO_FILE,
                    "plan_{$main['appointment_id']}"
                );

                if ($new_plan)
                {
                    $records['file_path'] = $new_plan;
                }

                if (empty($main['plan_id']))
                {
                    $db->insert('nutrition_plan', $records);
                }
                else
                {
                    $db->update('nutrition_plan', $records, 'plan_id = ?', [$main['plan_id']]);
                }
            }

            $response['success'] = true;
            $response['message'] = SUCCESS_SAVE_APPOINTMENT;
            $response['redirect_url'] = $seoUrl->generate('administration/appointment.php');
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
