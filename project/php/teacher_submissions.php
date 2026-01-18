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

require_once __DIR__ . '/../config/db.php';

$teacherId = (int)$_SESSION['user_id'];
$username  = $_SESSION['username'] ?? 'Καθηγητής';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function formatDt($s){
    $ts = strtotime($s);
    if ($ts === false) return $s;
    return date("d/m/Y H:i", $ts);
}

$sql = "
SELECT
  s.id AS submission_id,
  u.username AS student_username,
  c.title AS course,
  c.code AS course_code,
  a.title AS assignment_title,
  s.submitted_at,
  s.file_name,
  CASE WHEN g.id IS NULL THEN 'Σε αναμονή' ELSE 'Βαθμολογήθηκε' END AS grade_status,
  CASE WHEN g.id IS NULL THEN '-' ELSE CAST(g.grade AS CHAR) END AS grade_value
FROM submissions s
INNER JOIN assignments a ON a.id = s.assignment_id
INNER JOIN courses c ON c.id = a.course_id
INNER JOIN users u ON u.id = s.student_id
LEFT JOIN grades g ON g.submission_id = s.id
WHERE c.teacher_id = ?
ORDER BY s.submitted_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$subs = [];
while ($row = mysqli_fetch_assoc($result)) $subs[] = $row;
mysqli_stmt_close($stmt);

$filtered = [];
if ($q === '') {
    $filtered = $subs;
} else {
    $ql = mb_strtolower($q, "UTF-8");
    foreach ($subs as $s) {
        $hay = mb_strtolower(
            ($s["student_username"] ?? '') . ' ' .
            ($s["course"] ?? '') . ' ' .
            ($s["course_code"] ?? '') . ' ' .
            ($s["assignment_title"] ?? '') . ' ' .
            ($s["grade_status"] ?? ''),
            "UTF-8"
        );
        if (mb_strpos($hay, $ql) !== false) $filtered[] = $s;
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Υποβολές</title>

  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/login-register.css">

  <style>
    .home-container{ width:90%; max-width:1200px; margin:0 auto; }
    .topbar{ display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
    .title{ margin:0; color:#b71c1c; font-family:"Lato",sans-serif; font-size:1.6rem; }
    .actions{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .btn-like{ display:inline-block; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:700; font-family:"Lato",sans-serif; border:none; cursor:pointer; }
    .btn-secondary{ background:#eee; color:#333; }
    .btn-secondary:hover{ opacity:.9; }
    .btn-danger{ background:#b71c1c; color:#fff; }
    .btn-danger:hover{ background:#8e0000; }
    .btn-primary{ background:#2e7d32; color:#fff; }
    .btn-primary:hover{ background:#1b5e20; }

    .search-wrap{ margin: 10px 0 18px; }
    .search-wrap form{ display:flex; gap:10px; flex-wrap:wrap; }
    .search-wrap input{
      flex:1; min-width: 240px;
      padding: 0.85rem;
      border: 1px solid #ccc;
      border-radius: 10px;
      outline:none;
      transition:.25s;
      font-size: 1rem;
    }
    .search-wrap input:focus{ border-color:#b71c1c; box-shadow:0 0 6px rgba(183,28,28,0.28); }
    .search-wrap button{
      padding: 0.85rem 1.1rem;
      border-radius: 10px;
      border: none;
      font-weight: 800;
      cursor: pointer;
      background: #b71c1c;
      color: #fff;
      transition:.25s;
      font-family:"Lato",sans-serif;
    }
    .search-wrap button:hover{ background:#8e0000; }

    table{
      width:100%;
      border-collapse: collapse;
      overflow:hidden;
      border-radius: 16px;
      box-shadow: 0 12px 35px rgba(0,0,0,0.12);
      background: rgba(255,255,255,0.95);
    }
    th, td{
      padding: 14px 12px;
      text-align:left;
      border-bottom: 1px solid rgba(0,0,0,0.08);
      font-family:"Lato",sans-serif;
    }
    th{
      background: rgba(183,28,28,0.08);
      color:#7b0f0f;
      font-weight: 900;
    }
    tr:hover td{ background: rgba(0,0,0,0.03); }

    .pill{
      display:inline-block;
      padding:6px 10px;
      border-radius:999px;
      font-weight:900;
      font-size:.9rem;
    }
    .pill-ok{ background: rgba(46,125,50,0.12); color:#1b5e20; }
    .pill-wait{ background: rgba(0,0,0,0.08); color:#444; }

    .empty{
      text-align:center;
      padding: 18px;
      border-radius: 14px;
      background: rgba(0,0,0,0.04);
      font-weight:800;
      font-family:"Lato",sans-serif;
    }
  </style>
</head>
<body>
<div class="form-bg">
  <div class="form-wrapper home-container">

    <div class="topbar">
      <div>
        <img src="../images/logo1.png" class="form-logo" alt="Logo">
        <h1 class="title"> Υποβολές</h1>
        <div class="msg success">Συνδεδεμένος ως <?php echo h($username); ?></div>
      </div>

      <div class="actions">
        <a class="btn-like btn-secondary" href="teacher_home.php">Πίσω</a>
        <form action="../php/logout.php" method="POST" style="margin:0;">
          <button type="submit" class="btn-like btn-danger">Αποσύνδεση</button>
        </form>
      </div>
    </div>

    <div class="search-wrap">
      <form method="GET" action="">
        <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Αναζήτηση (φοιτητής/μάθημα/εργασία/κατάσταση)..." />
        <button type="submit">Αναζήτηση</button>
      </form>
    </div>

    <?php if (count($filtered) === 0): ?>
      <div class="empty">Δεν βρέθηκαν υποβολές.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Φοιτητής</th>
            <th>Μάθημα</th>
            <th>Κωδικός</th>
            <th>Εργασία</th>
            <th>Ημ/νία</th>
            <th>Βαθμός</th>
            <th>Ενέργεια</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($filtered as $s): ?>
            <?php
              $isOk = ($s["grade_status"] ?? '') === 'Βαθμολογήθηκε';
              $pillClass = $isOk ? 'pill-ok' : 'pill-wait';
            ?>
            <tr>
              <td><?php echo h($s["student_username"]); ?></td>
              <td><?php echo h($s["course"]); ?></td>
              <td><?php echo h($s["course_code"]); ?></td>
              <td><?php echo h($s["assignment_title"]); ?></td>
              <td><?php echo h(formatDt($s["submitted_at"])); ?></td>
              <td><span class="pill <?php echo h($pillClass); ?>"><?php echo h($s["grade_value"]); ?></span></td>
              <td><a class="btn-like btn-primary" href="teacher_grade_submit.php?submission_id=<?php echo (int)$s['submission_id']; ?>">Βαθμολόγηση</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
