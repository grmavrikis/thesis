<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['admin_id']))
{
    exit();
}

$response = ['success' => false, 'errors' => []];
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $main = $_POST['main'] ?? [];

    if (!isset($main['factor']))
    {
        $response['errors']["main-factor"] = ERROR_REQUIRED_FIELD;
    }

    if ($main['factor'] < 0 || $main['factor'] > 100)
    {
        $response['errors']['main-factor'] = ERROR_TAX_RANGE;
    }

    // If no validation errors, proceed to insert
    if (empty($response['errors']))
    {
        // Insert new tax
        $records = [
            'factor' => round($main['factor'] / 100, 4)
        ];
        $db->insert('tax', $records);

        $response['success'] = true;
        $response['message'] = SUCCESS_CREATE_TAX;
        $response['redirect_url'] = $seoUrl->generate('administration/tax.php');
    }
}

echo json_encode($response);
exit;
