<?php
/**
 * Entry point — redirect to login if no active LGU session.
 */
session_start();
if (isset($_SESSION['lgu_user'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
