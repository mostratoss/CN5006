<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

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
  <title>Εγγραφή</title>

  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/login-register.css">
</head>

<body>

<div class="form-bg">

  <div class="form-wrapper">

    <img src="../images/logo1.png" class="form-logo" alt="Logo">

    <h2>Εγγραφή</h2>

    <?php if (!empty($msg)): ?>
      <div class="msg <?= $type ?>">
        <?= $msg ?>
      </div>
    <?php endif; ?>

    <form action="../php/register_action.php" method="POST">

      <label>Username</label>
      <input type="text" name="username" required>

      <label>Email</label>
      <input type="email" name="email" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Ρόλος</label>
      <select name="role" required>
        <option value="student">Φοιτητής</option>
        <option value="teacher">Καθηγητής</option>
      </select>

      <label>Ειδικός κωδικός</label>
      <input type="text" name="secret" required>

      <button type="submit">Εγγραφή</button>

      <div class="bottom-link">
        Έχεις ήδη λογαριασμό;
        <a href="login.php">Σύνδεση εδώ</a>
      </div>

      <a class="back-home" href="../index.html">Επιστροφή στην Αρχική</a>

    </form>

  </div>

</div>

</body>
</html>
