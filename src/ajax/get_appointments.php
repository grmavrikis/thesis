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
    $page = isset($data['page']) ? (int)$data['page'] : 1;
    $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
    $search_term = isset($data['search_term']) ? $data['search_term'] : '';
    $sort_column = isset($data['sort_column']) ? $data['sort_column'] : '';
    $sort_direction = isset($data['sort_direction']) ? $data['sort_direction'] : 'ASC';
    $filters = isset($data['filters']) ? $data['filters'] : [];
    $filters_initialized = isset($data['filters_initialized']) ? (bool)$data['filters_initialized'] : false;

    $search_where = '';
    $search_params = [];

    if (!empty($auth->getUser()['client_id']))
    {
        $clients = [];
        $clients[] = $auth->getUser()['client_id'];

        $sql = "SELECT dependent_client_id FROM client_relationships
                WHERE manager_client_id = :client_id";
        $params = [':client_id' => $auth->getUser()['client_id']];
        $results = $db->query($sql, $params)->fetchAll();

        foreach ($results as $row)
        {
            $clients[] = $row['dependent_client_id'];
        }

        $inData = $db->prepareIn($clients, 'client_id');

        $search_where .= " AND a.client_id IN ({$inData['placeholders']}) ";
        $search_params = array_merge($search_params, $inData['params']);
    }

    if (!empty($search_term))
    {
        $search_where .= " AND (
                            d.first_name LIKE :search_dietitian_first_name 
                            OR d.last_name LIKE :_search_dietitian_lastname 
                            OR d.email LIKE :search_dietitian_email 
                            OR d.phone LIKE :search_dietitian_phone
                            OR c.first_name LIKE :search_client_first_name 
                            OR c.last_name LIKE :_search_client_lastname 
                            OR c.email LIKE :search_client_email 
                            OR c.phone LIKE :search_client_phone
                         ) ";

        $term = '%' . $search_term . '%';

        $search_terms = [
            ':search_dietitian_first_name' => $term,
            ':_search_dietitian_lastname' => $term,
            ':search_dietitian_email' => $term,
            ':search_dietitian_phone' => $term,
            ':search_client_first_name' => $term,
            ':_search_client_lastname' => $term,
            ':search_client_email' => $term,
            ':search_client_phone' => $term
        ];

        $search_params = array_merge($search_params, $search_terms);
    }

    if (!empty($filters['date_from']))
    {
        $search_where .= " AND a.appointment_date >= :date_from ";
        $search_params[':date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to']))
    {
        $search_where .= " AND a.appointment_date <= :date_to ";
        $search_params[':date_to'] = $filters['date_to'] . ' 23:59:59';
    }
    if (!empty($filters['appointment_status_id']) && $filters['appointment_status_id'] > 0)
    {
        $search_where .= " AND a.appointment_status_id = :appointment_status_id ";
        $search_params[':appointment_status_id'] = $filters['appointment_status_id'];
    }
    if (!empty($filters['client_id']) && $filters['client_id'] > 0)
    {
        $search_where .= " AND a.client_id = :client_id ";
        $search_params[':client_id'] = $filters['client_id'];
    }
    if (!empty($filters['service_id']) && $filters['service_id'] > 0)
    {
        $search_where .= " AND a.service_id = :service_id ";
        $search_params[':service_id'] = $filters['service_id'];
    }
    if (!empty($filters['dietitian_id']) && $filters['dietitian_id'] > 0)
    {
        $search_where .= " AND a.dietitian_id = :dietitian_id ";
        $search_params[':dietitian_id'] = $filters['dietitian_id'];
    }
    if (!empty($filters['pending_plans']) && $filters['pending_plans'] == true)
    {
        $search_where .= " AND a.appointment_date <= NOW() ";
        $search_where .= " AND a.appointment_id NOT IN (SELECT appointment_id FROM nutrition_plan) ";
    }

    $sort = '';
    if (!empty($sort_column) && !empty($sort_direction))
    {
        $allowed_sort_columns = ['id', 'appointment_date', 'dietitian_name', 'client_name', 'service', 'appointment_status_color', 'invoice_serial_number'];
        $allowed_sort_directions = ['ASC', 'DESC'];

        if (in_array($sort_column, $allowed_sort_columns) && in_array(strtoupper($sort_direction), $allowed_sort_directions))
        {
            $sort_column_map = [
                'id' => 'id',
                'appointment_date' => 'a.appointment_date',
                'dietitian_name' => 'dietitian_name',
                'client_name' => 'client_name',
                'service' => 'service',
                'appointment_status_color' => 'st.color',
                'invoice_serial_number' => 'i.serial_number'
            ];
            $sort_column_sql = $sort_column_map[$sort_column];
            $sort = " ORDER BY $sort_column_sql $sort_direction ";
        }
    }

    $count_sql = "SELECT COUNT(a.appointment_id) as total 
                  FROM appointment a
                  JOIN dietitian d ON a.dietitian_id=d.dietitian_id
                  JOIN client AS c ON a.client_id=c.client_id
                  JOIN service_description AS sd ON a.service_id=sd.service_id AND sd.language_id = :language_id
                  JOIN appointment_status AS st ON a.appointment_status_id=st.appointment_status_id
                  WHERE 1
                  $search_where";

    $total_records = $db->query($count_sql, array_merge([':language_id' => $language['id']], $search_params))->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    $sql = "SELECT 
            a.appointment_id AS id,
            a.client_id AS client_id,
            a.dietitian_id AS dietitian_id,
            DATE_FORMAT(a.appointment_date, '%d/%m/%Y<br>%H:%i') AS appointment_date,
            CONCAT_WS('<br>', d.last_name, d.first_name) AS dietitian_name,
            CONCAT_WS('<br>', c.last_name, c.first_name) AS client_name,
            sd.title AS service,
            st.color AS appointment_status_color,
            i.serial_number AS invoice_serial_number
            FROM appointment a
            JOIN dietitian d ON a.dietitian_id=d.dietitian_id
            JOIN client AS c ON a.client_id=c.client_id
            JOIN service_description AS sd ON a.service_id=sd.service_id AND sd.language_id = :language_id
            JOIN appointment_status AS st ON a.appointment_status_id=st.appointment_status_id
            LEFT JOIN invoice AS i ON a.invoice_id=i.invoice_id
            WHERE 1 $search_where
            $sort
            LIMIT :offset, :limit";

    $params = [':language_id' => $language['id'], ':offset' => ($page - 1) * $limit, ':limit' => $limit];
    $params = array_merge($params, $search_params);

    $results = $db->query($sql, $params)->fetchAll();
    foreach ($results as &$result)
    {
        if (!empty($auth->getUser()['client_id']))
        {
            $result['edit_url'] = $seoUrl->generate('portal/view_appointment.php', ['id' => $result['id']]);
        }
        else if (!empty($auth->getUser()['admin_id']))
        {
            $result['edit_url'] = $seoUrl->generate('administration/view_appointment.php', ['id' => $result['id']]);
        }
        $result['appointment_status_color'] = '<div style="width: 20px; height: 20px; background-color: ' . $result['appointment_status_color'] . '; border: 1px solid #ccc;"></div>';
    }

    if ($results !== false)
    {
        $response['success'] = true;
        $response['data'] = $results;
        $response['total_pages'] = $total_pages;
        $response['current_page'] = $page;
        $response['columns'] = [
            ['field' => 'id', 'title' => TEXT_ITEMS_ID],
            ['field' => 'appointment_status_color', 'title' => TEXT_ITEMS_APPOINTMENT_STATUS],
            ['field' => 'appointment_date', 'title' => TEXT_ITEMS_APPOINTMENT_DATE],
            ['field' => 'client_name', 'title' => TEXT_ITEMS_CLIENT],
            ['field' => 'service', 'title' => TEXT_ITEMS_SERVICE],
            ['field' => 'dietitian_name', 'title' => TEXT_ITEMS_DIETITIAN],
            ['field' => 'invoice_serial_number', 'title' => TEXT_ITEMS_INVOICE]
        ];

        if (!$filters_initialized)
        {
            $response['filters'] = [];

            $response['filters'] = [
                [
                    'id' => 'date_from',
                    'label' => TEXT_DATE_FROM,
                    'type' => 'date',
                    'value_type' => 'date'
                ],
                [
                    'id' => 'date_to',
                    'label' => TEXT_DATE_TO,
                    'type' => 'date',
                    'value_type' => 'date'
                ],
                [
                    'id' => 'appointment_status_id',
                    'label' => TEXT_FILTER_APPOINTMENT_STATUS,
                    'type'     => 'select',
                    'value_type' => 'select',
                    'options' => getAppointmentStatusOptions(true)
                ],
                [
                    'id' => 'client_id',
                    'label' => TEXT_FILTER_CLIENT,
                    'type'     => 'select',
                    'value_type' => 'select',
                    'options' => getClientsOptions()
                ],
                [
                    'id' => 'service_id',
                    'label' => TEXT_FILTER_SERVICE,
                    'type'     => 'select',
                    'value_type' => 'select',
                    'options' => getServicesOptions()
                ],
                [
                    'id' => 'dietitian_id',
                    'label' => TEXT_FILTER_DIETITIAN,
                    'type'     => 'select',
                    'value_type' => 'select',
                    'options' => getDietitiansOptions(true)
                ]
            ];
        }
    }
    else
    {
        $response['message'] = ERROR_NO_RECORDS_FOUND;
    }
}
catch (Exception $e)
{
    // In case of a database error
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
