<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['admin_id']) && empty($auth->getUser()['client_id']))
{
    exit();
}

$response = ['success' => false, 'errors' => []];
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $main = $_POST['main'] ?? [];
    // Check Required Fields
    foreach ($main as $key => $value)
    {
        if (empty($value))
        {
            $response['errors']["main-$key"] = ERROR_REQUIRED_FIELD;
        }
    }

    // If no validation errors, proceed to insert
    if (empty($response['errors']))
    {
        $sql = "SELECT appointment_status_id AS id FROM appointment_status where is_default=1";

        $appointment_status = $db->query($sql, [])->fetch();
        if (!$appointment_status)
        {
            $response['success'] = false;
            $response['message'] = ERROR_NO_DEFAULT_APPOINTMENT_STATUS;
        }

        // Insert new appointment
        $records = [
            'appointment_date' => $main['appointment_time'],
            'dietitian_id' => $main['dietitian_id'],
            'client_id' => $main['client_id'],
            'service_id' => $main['service_id'],
            'appointment_status_id' => $appointment_status['id']
        ];

        $inserted_appointment = $db->insert('appointment', $records);
        $appointment_id = $inserted_appointment[0] ?? null;

        $response['success'] = true;
        $response['message'] = SUCCESS_CREATE_APPOINTMENT;

        if (!empty($auth->getUser()['client_id']))
        {
            $response['redirect_url'] = $seoUrl->generate('portal/appointment.php');
        }
        else if (!empty($auth->getUser()['admin_id']))
        {
            $response['redirect_url'] = $seoUrl->generate('administration/appointment.php');
        }
    }
}

echo json_encode($response);
exit;
