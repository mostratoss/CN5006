<?php
session_start();
session_unset();
session_destroy();

header("Location: ../pages/login.php?msg=Αποσυνδεθήκατε+επιτυχώς&type=success");
exit();
?>
