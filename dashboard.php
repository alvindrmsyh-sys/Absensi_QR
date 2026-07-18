<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$user = currentUser();
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Dashboard</title>

    <link href="<?= BASE_URL ?>/assets/css/bootstrap.min.css"
          rel="stylesheet">

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card shadow border-0">

        <div class="card-body">

            <h2>
                Selamat Datang,
                <?= clean($user['nama']) ?>
            </h2>

            <p>
                Anda login sebagai:
                <strong><?= clean($user['role']) ?></strong>
            </p>

            <hr>

            <a href="<?= BASE_URL ?>/logout.php"
               class="btn btn-danger">

               Logout

            </a>

        </div>

    </div>

</div>

</body>
</html>