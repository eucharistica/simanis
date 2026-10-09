<?php
declare(strict_types=1);
require_once __DIR__ . '/lib_auth.php';
logout();
header('Location: login.php');
exit;
