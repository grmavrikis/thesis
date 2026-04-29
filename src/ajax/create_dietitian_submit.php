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
        if (empty($value))
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

    if (strlen($main['phone']) < 10)
    {
        $response['errors']['main-phone'] = ERROR_PHONE_SHORT;
    }

    if (!filter_var($main['email'], FILTER_VALIDATE_EMAIL))
    {
        $response['errors']['main-email'] = ERROR_INVALID_EMAIL;
    }

    if (strlen($main['username']) < 5)
    {
        $response['errors']['main-username'] = ERROR_USERNAME_SHORT;
    }

    if (strlen($main['password']) < 8)
    {
        $response['errors']['main-password'] = ERROR_PASSWORD_SHORT;
    }

    if ($main['password'] !== $main['confirm_password'])
    {
        $response['errors']['main-confirm_password'] = ERROR_PASSWORD_MISMATCH;
    }

    // If no validation errors, proceed to insert
    if (empty($response['errors']))
    {
        // Check if the username already exists
        $existingUsername = $db->query("SELECT user_id FROM user WHERE username = ?", [
            $main['username']
        ])->fetch();

        if ($existingUsername)
        {
            $response['errors']['main-username'] = ERROR_USERNAME_EXISTS;
            echo json_encode($response);
            exit;
        }

        // Check if the email already exists
        $existingEmail = $db->query("SELECT dietitian_id FROM dietitian WHERE email = ?", [
            $main['email']
        ])->fetch();

        if ($existingEmail)
        {
            $response['errors']['main-email'] = ERROR_EMAIL_EXISTS;
            echo json_encode($response);
            exit;
        }

        // Insert new dietitian
        $records = [
            'username' => $main['username'],
            'password_hash'  => password_hash($main['password'], PASSWORD_BCRYPT)
        ];
        $inserted_user = $db->insert('user', $records);
        $user_id = $inserted_user[0] ?? null;

        $records = [
            'first_name' => $main['firstname'],
            'last_name'  => $main['lastname'],
            'email'      => $main['email'],
            'phone'      => $main['phone'],
            'user_id'    => $user_id
        ];
        $db->insert('dietitian', $records);

        $response['success'] = true;
        $response['message'] = SUCCESS_CREATE_DIETITIAN;
        $response['redirect_url'] = $seoUrl->generate('administration/dietitian.php');
    }
}

echo json_encode($response);
exit;
