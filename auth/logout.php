<?php
/**
 * logout.php
 * Menghancurkan session dan redirect ke halaman login.
 */

session_start();
session_unset();
session_destroy();

header('Location: /bakecost/login.php');
exit;

