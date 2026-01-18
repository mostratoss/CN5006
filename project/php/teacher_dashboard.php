<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=Πρέπει+να+συνδεθείτε&type=error");
    exit();
}

if ($_SESSION['role'] !== "teacher") {
    header("Location: student_dashboard.php?msg=Δεν+έχετε+πρόσβαση&type=error");
    exit();
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <title>Πίνακας Καθηγητή</title>

  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/login-register.css">
</head>

<body>

<div class="form-bg">

  <div class="form-wrapper">

      <img src="../images/logo1.png" class="form-logo" alt="Logo">

      <h2>Καλώς ήρθατε, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>

      <div class="msg success">Έχετε συνδεθεί ως Καθηγητής.</div>

      <form action="../php/teacher_home.php" method="POST">
      <button type="submit">Συνέχεια</button> 
      </form>
      <br>
      <form action="../php/logout.php" method="POST"> 
    <button type="submit">Αποσύνδεση</button>
</form>


  </div>

</div>

</body>
</html>
