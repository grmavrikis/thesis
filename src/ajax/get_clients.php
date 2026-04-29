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

        $search_where .= " AND c.client_id IN ({$inData['placeholders']}) ";
        $search_params = array_merge($search_params, $inData['params']);
    }

    if (!empty($search_term))
    {
        $search_where .= " AND (c.first_name LIKE :search_first_name OR c.last_name LIKE :search_last_name OR c.email LIKE :search_email OR c.phone LIKE :search_phone) ";

        $term = '%' . $search_term . '%';

        $search_terms = [
            ':search_first_name' => $term,
            ':search_last_name' => $term,
            ':search_email' => $term,
            ':search_phone' => $term
        ];
        $search_params = array_merge($search_params, $search_terms);
    }

    if (!empty($filters['date_from']))
    {
        $search_where .= " AND c.creation_date >= :date_from ";
        $search_params[':date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to']))
    {
        $search_where .= " AND c.creation_date <= :date_to ";
        $search_params[':date_to'] = $filters['date_to'] . ' 23:59:59';
    }

    if (!empty($filters['age_from']) && (int)$filters['age_from'] > 0)
    {
        $search_where .= " AND TIMESTAMPDIFF(YEAR, c.dob, CURDATE()) >= :age_from ";
        $search_params[':age_from'] = (int)$filters['age_from'];
    }
    if (!empty($filters['age_to']) && (int)$filters['age_to'] > 0)
    {
        $search_where .= " AND TIMESTAMPDIFF(YEAR, c.dob, CURDATE()) <= :age_to ";
        $search_params[':age_to'] = (int)$filters['age_to'];
    }

    if (!empty($filters['user_status']) && (int)$filters['user_status'] > 0)
    {
        if ($filters['user_status'] == 1)
        {
            // Active
            $search_where .= " AND (
            u.active = 1 
            OR c.client_id IN (
                SELECT cr.dependent_client_id 
                FROM client_relationships cr 
                INNER JOIN client AS manager ON cr.manager_client_id = manager.client_id 
                INNER JOIN user AS mu ON manager.user_id = mu.user_id 
                WHERE mu.active = 1
            )
        ) ";
        }
        else if ($filters['user_status'] == 2)
        {
            // Inactive
            $search_where .= " AND (
            (u.active = 0 OR u.active IS NULL) 
            AND c.client_id NOT IN (
                SELECT cr.dependent_client_id 
                FROM client_relationships cr 
                INNER JOIN client AS manager ON cr.manager_client_id = manager.client_id 
                INNER JOIN user AS mu ON manager.user_id = mu.user_id 
                WHERE mu.active = 1
            )
        ) ";
        }
    }

    if (!empty($filters['gender']) && in_array($filters['gender'], ['M', 'F']))
    {
        $search_where .= " AND c.gender = :gender ";
        $search_params[':gender'] = $filters['gender'];
    }

    if (!empty($filters['client_status']) && (int)$filters['client_status'] > 0)
    {
        if ($filters['client_status'] == 1)
        {
            $search_where .= " AND EXISTS (
            SELECT 1 
            FROM client_relationships cr 
            WHERE cr.manager_client_id = c.client_id
        ) ";
        }
        else if ($filters['client_status'] == 2)
        {
            $search_where .= " AND EXISTS (
            SELECT 1 
            FROM client_relationships cr 
            WHERE cr.dependent_client_id = c.client_id
        ) ";
        }
    }

    $sort = '';
    if (!empty($sort_column) && !empty($sort_direction))
    {
        $allowed_sort_columns = ['id', 'first_name', 'last_name', 'email', 'phone', 'age', 'gender', 'creation_date'];
        $allowed_sort_directions = ['ASC', 'DESC'];

        if (in_array($sort_column, $allowed_sort_columns) && in_array(strtoupper($sort_direction), $allowed_sort_directions))
        {
            $sort_column_map = [
                'id' => 'c.client_id',
                'first_name' => 'c.first_name',
                'last_name' => 'c.last_name',
                'email' => 'c.email',
                'phone' => 'c.phone',
                'age' => 'age',
                'gender' => 'c.gender',
                'creation_date' => 'c.creation_date'
            ];
            $sort_column_sql = $sort_column_map[$sort_column];
            $sort = " ORDER BY $sort_column_sql $sort_direction ";
        }
    }

    $count_sql = "SELECT COUNT(c.client_id) as total 
                  FROM client c
                  LEFT JOIN user u ON c.user_id = u.user_id
                  WHERE 1
                  $search_where";

    $total_records = $db->query($count_sql, $search_params)->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    $sql = "SELECT 
            c.client_id AS id, 
            c.first_name,
            c.last_name,
            c.email,
            c.phone,   
            TIMESTAMPDIFF(YEAR, c.dob, CURDATE()) AS age,
            CASE c.gender
                WHEN 'M' THEN '" . GENDER_MALE . "'
                WHEN 'F' THEN '" . GENDER_FEMALE . "'
                ELSE c.gender
            END AS gender,            
            DATE_FORMAT(c.creation_date, '%d/%m/%Y') AS creation_date
            FROM client c
            LEFT JOIN user u ON c.user_id = u.user_id
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
            $result['edit_url'] = $seoUrl->generate('portal/view_client.php', ['id' => $result['id']]);
        }
        else if (!empty($auth->getUser()['admin_id']))
        {
            $result['edit_url'] = $seoUrl->generate('administration/view_client.php', ['id' => $result['id']]);
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
            ['field' => 'last_name', 'title' => TEXT_ITEMS_LAST_NAME],
            ['field' => 'first_name', 'title' => TEXT_ITEMS_FIRST_NAME],
            ['field' => 'email', 'title' => TEXT_ITEMS_EMAIL],
            ['field' => 'phone', 'title' => TEXT_ITEMS_PHONE],
            ['field' => 'age', 'title' => TEXT_ITEMS_AGE],
            ['field' => 'gender', 'title' => TEXT_ITEMS_GENDER],
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
                    'id' => 'age_from',
                    'label' => TEXT_AGE_FROM,
                    'type'     => 'number',
                    'value_type' => 'number',
                    'attributes' => [
                        'min' => 0
                    ]
                ],
                [
                    'id' => 'age_to',
                    'label' => TEXT_AGE_TO,
                    'type'     => 'number',
                    'value_type' => 'number',
                    'attributes' => [
                        'min' => 0
                    ]
                ],
                [
                    'id' => 'user_status',
                    'label' => TEXT_USER_STATUS,
                    'type'     => 'select',
                    'value_type' => 'select',
                    'options' => getUserStatusOptions()
                ],
                [
                    'id' => 'gender',
                    'label' => TEXT_ITEMS_GENDER,
                    'type'     => 'select',
                    'value_type' => 'select',
                    'options' => getGendersOptions(1)
                ],
                [
                    'id' => 'client_status',
                    'label' => TEXT_ITEMS_CLIENT_STATUS,
                    'type'     => 'select',
                    'value_type' => 'select',
                    'options' => getClientStatusOptions()
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
