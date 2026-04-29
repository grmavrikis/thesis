<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

$response = ['success' => false, 'errors' => []];
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $main = $_POST['main'] ?? [];

    $data = [
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

    // Check Required Fields
    foreach ($data as $key => $value)
    {
        if (empty($value) && $key !== 'confirm_password')
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

    if (strlen($data['password']) < 8)
    {
        $response['errors']['main-password'] = ERROR_PASSWORD_SHORT;
    }

    if ($data['password'] !== $data['confirm_password'])
    {
        $response['errors']['main-confirm_password'] = ERROR_PASSWORD_MISMATCH;
    }

    // No Errors Found
    if (empty($response['errors']))
    {
        // Check if the username already exists
        $existingUsername = $db->query("SELECT user_id FROM user WHERE username = ?", [
            $data['username']
        ])->fetch();

        if ($existingUsername)
        {
            $response['errors']['main-username'] = ERROR_USERNAME_EXISTS;
            echo json_encode($response);
            exit;
        }

        // Check if the email already exists
        $existingEmail = $db->query("SELECT client_id FROM client WHERE email = ?", [
            $data['email']
        ])->fetch();

        if ($existingEmail)
        {
            $response['errors']['main-email'] = ERROR_EMAIL_EXISTS;
            echo json_encode($response);
            exit;
        }

        // Insert new client
        $records = [
            'username' => $data['username'],
            'password_hash'  => password_hash($data['password'], PASSWORD_BCRYPT)
        ];
        $inserted_user = $db->insert('user', $records);
        $user_id = $inserted_user[0] ?? null;

        $records = [
            'first_name' => $data['firstname'],
            'last_name'  => $data['lastname'],
            'email'      => $data['email'],
            'phone'      => $data['phone'],
            'dob'        => $data['dob'],
            'gender'     => $data['gender'],
            'user_id'    => $user_id
        ];
        $db->insert('client', $records);

        $response['success'] = true;
        $response['message'] = SUCCESS_REGISTRATION;
        $response['redirect_url'] = $seoUrl->generate('index.php');
    }
}

echo json_encode($response);
exit;
