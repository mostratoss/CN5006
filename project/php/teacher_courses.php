<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    echo "Forbidden Action";
    exit();
}

require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn, "utf8mb4");

$username = $_SESSION['username'] ?? 'Καθηγητής';

$teacherIdDb = 0;
$stmtT = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND role = 'teacher' LIMIT 1");
mysqli_stmt_bind_param($stmtT, "s", $username);
mysqli_stmt_execute($stmtT);
$resT = mysqli_stmt_get_result($stmtT);
if ($rowT = mysqli_fetch_assoc($resT)) {
    $teacherIdDb = (int)$rowT['id'];
}
mysqli_stmt_close($stmtT);

$msg = '';
$type = '';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $semester = trim($_POST['semester'] ?? '');

    if ($code === '' || $title === '') {
        $msg = 'Συμπλήρωσε κωδικό και τίτλο.';
        $type = 'error';
    } elseif ($teacherIdDb === 0) {
        $msg = 'Δεν βρέθηκε ο καθηγητής στον πίνακα users.';
        $type = 'error';
    } else {
        $sql = "INSERT INTO courses (code, title, semester, teacher_id) VALUES (?,?,?,?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssi", $code, $title, $semester, $teacherIdDb);

        try {
            mysqli_stmt_execute($stmt);
            $msg = 'Το μάθημα δημιουργήθηκε.';
            $type = 'success';
        } catch (mysqli_sql_exception $e) {
            if ((int)$e->getCode() === 1062) {
                $msg = 'Υπάρχει ήδη μάθημα με αυτόν τον κωδικό.';
            } else {
                $msg = 'DB Error: ' . $e->getMessage();
            }
            $type = 'error';
        }

        mysqli_stmt_close($stmt);
    }
}

$courses = [];
$sql = "SELECT id, code, title, semester, created_at FROM courses ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $courses[] = $row;
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Teacher - Μαθήματα</title>

  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/login-register.css">

  <style>
    .home-container{ width:90%; max-width:1100px; margin:0 auto; }
    .topbar{ display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
    .title{ margin:0; color:#b71c1c; font-family:"Lato",sans-serif; font-size:1.6rem; }
    .actions{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .btn-like{ display:inline-block; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:700; font-family:"Lato",sans-serif; border:none; cursor:pointer; }
    .btn-secondary{ background:#eee; color:#333; }
    .btn-secondary:hover{ opacity:.9; }
    .btn-danger{ background:#b71c1c; color:#fff; }
    .btn-danger:hover{ background:#8e0000; }

    .panel{
      background: rgba(255,255,255,0.95);
      border-radius: 18px;
      box-shadow: 0 12px 35px rgba(0,0,0,0.18);
      padding: 18px;
      margin-bottom: 16px;
    }

    .form-grid{ display:grid; grid-template-columns: 1fr 2fr 1fr; gap: 10px; }
    .form-grid input{
      width:100%;
      padding: 0.85rem;
      border: 1px solid #ccc;
      border-radius: 10px;
      outline:none;
      transition:.25s;
      font-size: 1rem;
    }
    .form-grid input:focus{
      border-color:#b71c1c;
      box-shadow:0 0 6px rgba(183,28,28,0.28);
    }
    .form-grid button{
      grid-column: 1 / -1;
      padding: 0.9rem;
      border-radius: 10px;
      border: none;
      font-weight: 900;
      cursor: pointer;
      background: #2e7d32;
      color: #fff;
      transition:.25s;
      font-family:"Lato",sans-serif;
      font-size: 1.05rem;
    }
    .form-grid button:hover{ background:#1b5e20; }

    .grid{ display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
    .card{
      background: rgba(255,255,255,0.95);
      border-radius: 18px;
      box-shadow: 0 12px 35px rgba(0,0,0,0.18);
      padding: 18px;
    }
    .card h3{ margin:0 0 6px; color:#b71c1c; font-family:"Lato",sans-serif; font-size:1.2rem; }
    .meta{ margin:0; opacity:.92; }
    .badge{
      display:inline-block;
      margin-top:10px;
      padding:6px 10px;
      border-radius:999px;
      background: rgba(183,28,28,0.12);
      color:#7b0f0f;
      font-weight:800;
      font-family:"Lato",sans-serif;
      font-size:.9rem;
    }

    @media (max-width: 900px){
      .form-grid{ grid-template-columns: 1fr; }
    }
    @media (max-width: 768px){
      .grid{ grid-template-columns:1fr; }
    }
  </style>
</head>

<body>
<div class="form-bg">
  <div class="form-wrapper home-container">

    <div class="topbar">
      <div>
        <img src="../images/logo1.png" class="form-logo" alt="Logo">
        <h1 class="title"> Τα Μαθήματά μου (Teacher)</h1>
        <div class="msg success">Συνδεδεμένος ως <?php echo h($username); ?></div>
      </div>

      <div class="actions">
        <a class="btn-like btn-secondary" href="teacher_home.php">Πίσω</a>
        <form action="../php/logout.php" method="POST" style="margin:0;">
          <button type="submit" class="btn-like btn-danger">Αποσύνδεση</button>
        </form>
      </div>
    </div>

    <?php if ($msg !== ''): ?>
      <div class="msg <?php echo h($type); ?>"><?php echo h($msg); ?></div>
    <?php endif; ?>

    <div class="panel">
      <h3 style="margin:0 0 12px; color:#b71c1c; font-family:'Lato',sans-serif;">Δημιουργία Μαθήματος</h3>
      <form method="POST" action="">
        <div class="form-grid">
          <input type="text" name="code" placeholder="Κωδικός (π.χ. CN5006)" required>
          <input type="text" name="title" placeholder="Τίτλος μαθήματος" required>
          <input type="text" name="semester" placeholder="Εξάμηνο (π.χ. 2025-26)">
          <button type="submit">Δημιουργία</button>
        </div>
      </form>
    </div>

    <?php if (count($courses) === 0): ?>
      <div class="panel" style="text-align:center; font-weight:800;">Δεν έχουν δημιουργηθεί μαθήματα ακόμα.</div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($courses as $c): ?>
          <?php
            $title = h($c['title'] ?? '');
            $code = h($c['code'] ?? '');
            $sem = h($c['semester'] ?? '');
            $id = (int)($c['id'] ?? 0);
          ?>
          <div class="card">
            <h3><?php echo $title; ?></h3>
            <p class="meta"><b>Κωδικός:</b> <?php echo $code !== '' ? $code : '—'; ?></p>
            <p class="meta"><b>Εξάμηνο:</b> <?php echo $sem !== '' ? $sem : '—'; ?></p>
            <span class="badge">Course ID: <?php echo $id; ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
