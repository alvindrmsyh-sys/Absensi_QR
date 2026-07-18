<?php

/*
|--------------------------------------------------------------------------
| Escape HTML
|--------------------------------------------------------------------------
*/
function clean($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/
function redirect($url)
{
    header("Location: $url");
    exit;
}

/*
|--------------------------------------------------------------------------
| Flash Message
|--------------------------------------------------------------------------
*/
function setFlash($type, $msg)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'msg'  => $msg
    ];
}

function getFlash()
{
    if (isset($_SESSION['flash'])) {

        $flash = $_SESSION['flash'];

        unset($_SESSION['flash']);

        return $flash;
    }

    return null;
}