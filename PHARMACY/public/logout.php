<?php
require_once __DIR__.'/../models/auth.php';

logout();
header('Location: login.php');
exit;
?>
