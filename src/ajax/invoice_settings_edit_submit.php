<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['admin_id']))
{
    exit();
}

$response = ['success' => false, 'errors' => []];
$fileManager = new FileManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $main = $_POST['main'] ?? [];

    // Check Required Fields
    foreach ($main as $key => $value)
    {
        if (empty($value) && !in_array($key, ['logo_path']))
        {
            $response['errors']["main-$key"] = ERROR_REQUIRED_FIELD;
        }
    }

    // User Input Validity Checks
    if (strlen($main['company_name']) < 3)
    {
        $response['errors']['main-company_name'] = ERROR_COMPANY_NAME_SHORT;
    }
    if (strlen($main['company_title']) < 3)
    {
        $response['errors']['main-company_title'] = ERROR_COMPANY_TITLE_SHORT;
    }
    if (!preg_match('/^[0-9]{9}$/', $main['vat_number']))
    {
        $response['errors']['main-vat_number'] = ERROR_INVALID_VAT_NUMBER;
    }
    if (strlen($main['address_street']) < 2)
    {
        $response['errors']['main-address_street'] = ERROR_ADDRESS_STREET_SHORT;
    }
    if (strlen($main['city']) < 2)
    {
        $response['errors']['main-city'] = ERROR_CITY_SHORT;
    }
    if (!preg_match('/^[0-9]{5}$/', $main['postal_code']))
    {
        $response['errors']['main-postal_code'] = ERROR_INVALID_POSTAL_CODE;
    }
    if (strlen($main['phone']) < 10)
    {
        $response['errors']['main-phone'] = ERROR_PHONE_SHORT;
    }
    if (!filter_var($main['email'], FILTER_VALIDATE_EMAIL))
    {
        $response['errors']['main-email'] = ERROR_INVALID_EMAIL;
    }

    // Logo file validation
    if (
        isset($_FILES['main']['name']['logo_path']) &&
        $_FILES['main']['error']['logo_path'] !== UPLOAD_ERR_NO_FILE
    )
    {
        $v_result = $fileManager->validate(
            $_FILES['main']['name']['logo_path'],
            $_FILES['main']['size']['logo_path'],
            $_FILES['main']['error']['logo_path'],
            ['jpg']
        );
        if ($v_result !== true)
        {
            $response['errors']["main-logo_path"] = $v_result;
        }
    }

    // If no validation errors, proceed with database operations
    if (empty($response['errors']))
    {
        try
        {
            // Update invoice_settings table
            $records = [
                'company_name' => $main['company_name'],
                'company_title' => $main['company_title'],
                'vat_number' => $main['vat_number'],
                'tax_office' => $main['tax_office'],
                'address_street' => $main['address_street'],
                'address_number' => $main['address_number'],
                'city' => $main['city'],
                'postal_code' => $main['postal_code'],
                'email' => $main['email'],
                'phone' => $main['phone']
            ];

            if (
                isset($_FILES['main']['name']['logo_path']) &&
                $_FILES['main']['error']['logo_path'] !== UPLOAD_ERR_NO_FILE
            )
            {
                $new_logo = $fileManager->handleUpload(
                    $_FILES['main']['name']['logo_path'] ?? null,
                    $_FILES['main']['tmp_name']['logo_path'] ?? null,
                    $_FILES['main']['error']['logo_path'] ?? UPLOAD_ERR_NO_FILE,
                    "invoice_settings_1"
                );

                if ($new_logo)
                {
                    $records['logo_path'] = $new_logo;
                }
            }

            $db->update('invoice_settings', $records, 'invoice_settings_id = 1', []);

            $response['success'] = true;
            $response['redirect_url'] = $seoUrl->generate('administration/invoice_settings.php');
        }
        catch (Exception $e)
        {
            $response['errors']['main-generic'] = "Database Error: " . $e->getMessage();
        }
    }
}

echo json_encode($response);
exit;
