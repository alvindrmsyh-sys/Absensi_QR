<?php
/**
 * login.php
 * Halaman Login Admin & Guru
 */

session_start();

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

/*
|--------------------------------------------------------------------------
| Konstanta Default
|--------------------------------------------------------------------------
*/
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Absensi QR');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/absensi-qr');
}

/*
|--------------------------------------------------------------------------
| Redirect jika sudah login
|--------------------------------------------------------------------------
*/
if (function_exists('isLoggedIn') && isLoggedIn()) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

$error = '';

/*
|--------------------------------------------------------------------------
| Generate CSRF Token
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Proses Login
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Validasi CSRF
    |--------------------------------------------------------------------------
    */
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {

        $error = 'Request tidak valid.';

    } else {

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        /*
        |--------------------------------------------------------------------------
        | Validasi Input
        |--------------------------------------------------------------------------
        */
        if (empty($username) || empty($password)) {

            $error = 'Username dan password wajib diisi.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Login User
            |--------------------------------------------------------------------------
            */
            if (function_exists('loginUser')) {

                $user = loginUser($username, $password);

                if ($user) {

                    $_SESSION['login'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['role'] = $user['role'];

                    /*
                    |--------------------------------------------------------------------------
                    | Flash Message
                    |--------------------------------------------------------------------------
                    */
                    if (function_exists('setFlash')) {
                        setFlash('success', 'Selamat datang, ' . $user['nama']);
                    }

                    header("Location: " . BASE_URL . "/dashboard.php");
                    exit;

                } else {

                    $error = 'Username atau password salah.';

                }

            } else {

                $error = 'Function loginUser() tidak ditemukan.';

            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Helper Escape HTML
|--------------------------------------------------------------------------
*/
function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - <?= e(APP_NAME) ?></title>

    <!-- Bootstrap -->
    <link href="<?= BASE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0d6efd, #4f8cff);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .login-logo {
            width: 80px;
            height: 80px;
            background: #0d6efd;
            color: white;
            border-radius: 20px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            margin-bottom: 15px;
        }

        .login-title {
            font-weight: 800;
            font-size: 28px;
        }

        .form-control,
        .input-group-text {
            height: 50px;
        }

        .btn-primary {
            height: 50px;
            border-radius: 10px;
        }
    </style>
</head>

<body>

<div class="login-card">

    <!-- Logo -->
    <div class="text-center mb-4">
        <div class="login-logo">
            <i class="bi bi-qr-code-scan"></i>
        </div>

        <h2 class="login-title"><?= e(APP_NAME) ?></h2>

        <p class="text-muted small">
            Sistem Absensi QR Code
        </p>
    </div>

    <!-- Error -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle"></i>
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Form Login -->
    <form method="POST" autocomplete="off">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION['csrf_token']) ?>"
        >

        <!-- Username -->
        <div class="mb-3">
            <label class="form-label fw-semibold">
                Username
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-person"></i>
                </span>

                <input
                    type="text"
                    name="username"
                    class="form-control"
                    placeholder="Masukkan username"
                    value="<?= e($_POST['username'] ?? '') ?>"
                    required
                >
            </div>
        </div>

        <!-- Password -->
        <div class="mb-4">
            <label class="form-label fw-semibold">
                Password
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-lock"></i>
                </span>

                <input
                    type="password"
                    name="password"
                    id="passwordInput"
                    class="form-control"
                    placeholder="Masukkan password"
                    required
                >

                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    onclick="togglePassword()"
                >
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>

            </div>
        </div>

        <!-- Button -->
        <button type="submit" class="btn btn-primary w-100 fw-bold">
            <i class="bi bi-box-arrow-in-right me-1"></i>
            Login
        </button>

    </form>

    <!-- Default Login -->
    <div class="text-center mt-4">
        <small class="text-muted">
            Default Login:
            <br>
            <code>admin</code> / <code>password</code>
        </small>
    </div>

</div>

<!-- Bootstrap -->
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>

<script>
function togglePassword() {

    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('eyeIcon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>

</body>
</html>