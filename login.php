<?php
declare(strict_types=1);
require_once __DIR__ . '/lib_auth.php';

auth_session_start();
if (auth_user()) { header('Location: index.php'); exit; }

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $r = attempt_login($_POST['username'] ?? '', $_POST['password'] ?? '');
  if ($r['ok']) { header('Location: index.php'); exit; }
  $error = $r['error'] ?? 'Login gagal';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - SIMANIS</title>
  <link rel="icon" href="favicon.ico">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <style>
    body{min-height:100vh;background:linear-gradient(135deg,#0d6efd22,#6f42c122);}
    .card{border:0;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.12);}
    .app-logo{height:64px;object-fit:contain;}
  </style>
</head>
<body class="d-flex align-items-center">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card">
        <div class="card-body p-4">

          <div class="text-center mb-3">
            <img src="logo.png" class="app-logo" alt="Logo">
          </div>

          <h4 class="mb-1 text-center">MONITORING REKAM MEDIS</h4>
          <p class="text-muted mb-4 text-center">Silakan login untuk melanjutkan.</p>

          <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
          <?php endif; ?>

          <form method="post" autocomplete="off">
            <div class="form-group">
              <label>Username</label>
              <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary btn-block">Login</button>
          </form>
          <small class="text-muted d-block mt-3">Akses terbatas untuk petugas.</small>    
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
