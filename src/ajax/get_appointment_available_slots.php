<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['admin_id']) && empty($auth->getUser()['client_id']))
{
    exit();
}

$response = ['success' => false, 'data' => [], 'message' => '', 'filters' => []];
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    sendResponse(false, TEXT_INVALID_REQUEST_METHOD);
}

// Get the JSON input
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

try
{
    $appointment_date = $data['appointment_date'] ?? '';
    $appointment_id = $data['appointment_id'] ?? 0;

    $taken_slot_datetime = ''; // YYYY-MM-DD HH:ii

    // If appointment_id exists then include the apppointment's slot in the results.
    if (!empty($appointment_id))
    {
        $sql = "SELECT appointment_date FROM appointment WHERE appointment_id = :id";
        $current_app = $db->query($sql, [':id' => $appointment_id])->fetch();

        if ($current_app)
        {
            $dt_obj = new DateTime($current_app['appointment_date']);
            $taken_slot_datetime = $dt_obj->format('Y-m-d H:i');
        }
    }

    $available_appointment_slots = [];
    if (!empty($appointment_date))
    {
        $available_appointment_slots = array_merge(
            $available_appointment_slots,
            getAppointmentSlots($appointment_date, $taken_slot_datetime)
        );
    }

    $html_options = '';
    if (!empty($available_appointment_slots))
    {
        foreach ($available_appointment_slots as $slot)
        {
            $val = htmlspecialchars($slot['value']);
            $lab = htmlspecialchars($slot['label']);

            $selected = ($val === $taken_slot_datetime) ? ' selected' : '';
            $html_options .= "<option value=\"{$val}\"{$selected}>{$lab}</option>\n";
        }
    }

    $response['html_options'] = $html_options;
    $response['success'] = true;
}
catch (Exception $e)
{
    // In case of a database error
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
