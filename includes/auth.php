<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Login User
|--------------------------------------------------------------------------
*/
function loginUser($username, $password)
{
    global $conn;

    $username = mysqli_real_escape_string($conn, $username);

    $query = mysqli_query(
        $conn,
        "SELECT * FROM users WHERE username='$username' LIMIT 1"
    );

    if (mysqli_num_rows($query) > 0) {

        $user = mysqli_fetch_assoc($query);

        /*
        |--------------------------------------------------------------------------
        | Password Verify
        |--------------------------------------------------------------------------
        */
        if (password_verify($password, $user['password'])) {

            return $user;
        }
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/
function isLoggedIn()
{
    return isset($_SESSION['login']);
}

/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/
function currentUser()
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama'     => $_SESSION['nama'],
        'role'     => $_SESSION['role']
    ];
}

/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/
function requireLogin()
{
    if (!isLoggedIn()) {

        header("Location: /absensi-qr/login.php");

        exit;
    }
}