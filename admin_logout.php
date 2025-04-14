<?php
session_start();

// unsets all session variables
$_SESSION = array();

session_destroy();

// redirects to the home page
header("Location: index.php");
exit();
?>