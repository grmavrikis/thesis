<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['client_id']))
{
    exit();
}

$response = ['success' => false, 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $main = $_POST['main'] ?? [];

    $data = [
        'user_id'          => trim($main['user_id'] ?? ''),
        'client_id'        => trim($main['client_id'] ?? ''),
        'firstname'        => trim($main['firstname'] ?? ''),
        'lastname'         => trim($main['lastname'] ?? ''),
        'email'            => filter_var($main['email'] ?? '', FILTER_SANITIZE_EMAIL),
        'phone'            => trim($main['phone'] ?? ''),
        'dob'              => trim($main['dob'] ?? ''),
        'gender'           => trim($main['gender'] ?? ''),
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
    if (strlen($data['firstname']) < 2)
    {
        $response['errors']['main-firstname'] = ERROR_FIRSTNAME_SHORT;
    }

    if (strlen($data['lastname']) < 2)
    {
        $response['errors']['main-lastname'] = ERROR_LASTNAME_SHORT;
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
    {
        $response['errors']['main-email'] = ERROR_INVALID_EMAIL;
    }

    if (strlen($data['phone']) < 10)
    {
        $response['errors']['main-phone'] = ERROR_PHONE_SHORT;
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
            $existingEmail = $db->query("SELECT client_id FROM client WHERE email = ? and client_id != ?", [
                $data['email'],
                $data['client_id']
            ])->fetch();

            if ($existingEmail)
            {
                $response['errors']['main-email'] = ERROR_EMAIL_EXISTS;
                echo json_encode($response);
                exit;
            }

            // Check if username already exists
            $existingUsername = $db->query("SELECT user_id FROM user WHERE username = ? AND user_id != ?", [
                $data['username'],
                $data['user_id']
            ])->fetch();

            if ($existingUsername)
            {
                $response['errors']['main-username'] = ERROR_USERNAME_EXISTS;
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

            // Update client table
            $client_records = [
                'first_name' => $data['firstname'],
                'last_name'  => $data['lastname'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'dob'        => $data['dob'],
                'gender'     => $data['gender']
            ];
            $db->update('client', $client_records, 'client_id = ?', [$data['client_id']]);

            $response['success'] = true;
            $response['redirect_url'] = $seoUrl->generate('portal/account.php');
        }
        catch (Exception $e)
        {
            $response['errors']['main-generic'] = "Database Error: " . $e->getMessage();
        }
    }
}

echo json_encode($response);
exit;
