<?php

class Auth
{
    private $isLoggedIn = false;
    private $userData = [];

    /**
     * Starts the session and loads user data.
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE)
        {
            session_start();
        }
        $this->loadSessionData();
    }

    /**
     * Checks session data to determine if the user is logged in.
     */
    private function loadSessionData(): void
    {
        if (!empty($_SESSION['admin_id']))
        {
            $this->userData = [
                'id'        => $_SESSION['user_id'],
                'admin_id'  => $_SESSION['admin_id'],
                'username'  => $_SESSION['username'],
                'role'      => $_SESSION['user_type']
            ];
        }
        else if (!empty($_SESSION['client_id']))
        {
            $this->userData = [
                'id'        => $_SESSION['user_id'],
                'client_id' => $_SESSION['client_id'],
                'username'  => $_SESSION['username'],
                'role'      => $_SESSION['user_type']
            ];
        }
    }

    /**
     * Returns the user data.
     * @param string|null $key Optional specific key to retrieve from user data.
     * @return mixed The user data or specific key value, or null if not found.
     */
    public function getUser($key = null): mixed
    {
        if ($key)
        {
            return $this->userData[$key] ?? null;
        }
        return $this->userData;
    }

    /**
     * Returns the current access scope.
     * @return string 'public', 'client', or 'admin'
     */
    public function getAccessScope(): string
    {
        return $this->userData['role'] ?? 'public';
    }
}
