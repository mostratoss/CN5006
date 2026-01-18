<?php
$msg = "";
$type = "";

if (isset($_GET['msg'])) {
    $msg  = htmlspecialchars($_GET['msg']);
    $type = htmlspecialchars($_GET['type'] ?? "error");
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <title>Σύνδεση</title>

  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/login-register.css">
</head>

<body>

<div class="form-bg">

  <div class="form-wrapper">

    <img src="../images/logo1.png" class="form-logo" alt="Logo">

    <h2>Σύνδεση</h2>

    <?php if (!empty($msg)): ?>
      <div class="msg <?= $type ?>">
        <?= $msg ?>
      </div>
    <?php endif; ?>

    <form action="../php/login.php" method="POST">

      <label>Email</label>
      <input type="email" name="email" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <button type="submit">Σύνδεση</button>

      <div class="bottom-link">
        Δεν έχεις λογαριασμό;
        <a href="register.php">Εγγραφή εδώ</a>
      </div>

      <a class="back-home" href="../index.html">Επιστροφή στην Αρχική</a>

    </form>

  </div>

</div>

</body>
</html>
