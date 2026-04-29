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

    // If no validation errors, proceed to update the database
    if (empty($response['errors']))
    {
        try
        {
            // Update tax record
            $records = [
                'factor' => round($main['factor'] / 100, 4)
            ];
            $updated_tax = $db->update('tax', $records, 'tax_id = :tax_id', ['tax_id' => $main['tax_id']]);

            $response['success'] = true;
            $response['message'] = SUCCESS_SAVE_TAX;
            $response['redirect_url'] = $seoUrl->generate('administration/tax.php');
        }
        catch (Exception $e)
        {
            $response['success'] = false;
            $response['errors']['general'] = "Database Error: " . $e->getMessage();
        }
    }
}

echo json_encode($response);
exit;
