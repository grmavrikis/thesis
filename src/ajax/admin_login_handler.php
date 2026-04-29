<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    sendResponse(false, TEXT_INVALID_REQUEST_METHOD);
}

// Get the JSON input
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// Validate input
if (empty($data['username']) || empty($data['password']))
{
    sendResponse(false, TEXT_INVALID_INPUT);
}

$username = $data['username'];
$password = $data['password'];

try
{
    // Fetch user record
    $sql = "SELECT u.user_id, u.username, u.password_hash, d.dietitian_id
            FROM user u
            JOIN dietitian d ON u.user_id = d.user_id
            WHERE u.username = :username";

    $user_record = $db->query($sql, ['username' => $username])->fetch();

    // User not found
    if (!$user_record)
    {
        sendResponse(false, TEXT_INVALID_CREDENTIALS);
    }

    // Password does not match
    if (!password_verify($password, $user_record['password_hash']))
    {
        sendResponse(false, TEXT_INVALID_CREDENTIALS);
    }

    // Successful Login
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_record['user_id'];
    $_SESSION['username'] = $user_record['username'];
    $_SESSION['admin_id'] = $user_record['dietitian_id'];
    $_SESSION['user_type'] = 'admin';

    sendResponse(true, TEXT_SUCCESSFUL_LOGIN, [
        'user_id' => $user_record['user_id'],
        'admin_id' => $user_record['dietitian_id'],
        'redirect_url' => $seoUrl->generate('administration/dashboard.php')
    ]);
}
catch (Exception $e)
{
    // Throw generic error message
    sendResponse(false, sprintf(TEXT_GENERIC_ERROR, $e->getMessage()));
}
