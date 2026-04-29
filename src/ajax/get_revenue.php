<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['admin_id']))
{
    exit();
}

$response = ['success' => false, 'data' => [], 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    sendResponse(false, TEXT_INVALID_REQUEST_METHOD);
}

try
{
    $sql = "SELECT 
            DATE_FORMAT(i.issue_date, '%m/%Y') AS month_year,
            SUM(ic.clean_amount) AS total_net,
            SUM(ic.tax_amount) AS total_tax,
            SUM(ic.clean_amount + ic.tax_amount) AS total_gross
        FROM invoice i
        JOIN invoice_charge ic ON i.invoice_id = ic.invoice_id
        WHERE i.canceled = 0 
          AND i.issue_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month_year, YEAR(i.issue_date), MONTH(i.issue_date)
        ORDER BY YEAR(i.issue_date) DESC, MONTH(i.issue_date) DESC";

    $results = $db->query($sql)->fetchAll();

    for ($i = 0; $i < count($results); $i++)
    {
        $current_gross = (float)$results[$i]['total_gross'];
        $percentage_change = null;

        if (isset($results[$i + 1]))
        {
            $prev_gross = (float)$results[$i + 1]['total_gross'];
            if ($prev_gross > 0)
            {
                $percentage_change = (($current_gross - $prev_gross) / $prev_gross) * 100;
            }
        }
        $results[$i]['percentage_change'] = $percentage_change;
    }

    $response['success'] = true;
    $response['data'] = $results;
}
catch (Exception $e)
{
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
