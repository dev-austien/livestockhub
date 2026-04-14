<?php
session_start();
session_unset();
session_destroy();
header("Location: ../frontend/pages/auth/login.php");
exit();
?>