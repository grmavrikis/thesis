<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['admin_id']))
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
    if (!empty($search_term))
    {
        $search_where = " AND (d.first_name LIKE :search_first_name OR d.last_name LIKE :search_last_name OR d.email LIKE :search_email OR d.phone LIKE :search_phone) ";

        $term = '%' . $search_term . '%';

        $search_params = [
            ':search_first_name' => $term,
            ':search_last_name' => $term,
            ':search_email' => $term,
            ':search_phone' => $term
        ];
    }

    if (!empty($filters['date_from']))
    {
        $search_where .= " AND d.creation_date >= :date_from ";
        $search_params[':date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to']))
    {
        $search_where .= " AND d.creation_date <= :date_to ";
        $search_params[':date_to'] = $filters['date_to'] . ' 23:59:59';
    }

    $sort = '';
    if (!empty($sort_column) && !empty($sort_direction))
    {
        $allowed_sort_columns = ['id', 'first_name', 'last_name', 'email', 'phone', 'creation_date'];
        $allowed_sort_directions = ['ASC', 'DESC'];

        if (in_array($sort_column, $allowed_sort_columns) && in_array(strtoupper($sort_direction), $allowed_sort_directions))
        {
            $sort_column_map = [
                'id' => 'd.dietitian_id',
                'first_name' => 'd.first_name',
                'last_name' => 'd.last_name',
                'email' => 'd.email',
                'phone' => 'd.phone',
                'creation_date' => 'd.creation_date'
            ];
            $sort_column_sql = $sort_column_map[$sort_column];
            $sort = " ORDER BY $sort_column_sql $sort_direction ";
        }
    }

    $count_sql = "SELECT COUNT(d.dietitian_id) as total 
                  FROM dietitian d
                  WHERE 1
                  $search_where";

    $total_records = $db->query($count_sql, $search_params)->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    $sql = "SELECT 
            d.dietitian_id AS id, 
            d.first_name,
            d.last_name,
            d.email,
            d.phone,
            DATE_FORMAT(creation_date, '%d/%m/%Y') AS creation_date
            FROM dietitian d
            WHERE 1 $search_where
            $sort
            LIMIT :offset, :limit";

    $params = [':offset' => ($page - 1) * $limit, ':limit' => $limit];
    $params = array_merge($params, $search_params);

    $results = $db->query($sql, $params)->fetchAll();
    foreach ($results as &$result)
    {
        $result['edit_url'] = $seoUrl->generate('administration/view_dietitian.php', ['id' => $result['id']]);
    }

    if ($results !== false)
    {
        $response['success'] = true;
        $response['data'] = $results;
        $response['total_pages'] = $total_pages;
        $response['current_page'] = $page;
        $response['columns'] = [
            ['field' => 'id', 'title' => TEXT_ITEMS_ID],
            ['field' => 'last_name', 'title' => TEXT_ITEMS_LAST_NAME],
            ['field' => 'first_name', 'title' => TEXT_ITEMS_FIRST_NAME],
            ['field' => 'email', 'title' => TEXT_ITEMS_EMAIL],
            ['field' => 'phone', 'title' => TEXT_ITEMS_PHONE],
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
