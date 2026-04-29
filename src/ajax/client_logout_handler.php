<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

if (empty($auth->getUser()['client_id']))
{
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    sendResponse(false, TEXT_INVALID_REQUEST_METHOD, ['redirect_url' => $seoUrl->generate('index.php')]);
}

// Logout process
try
{
    // Get the redirect URL before destroying the session
    // because after session_destroy() we lose the current language info.
    $redirectUrl = $seoUrl->generate('index.php');
    // Clear all session data and destroy the session
    $_SESSION = [];
    session_destroy();

    // Return successful response and redirect URL (homepage)
    sendResponse(true, TEXT_SUCCESSFUL_LOGOUT, [
        'redirect_url' => $redirectUrl
    ]);
}
catch (Exception $e)
{
    // Return success: true always to force JS redirection,
    // since the purpose of logout is to end the session.
    sendResponse(true, sprintf(TEXT_GENERIC_ERROR, $e->getMessage()), [
        'redirect_url' => $seoUrl->generate('index.php')
    ]);
}
