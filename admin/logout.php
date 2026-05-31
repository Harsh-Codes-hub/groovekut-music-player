<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: /groovekut/admin/index.php');
exit;
