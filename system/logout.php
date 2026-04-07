<?php
require_once __DIR__ . '/auth.php';
destroyAuthToken();
header('Location: login.php');
exit;
