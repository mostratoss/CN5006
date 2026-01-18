<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=Πρέπει+να+συνδεθείτε&type=error");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo "Forbidden Action";
    exit();
}

require_once __DIR__ . '/../config/db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn, "utf8mb4");

$studentId = (int) $_SESSION['user_id'];
$username  = $_SESSION['username'] ?? 'Φοιτητής';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function statusLabel($status, $submitted) {
    if ($submitted) return ["Υποβλήθηκε", "badge-ok"];
    if ($status === "closed") return ["Έληξε", "badge-closed"];
    return ["Ανοιχτή", "badge-open"];
}

function formatDue($due) {
    if (!$due) return '—';
    $ts = strtotime($due);
    if ($ts === false) return $due;
    return date("d/m/Y H:i", $ts);
}

$assignments = [];

$sql = "
SELECT
  a.id,
  a.title,
  a.due_at AS due,
  c.title AS course,
  c.code  AS course_code,
  CASE WHEN a.due_at IS NOT NULL AND a.due_at < NOW() THEN 'closed' ELSE 'open' END AS status,
  CASE WHEN s.id IS NULL THEN 0 ELSE 1 END AS submitted
FROM assignments a
INNER JOIN courses c ON c.id = a.course_id
LEFT JOIN submissions s
  ON s.assignment_id = a.id AND s.student_id = ?
ORDER BY a.due_at IS NULL, a.due_at ASC, a.id DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $studentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $row['submitted'] = (int)($row['submitted'] ?? 0);
    $assignments[] = $row;
}
mysqli_stmt_close($stmt);

/* Search */
$filtered = [];
if ($q === '') {
    $filtered = $assignments;
} else {
    $ql = mb_strtolower($q, "UTF-8");
    foreach ($assignments as $a) {
        $hay = mb_strtolower(($a["course"] ?? '') . ' ' . ($a["course_code"] ?? '') . ' ' . ($a["title"] ?? ''), "UTF-8");
        if (mb_strpos($hay, $ql) !== false) $filtered[] = $a;
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Εργασίες</title>

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

    .search-wrap{ margin: 10px 0 18px; }
    .search-wrap form{ display:flex; gap:10px; flex-wrap:wrap; }
    .search-wrap input{
      flex:1;
      min-width: 220px;
      padding: 0.85rem;
      border: 1px solid #ccc;
      border-radius: 10px;
      outline:none;
      transition:.25s;
      font-size: 1rem;
    }
    .search-wrap input:focus{
      border-color:#b71c1c;
      box-shadow:0 0 6px rgba(183,28,28,0.28);
    }
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

    .grid{ display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
    .card{
      background: rgba(255,255,255,0.95);
      border-radius: 18px;
      box-shadow: 0 12px 35px rgba(0,0,0,0.18);
      padding: 18px;
    }
    .card h3{ margin:0 0 6px; color:#b71c1c; font-family:"Lato",sans-serif; font-size:1.2rem; }
    .meta{ margin:0; opacity:.92; }
    .row{ display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-top:10px; }

    .badge{
      display:inline-block;
      padding:6px 10px;
      border-radius:999px;
      font-weight:800;
      font-family:"Lato",sans-serif;
      font-size:.9rem;
    }
    .badge-open{ background: rgba(46,125,50,0.12); color:#1b5e20; }
    .badge-closed{ background: rgba(183,28,28,0.12); color:#7b0f0f; }
    .badge-ok{ background: rgba(0,0,0,0.08); color:#333; }

    .cta{
      display:inline-block;
      padding:10px 12px;
      border-radius:10px;
      text-decoration:none;
      font-weight:800;
      font-family:"Lato",sans-serif;
      transition:.25s;
    }
    .cta-primary{ background:#2e7d32; color:#fff; }
    .cta-primary:hover{ background:#1b5e20; }
    .cta-disabled{ background: rgba(0,0,0,0.08); color:#666; pointer-events:none; }

    .empty{
      text-align:center;
      padding: 18px;
      border-radius: 14px;
      background: rgba(0,0,0,0.04);
      font-weight:800;
      font-family:"Lato",sans-serif;
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
        <h1 class="title"> Εργασίες</h1>
        <div class="msg success">Συνδεδεμένος ως <?php echo h($username); ?></div>
      </div>

      <div class="actions">
        <a class="btn-like btn-secondary" href="student_home.php"> Πίσω</a>
        <form action="../php/logout.php" method="POST" style="margin:0;">
          <button type="submit" class="btn-like btn-danger">Αποσύνδεση</button>
        </form>
      </div>
    </div>

    <div class="search-wrap">
      <form method="GET" action="">
        <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Αναζήτηση (μάθημα/κωδικός/τίτλος)..." />
        <button type="submit">Αναζήτηση</button>
      </form>
    </div>

    <?php if (count($filtered) === 0): ?>
      <div class="empty">Δεν βρέθηκαν εργασίες.</div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($filtered as $a): ?>
          <?php
            [$label, $badgeClass] = statusLabel($a["status"], (int)$a["submitted"] === 1);
            $due = formatDue($a["due"]);
            $canSubmit = ($a["status"] === "open" && (int)$a["submitted"] === 0);
          ?>
          <div class="card">
            <h3><?php echo h($a["title"]); ?></h3>
            <p class="meta"><b>Μάθημα:</b> <?php echo h($a["course"]); ?> (<?php echo h($a["course_code"]); ?>)</p>
            <p class="meta"><b>Προθεσμία:</b> <?php echo h($due); ?></p>

            <div class="row">
              <span class="badge <?php echo h($badgeClass); ?>"><?php echo h($label); ?></span>

              <?php if ($canSubmit): ?>
                <a class="cta cta-primary" href="<?php echo "student_submission_new.php?assignment_id=".(int)$a["id"]; ?>">Υποβολή</a>
              <?php else: ?>
                <a class="cta cta-disabled" href="#">Υποβολή</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
