<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=Πρέπει+να+συνδεθείτε&type=error");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    echo "Forbidden Action";
    exit();
}

$username = $_SESSION['username'] ?? 'Καθηγητής';
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Teacher Home</title>

  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/login-register.css">

  <style>
    .home-container{ width:90%; max-width:900px; margin:0 auto; }
    .menu{ display:grid; grid-template-columns:1fr; gap:12px; margin-top:14px; }
    .menu a{ display:block; text-decoration:none; font-weight:900; font-family:"Lato",sans-serif; padding:14px; border-radius:14px; background: rgba(255,255,255,0.95); box-shadow: 0 12px 35px rgba(0,0,0,0.18); color:#b71c1c; }
    .menu a:hover{ opacity:.92; }
  </style>
</head>
<body>
<div class="form-bg">
  <div class="form-wrapper home-container">
    <img src="../images/logo1.png" class="form-logo" alt="Logo">
    <h1 style="margin:0;color:#b71c1c;font-family:'Lato',sans-serif;">Teacher Panel</h1>
    <div class="msg success">Συνδεδεμένος ως <?php echo htmlspecialchars($username); ?></div>

    <div class="menu">
      <a href="teacher_courses.php"> Τα Μαθήματά μου</a>
      <a href="teacher_assignments.php"> Εργασίες</a>
      <a href="teacher_submissions.php"> Υποβολές</a>
    </div>

    <form action="../php/logout.php" method="POST" style="margin-top:14px;">
      <button type="submit">Αποσύνδεση</button>
    </form>
  </div>
</div>
</body>
</html>
