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
    $having_clause = '';
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

        $search_where .= " AND i.client_id IN ({$inData['placeholders']}) ";
        $search_params = array_merge($search_params, $inData['params']);
    }

    if (!empty($search_term))
    {
        $search_where .= " AND (
            d.first_name LIKE :s_d_fname 
            OR d.last_name LIKE :s_d_lname 
            OR d.email LIKE :s_d_email 
            OR d.phone LIKE :s_d_phone 
            OR c.first_name LIKE :s_c_fname 
            OR c.last_name LIKE :s_c_lname 
            OR c.email LIKE :s_c_email 
            OR c.phone LIKE :s_c_phone
            OR i.serial_number LIKE :s_serial
        ) ";

        $term = '%' . $search_term . '%';

        $search_terms = [
            ':s_d_fname' => $term,
            ':s_d_lname' => $term,
            ':s_d_email' => $term,
            ':s_d_phone' => $term,
            ':s_c_fname' => $term,
            ':s_c_lname' => $term,
            ':s_c_email' => $term,
            ':s_c_phone' => $term,
            ':s_serial'  => $term
        ];
        $search_params = array_merge($search_params, $search_terms);
    }

    if (!empty($filters['date_from']))
    {
        $search_where .= " AND i.issue_date >= :date_from ";
        $search_params[':date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to']))
    {
        $search_where .= " AND i.issue_date <= :date_to ";
        $search_params[':date_to'] = $filters['date_to'] . ' 23:59:59';
    }
    if (!empty($filters['invoice_status']))
    {
        if ($filters['invoice_status'] == 1)
        {
            $search_where .= " AND i.canceled = 0 ";
        }
        else if ($filters['invoice_status'] == 2)
        {
            $search_where .= " AND i.canceled = 1 ";
        }
    }

    $having_conditions = [];
    if (isset($filters['total_from']) && is_numeric($filters['total_from']) && (float)$filters['total_from'] >= 0)
    {
        $having_conditions[] = "SUM(ic.clean_amount + ic.tax_amount) >= :total_from";
        $search_params[':total_from'] = (float)$filters['total_from'];
    }
    if (isset($filters['total_to']) && is_numeric($filters['total_to']) && (float)$filters['total_to'] > 0)
    {
        $having_conditions[] = "SUM(ic.clean_amount + ic.tax_amount) <= :total_to";
        $search_params[':total_to'] = (float)$filters['total_to'];
    }

    if (!empty($having_conditions))
    {
        $having_clause = " HAVING " . implode(" AND ", $having_conditions);
    }

    if (!empty($filters['client_id']) && $filters['client_id'] > 0)
    {
        $search_where .= " AND i.client_id = :client_id ";
        $search_params[':client_id'] = $filters['client_id'];
    }
    if (!empty($filters['dietitian_id']) && $filters['dietitian_id'] > 0)
    {
        $search_where .= " AND i.dietitian_id = :dietitian_id ";
        $search_params[':dietitian_id'] = $filters['dietitian_id'];
    }

    $sort = '';
    if (!empty($sort_column) && !empty($sort_direction))
    {
        $allowed_sort_columns = ['id', 'canceled', 'serial_number', 'issue_date', 'total_amount', 'dietitian_name', 'client_name'];
        $allowed_sort_directions = ['ASC', 'DESC'];

        if (in_array($sort_column, $allowed_sort_columns) && in_array(strtoupper($sort_direction), $allowed_sort_directions))
        {
            $sort_column_map = [
                'id' => 'id',
                'canceled' => 'i.canceled',
                'serial_number' => 'serial_number',
                'issue_date' => 'issue_date',
                'total_amount' => 'SUM(ic.clean_amount + ic.tax_amount)', /* For correct sorting */
                'dietitian_name' => 'dietitian_name',
                'client_name' => 'client_name'
            ];
            $sort_column_sql = $sort_column_map[$sort_column];
            $sort = " ORDER BY $sort_column_sql $sort_direction ";
        }
    }

    // For COUNT to work properly with HAVING, we need to make the main query a subquery
    $count_sql = "SELECT COUNT(*) as total FROM (
                    SELECT i.invoice_id 
                    FROM invoice i 
                    JOIN client c ON i.client_id = c.client_id
                    JOIN dietitian d ON i.dietitian_id = d.dietitian_id
                    JOIN invoice_charge ic ON i.invoice_id = ic.invoice_id
                    WHERE 1 $search_where
                    GROUP BY i.invoice_id
                    $having_clause
                  ) as count_table";

    $total_records = $db->query($count_sql, $search_params)->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    $sql = "SELECT 
            i.invoice_id AS id,
            i.serial_number,
            DATE_FORMAT(i.issue_date, '%d/%m/%Y') AS issue_date,
            i.canceled,
            CONCAT(FORMAT(SUM(ic.clean_amount + ic.tax_amount), 2, 'el_GR'), ' €') AS total_amount,
            CONCAT_WS('<br>', d.last_name, d.first_name) AS dietitian_name,
            CONCAT_WS('<br>', c.last_name, c.first_name) AS client_name,
            i.invoice_id as invoice_id
            FROM invoice i
            JOIN client c ON i.client_id = c.client_id
            JOIN dietitian d ON i.dietitian_id = d.dietitian_id
            JOIN invoice_charge ic ON i.invoice_id = ic.invoice_id
            WHERE 1  $search_where
            GROUP BY i.invoice_id
            $having_clause
            $sort
            LIMIT :offset, :limit";

    $params = [':offset' => ($page - 1) * $limit, ':limit' => $limit];
    $params = array_merge($params, $search_params);

    $results = $db->query($sql, $params)->fetchAll();

    foreach ($results as &$result)
    {
        if (!empty($auth->getUser()['client_id']))
        {
            $result['edit_url'] = $seoUrl->generate('portal/view_invoice.php', ['id' => $result['id']]);
        }
        else if (!empty($auth->getUser()['admin_id']))
        {
            $result['edit_url'] = $seoUrl->generate('administration/view_invoice.php', ['id' => $result['id']]);
        }

        if ($result['canceled'] == 1)
        {
            $bg_color = '#f44336';
        }
        else
        {
            $bg_color = '#4caf50';
        }
        $result['canceled'] = "<div style=\"width: 20px; height: 20px; background-color: $bg_color; border: 1px solid #ccc;\"></div>";
    }

    if ($results !== false)
    {
        $response['success'] = true;
        $response['data'] = $results;
        $response['total_pages'] = $total_pages;
        $response['current_page'] = $page;
        $response['columns'] = [
            ['field' => 'id', 'title' => TEXT_ITEMS_ID],
            ['field' => 'canceled', 'title' => TEXT_ITEMS_STATUS],
            ['field' => 'serial_number', 'title' => TEXT_ITEMS_SERIAL_NUMBER],
            ['field' => 'issue_date', 'title' => TEXT_ITEMS_ISSUE_DATE],
            ['field' => 'total_amount', 'title' => TEXT_ITEMS_TOTAL_AMOUNT],
            ['field' => 'client_name', 'title' => TEXT_ITEMS_CLIENT],
            ['field' => 'dietitian_name', 'title' => TEXT_ITEMS_DIETITIAN]
        ];

        if (!$filters_initialized)
        {
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
                    'id' => 'invoice_status',
                    'label' => TEXT_ITEMS_STATUS,
                    'type'     => 'select',
                    'value_type' => 'select',
                    'options' => getInvoiceStatusOptions()
                ],
                [
                    'id' => 'total_from',
                    'label' => TEXT_TOTAL_FROM,
                    'type'     => 'number',
                    'value_type' => 'number',
                    'attributes' => [
                        'min' => 0
                    ]
                ],
                [
                    'id' => 'total_to',
                    'label' => TEXT_TOTAL_TO,
                    'type'     => 'number',
                    'value_type' => 'number',
                    'attributes' => [
                        'min' => 0
                    ]
                ],
                [
                    'id' => 'client_id',
                    'label' => TEXT_ITEMS_CLIENT,
                    'type'     => 'select',
                    'value_type' => 'select',
                    'options' => getClientsOptions()
                ],
                [
                    'id' => 'dietitian_id',
                    'label' => TEXT_ITEMS_DIETITIAN,
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
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
