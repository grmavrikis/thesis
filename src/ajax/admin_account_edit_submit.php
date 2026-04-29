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

    $data = [
        'user_id'          => trim($main['user_id'] ?? ''),
        'admin_id'         => trim($main['admin_id'] ?? ''),
        'firstname'        => trim($main['firstname'] ?? ''),
        'lastname'         => trim($main['lastname'] ?? ''),
        'email'            => filter_var($main['email'] ?? '', FILTER_SANITIZE_EMAIL),
        'phone'            => trim($main['phone'] ?? ''),
        'username'         => trim($main['username'] ?? ''),
        'password'         => $main['password'] ?? '',
        'confirm_password' => $main['confirm_password'] ?? ''
    ];

    // check required fields (except password)
    foreach ($data as $key => $value)
    {
        if (empty($value) && !in_array($key, ['password', 'confirm_password']))
        {
            $response['errors']["main-$key"] = ERROR_REQUIRED_FIELD;
        }
    }

    // User Input Validity Checks
    if (strlen($data['firstname']) < 3)
    {
        $response['errors']['main-firstname'] = ERROR_FIRSTNAME_SHORT;
    }

    if (strlen($data['lastname']) < 3)
    {
        $response['errors']['main-lastname'] = ERROR_LASTNAME_SHORT;
    }

    if (strlen($data['phone']) < 10)
    {
        $response['errors']['main-phone'] = ERROR_PHONE_SHORT;
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
    {
        $response['errors']['main-email'] = ERROR_INVALID_EMAIL;
    }

    if (strlen($data['username']) < 5)
    {
        $response['errors']['main-username'] = ERROR_USERNAME_SHORT;
    }

    // Check password only if it's provided
    if (!empty($data['password']))
    {
        if (strlen($data['password']) < 8)
        {
            $response['errors']['main-password'] = ERROR_PASSWORD_SHORT;
        }

        if ($data['password'] !== $data['confirm_password'])
        {
            $response['errors']['main-confirm_password'] = ERROR_PASSWORD_MISMATCH;
        }
    }

    // If no validation errors, proceed with database operations
    if (empty($response['errors']))
    {
        try
        {
            // Check if the email already exists
            $existingEmail = $db->query("SELECT dietitian_id FROM dietitian WHERE email = ? AND dietitian_id != ?", [
                $data['email'],
                $data['admin_id']
            ])->fetch();

            if ($existingEmail)
            {
                $response['errors']['main-email'] = ERROR_EMAIL_EXISTS;
                echo json_encode($response);
                exit;
            }

            // Update user table
            $user_records = [
                'username' => $data['username']
            ];
            if (!empty($data['password']))
            {
                $user_records['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }
            $db->update('user', $user_records, 'user_id = ?', [$data['user_id']]);

            // Update dietitian table
            $dietitian_records = [
                'first_name' => $data['firstname'],
                'last_name'  => $data['lastname'],
                'email'      => $data['email'],
                'phone'      => $data['phone']
            ];
            $db->update('dietitian', $dietitian_records, 'dietitian_id = ?', [$data['admin_id']]);

            $response['success'] = true;
            $response['redirect_url'] = $seoUrl->generate('administration/admin_account.php');
        }
        catch (Exception $e)
        {
            $response['errors']['main-generic'] = "Database Error: " . $e->getMessage();
        }
    }
}

echo json_encode($response);
exit;
