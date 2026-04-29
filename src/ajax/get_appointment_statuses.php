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
        $search_where = " AND ad.title LIKE :search_title ";
        $search_params = [':search_title' => '%' . $search_term . '%'];
    }

    if (!empty($filters['date_from']))
    {
        $search_where .= " AND a.creation_date >= :date_from ";
        $search_params[':date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to']))
    {
        $search_where .= " AND a.creation_date <= :date_to ";
        $search_params[':date_to'] = $filters['date_to'] . ' 23:59:59';
    }

    if (!empty($filters['colors']) && is_array($filters['colors']))
    {
        $inData = $db->prepareIn($filters['colors'], 'color');
        $search_where .= " AND a.color IN ({$inData['placeholders']}) ";
        $search_params = array_merge($search_params, $inData['params']);
    }

    $sort = '';
    if (!empty($sort_column) && !empty($sort_direction))
    {
        $allowed_sort_columns = ['id', 'title', 'sort_order', 'color', 'creation_date'];
        $allowed_sort_directions = ['ASC', 'DESC'];

        if (in_array($sort_column, $allowed_sort_columns) && in_array(strtoupper($sort_direction), $allowed_sort_directions))
        {
            $sort_column_map = [
                'id' => 'a.appointment_status_id',
                'title' => 'title',
                'sort_order' => 'a.sort_order',
                'color' => 'a.color',
                'creation_date' => 'a.creation_date'
            ];
            $sort_column_sql = $sort_column_map[$sort_column];
            $sort = " ORDER BY $sort_column_sql $sort_direction ";
        }
    }

    $count_sql = "SELECT COUNT(a.appointment_status_id) as total 
                  FROM appointment_status a 
                  JOIN appointment_status_description ad ON a.appointment_status_id = ad.appointment_status_id 
                  WHERE ad.language_id = :language_id $search_where";

    $count_params = [':language_id' => $language['id']];
    $count_params = array_merge($count_params, $search_params);

    $total_records = $db->query($count_sql, $count_params)->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    $sql = "SELECT 
            a.appointment_status_id AS id, 
            a.sort_order, 
            a.color, 
            DATE_FORMAT(a.creation_date, '%d/%m/%Y') AS creation_date, 
            CASE 
                WHEN a.is_default = 1 THEN CONCAT('<b>', ad.title, '</b>')
                ELSE ad.title 
            END AS title 
            FROM appointment_status a
            JOIN appointment_status_description ad ON a.appointment_status_id = ad.appointment_status_id 
            WHERE ad.language_id = :language_id 
            $search_where
            $sort
            LIMIT :offset, :limit";

    $params = [':language_id' => $language['id'], ':offset' => ($page - 1) * $limit, ':limit' => $limit];
    $params = array_merge($params, $search_params);

    $results = $db->query($sql, $params)->fetchAll();
    foreach ($results as &$result)
    {
        $result['edit_url'] = $seoUrl->generate('administration/view_appointment_status.php', ['id' => $result['id']]);
        $result['color'] = '<div style="width: 20px; height: 20px; background-color: ' . $result['color'] . '; border: 1px solid #ccc;"></div>';
    }

    if ($results !== false)
    {
        $response['success'] = true;
        $response['data'] = $results;
        $response['total_pages'] = $total_pages;
        $response['current_page'] = $page;
        $response['columns'] = [
            ['field' => 'id', 'title' => TEXT_ITEMS_ID],
            ['field' => 'color', 'title' => TEXT_ITEMS_COLOR],
            ['field' => 'title', 'title' => TEXT_ITEMS_TITLE],
            ['field' => 'sort_order', 'title' => TEXT_ITEMS_SORT_ORDER],
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

            // Get all unique colors for the filter options and add the color filter
            $colors = $db->query("SELECT color FROM appointment_status WHERE color IS NOT NULL GROUP BY color")->fetchAll();
            $response['filters'][] = [
                'id' => 'colors',
                'label' => TEXT_ITEMS_COLOR,
                'type' => 'checkbox_group',
                'value_type' => 'color',
                'options' => $colors
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
