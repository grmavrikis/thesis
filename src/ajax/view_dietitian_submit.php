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

    // Check Required Fields
    foreach ($main as $key => $value)
    {
        if (empty($value) && !in_array($key, ['password', 'confirm_password']))
        {
            $response['errors']["main-$key"] = ERROR_REQUIRED_FIELD;
        }
    }

    // User Input Validity Checks
    if (strlen($main['firstname']) < 3)
    {
        $response['errors']['main-firstname'] = ERROR_FIRSTNAME_SHORT;
    }

    if (strlen($main['lastname']) < 3)
    {
        $response['errors']['main-lastname'] = ERROR_LASTNAME_SHORT;
    }

    if (!filter_var($main['email'], FILTER_VALIDATE_EMAIL))
    {
        $response['errors']['main-email'] = ERROR_INVALID_EMAIL;
    }

    if (strlen($main['phone']) < 10)
    {
        $response['errors']['main-phone'] = ERROR_PHONE_SHORT;
    }

    if (strlen($main['username']) < 5)
    {
        $response['errors']['main-username'] = ERROR_USERNAME_SHORT;
    }

    // Check password only if it's provided
    if (!empty($main['password']))
    {
        if (strlen($main['password']) < 8)
        {
            $response['errors']['main-password'] = ERROR_PASSWORD_SHORT;
        }

        if ($main['password'] !== $main['confirm_password'])
        {
            $response['errors']['main-confirm_password'] = ERROR_PASSWORD_MISMATCH;
        }
    }

    // If no validation errors, proceed to update the database
    if (empty($response['errors']))
    {
        try
        {
            // Check if the email already exists
            $existingEmail = $db->query("SELECT dietitian_id FROM dietitian WHERE email = ? and dietitian_id != ?", [
                $main['email'],
                $main['dietitian_id']
            ])->fetch();

            if ($existingEmail)
            {
                $response['errors']['main-email'] = ERROR_EMAIL_EXISTS;
                echo json_encode($response);
                exit;
            }

            // Check if username already exists
            $existingUsername = $db->query("SELECT user_id FROM user WHERE username = ? AND user_id != ?", [
                $main['username'],
                $main['user_id']
            ])->fetch();

            if ($existingUsername)
            {
                $response['errors']['main-username'] = ERROR_USERNAME_EXISTS;
                echo json_encode($response);
                exit;
            }

            // Update user table
            $user_records = [
                'username' => $main['username']
            ];

            if (!empty($main['password']))
            {
                $user_records['password_hash'] = password_hash($main['password'], PASSWORD_BCRYPT);
            }

            $db->update('user', $user_records, 'user_id = ?', [$main['user_id']]);

            // Update dietitian table
            $dietitian_records = [
                'first_name' => $main['firstname'],
                'last_name'  => $main['lastname'],
                'email'      => $main['email'],
                'phone'      => $main['phone']
            ];
            $db->update('dietitian', $dietitian_records, 'dietitian_id = ?', [$main['dietitian_id']]);

            $response['success'] = true;
            $response['message'] = SUCCESS_SAVE_DIETITIAN;
            $response['redirect_url'] = $seoUrl->generate('administration/dietitian.php');
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
