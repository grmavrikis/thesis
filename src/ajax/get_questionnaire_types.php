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
        $search_where = " AND qd.title LIKE :search_term ";
        $search_params = [':search_term' => '%' . $search_term . '%'];
    }

    if (!empty($filters['date_from']))
    {
        $search_where .= " AND q.creation_date >= :date_from ";
        $search_params[':date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to']))
    {
        $search_where .= " AND q.creation_date <= :date_to ";
        $search_params[':date_to'] = $filters['date_to'] . ' 23:59:59';
    }

    $sort = '';
    if (!empty($sort_column) && !empty($sort_direction))
    {
        $allowed_sort_columns = ['id', 'title', 'sort_order', 'creation_date'];
        $allowed_sort_directions = ['ASC', 'DESC'];

        if (in_array($sort_column, $allowed_sort_columns) && in_array(strtoupper($sort_direction), $allowed_sort_directions))
        {
            $sort_column_map = [
                'id' => 'q.questionnaire_type_id',
                'title' => 'qd.title',
                'sort_order' => 'q.sort_order',
                'creation_date' => 'q.creation_date'
            ];
            $sort_column_sql = $sort_column_map[$sort_column];
            $sort = " ORDER BY $sort_column_sql $sort_direction ";
        }
    }

    $count_sql = "SELECT COUNT(q.questionnaire_type_id) as total 
                  FROM questionnaire_type q 
                  JOIN questionnaire_type_description qd ON q.questionnaire_type_id = qd.questionnaire_type_id 
                  WHERE qd.language_id = :language_id $search_where";

    $count_params = [':language_id' => $language['id']];
    $count_params = array_merge($count_params, $search_params);

    $total_records = $db->query($count_sql, $count_params)->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    $sql = "SELECT 
            q.questionnaire_type_id AS id, 
            qd.title,
            q.sort_order, 
            DATE_FORMAT(q.creation_date, '%d/%m/%Y') AS creation_date
            FROM questionnaire_type q
            JOIN questionnaire_type_description qd ON q.questionnaire_type_id = qd.questionnaire_type_id 
            WHERE qd.language_id = :language_id 
            $search_where
            $sort
            LIMIT :offset, :limit";

    $params = [':language_id' => $language['id'], ':offset' => ($page - 1) * $limit, ':limit' => $limit];
    $params = array_merge($params, $search_params);

    $results = $db->query($sql, $params)->fetchAll();
    foreach ($results as &$result)
    {
        $result['edit_url'] = $seoUrl->generate('administration/view_questionnaire_type.php', ['id' => $result['id']]);
    }

    if ($results !== false)
    {
        $response['success'] = true;
        $response['data'] = $results;
        $response['total_pages'] = $total_pages;
        $response['current_page'] = $page;
        $response['columns'] = [
            ['field' => 'id', 'title' => TEXT_ITEMS_ID],
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
