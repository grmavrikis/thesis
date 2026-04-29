<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['admin_id']) && empty($auth->getUser()['client_id']))
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
        if (empty($value) && !in_array($key, ['user_id', 'manager_client_id', 'email', 'phone', 'username', 'password', 'confirm_password']))
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

    // If manager_client_id exists then the client id a dependent client, 
    // so they have no login options nor email/phone validity requirements
    if (empty($main['manager_client_id']))
    {
        if (!filter_var($main['email'], FILTER_VALIDATE_EMAIL))
        {
            $response['errors']['main-email'] = ERROR_INVALID_EMAIL;
        }

        if (strlen($main['phone']) < 10)
        {
            $response['errors']['main-phone'] = ERROR_PHONE_SHORT;
        }

        if (empty($auth->getUser()['client_id']))
        {
            if (strlen($main['username']) < 5)
            {
                $response['errors']['main-username'] = ERROR_USERNAME_SHORT;
            }

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
        }
    }

    // If no validation errors, proceed to update the database
    if (empty($response['errors']))
    {
        try
        {
            // Check if the email already exists
            if (!empty($main['email']))
            {
                $existingEmail = $db->query("SELECT client_id FROM client WHERE email = ? and client_id != ?", [
                    $main['email'],
                    $main['client_id']
                ])->fetch();

                if ($existingEmail)
                {
                    $response['errors']['main-email'] = ERROR_EMAIL_EXISTS;
                    echo json_encode($response);
                    exit;
                }
            }

            if (empty($auth->getUser()['client_id']))
            {
                if (empty($main['manager_client_id']) && empty($auth->getUser()['client_id']))
                {
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
                }
                else if (empty($auth->getUser()['client_id']) && (!empty($main['manager_client_id']) && !empty($main['user_id'])) || (!empty($main['user_id']) && empty($main['username'])))
                {
                    // Delete the login info if the client swithes to dependant 
                    // or if the login details where removed
                    $db->delete('user', "user_id = ?", [$main['user_id']]);
                }

                if (empty($auth->getUser()['client_id']) && !empty($main['manager_client_id']))
                {
                    unset($main['user_id']);
                }

                if (empty($auth->getUser()['client_id']) && !empty($main['user_id']))
                {
                    $user_records = [
                        'active' => $main['active'] ?? 0
                    ];

                    $db->update('user', $user_records, 'user_id = ?', [$main['user_id']]);
                }

                // Insert or update user table
                if (empty($auth->getUser()['client_id']) && !empty($main['username']) && !empty($main['password']))
                {
                    $user_records = [
                        'active' => $main['active'] ?? 0,
                        'username' => $main['username'],
                        'password_hash' => password_hash($main['password'], PASSWORD_BCRYPT)
                    ];

                    if (!empty($main['user_id']))
                    {
                        $db->update('user', $user_records, 'user_id = ?', [$main['user_id']]);
                    }
                    else
                    {
                        $inserted_user = $db->insert('user', $user_records);
                        $main['user_id'] = $inserted_user[0] ?? null;
                    }
                }
            }

            // Update client table
            $client_records = [
                'first_name' => $main['firstname'],
                'last_name'  => $main['lastname'],
                'email'      => $main['email'],
                'phone'      => $main['phone'],
                'dob'        => $main['dob'],
                'gender'     => $main['gender'],
                'user_id'    => $main['user_id'] ?? null
            ];

            $db->update('client', $client_records, 'client_id = ?', [$main['client_id']]);

            if (empty($auth->getUser()['client_id']))
            {
                // Check if manager_client_id already exists
                $existingManager = $db->query("SELECT manager_client_id FROM client_relationships WHERE dependent_client_id = ?", [
                    $main['client_id']
                ])->fetch();

                // Handle client relationship.
                if (empty($main['manager_client_id']) && $existingManager)
                {
                    $db->delete('client_relationships', "dependent_client_id = ?", [$main['client_id']]);
                }
                else if (!empty($main['manager_client_id']) && $existingManager)
                {
                    $records = [
                        'manager_client_id' => $main['manager_client_id']
                    ];
                    $db->update('client_relationships', $records, 'dependent_client_id = ?', [$main['client_id']]);
                }
                else if (!empty($main['manager_client_id']) && !$existingManager)
                {
                    $records = [
                        'manager_client_id' => $main['manager_client_id'],
                        'dependent_client_id'  => $main['client_id']
                    ];
                    $db->insert('client_relationships', $records);
                }
            }

            $response['success'] = true;
            $response['message'] = SUCCESS_SAVE_CLIENT;
            if (!empty($auth->getUser()['client_id']))
            {
                $response['redirect_url'] = $seoUrl->generate('portal/clients.php');
            }
            else if (!empty($auth->getUser()['admin_id']))
            {
                $response['redirect_url'] = $seoUrl->generate('administration/clients.php');
            }
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
