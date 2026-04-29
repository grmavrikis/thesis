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
                                p.title LIKE :search_title OR 
                                p.file_path LIKE :search_file_path OR 
                                c.first_name LIKE :search_client_first_name OR 
                                c.last_name LIKE :search_client_last_name OR 
                                d.first_name LIKE :search_dietitian_first_name OR
                                d.last_name LIKE :search_dietitian_last_name 
                                ) ";

        $term = '%' . $search_term . '%';

        $search_terms = [
            ':search_title' => $term,
            ':search_file_path' => $term,
            ':search_client_last_name' => $term,
            ':search_client_first_name' => $term,
            ':search_dietitian_last_name' => $term,
            ':search_dietitian_first_name' => $term
        ];

        $search_params = array_merge($search_params, $search_terms);
    }

    if (!empty($filters['date_from']))
    {
        $search_where .= " AND p.creation_date >= :date_from ";
        $search_params[':date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to']))
    {
        $search_where .= " AND p.creation_date <= :date_to ";
        $search_params[':date_to'] = $filters['date_to'] . ' 23:59:59';
    }
    if (!empty($filters['client_id']) && $filters['client_id'] > 0)
    {
        $search_where .= " AND a.client_id = :client_id ";
        $search_params[':client_id'] = $filters['client_id'];
    }
    if (!empty($filters['dietitian_id']) && $filters['dietitian_id'] > 0)
    {
        $search_where .= " AND a.dietitian_id = :dietitian_id ";
        $search_params[':dietitian_id'] = $filters['dietitian_id'];
    }

    $sort = '';
    if (!empty($sort_column) && !empty($sort_direction))
    {
        $allowed_sort_columns = ['id', 'client_name', 'dietitian_name', 'title', 'file_path', 'creation_date'];
        $allowed_sort_directions = ['ASC', 'DESC'];

        if (in_array($sort_column, $allowed_sort_columns) && in_array(strtoupper($sort_direction), $allowed_sort_directions))
        {
            $sort_column_map = [
                'id' => 'p.plan_id',
                'client_name' => 'client_name',
                'dietitian_name' => 'dietitian_name',
                'title' => 'p.title',
                'file_path' => 'p.file_path',
                'creation_date' => 'p.creation_date'
            ];
            $sort_column_sql = $sort_column_map[$sort_column];
            $sort = " ORDER BY $sort_column_sql $sort_direction ";
        }
    }

    $count_sql = "SELECT COUNT(p.plan_id) as total 
                  FROM nutrition_plan p
                  JOIN appointment a ON p.appointment_id = a.appointment_id
                  JOIN client c ON a.client_id = c.client_id
                  JOIN dietitian d ON a.dietitian_id = d.dietitian_id
                  WHERE 1
                  $search_where";

    $total_records = $db->query($count_sql, $search_params)->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    $sql = "SELECT 
            p.plan_id AS id, 
            CONCAT_WS('<br>', c.last_name, c.first_name) AS client_name,
            CONCAT_WS('<br>', d.last_name, d.first_name) AS dietitian_name,
            p.title,
            p.file_path,
            DATE_FORMAT(p.creation_date, '%d/%m/%Y') AS creation_date
            FROM nutrition_plan p
            JOIN appointment a ON p.appointment_id = a.appointment_id
            JOIN client c ON a.client_id = c.client_id
            JOIN dietitian d ON a.dietitian_id = d.dietitian_id
            WHERE 1 $search_where
            $sort
            LIMIT :offset, :limit";

    $params = [':offset' => ($page - 1) * $limit, ':limit' => $limit];
    $params = array_merge($params, $search_params);

    $results = $db->query($sql, $params)->fetchAll();
    foreach ($results as &$result)
    {
        if (!empty($auth->getUser()['client_id']))
        {
            $result['edit_url'] = $seoUrl->generate('portal/view_nutrition_plan.php', ['id' => $result['id']]);
        }
        else if (!empty($auth->getUser()['admin_id']))
        {
            $result['edit_url'] = $seoUrl->generate('administration/view_nutrition_plan.php', ['id' => $result['id']]);
        }
    }

    if ($results !== false)
    {
        $response['success'] = true;
        $response['data'] = $results;
        $response['total_pages'] = $total_pages;
        $response['current_page'] = $page;
        $response['columns'] = [
            ['field' => 'id', 'title' => TEXT_ITEMS_ID],
            ['field' => 'client_name', 'title' => TEXT_ITEMS_CLIENT],
            ['field' => 'dietitian_name', 'title' => TEXT_ITEMS_DIETITIAN],
            ['field' => 'title', 'title' => TEXT_ITEMS_TITLE],
            ['field' => 'file_path', 'title' => TEXT_ITEMS_FILE_PATH],
            ['field' => 'creation_date', 'title' => TEXT_ITEMS_DATE]
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
                    'id' => 'client_id',
                    'label' => TEXT_FILTER_CLIENT,
                    'type'     => 'select',
                    'value_type' => 'select',
                    'options' => getClientsOptions()
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
