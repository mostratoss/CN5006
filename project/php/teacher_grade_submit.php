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
$submissionId = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;

if ($submissionId <= 0) {
    header("Location: teacher_submissions.php?msg=Μη+έγκυρη+υποβολή&type=error");
    exit();
}

$msg = '';
$type = '';

$sql = "
SELECT s.id, u.username AS student_username, a.title AS assignment_title, c.title AS course, c.code AS course_code,
       s.submitted_at, s.file_name,
       g.id AS grade_id, g.grade AS grade_value, g.feedback AS feedback_value
FROM submissions s
INNER JOIN users u ON u.id = s.student_id
INNER JOIN assignments a ON a.id = s.assignment_id
INNER JOIN courses c ON c.id = a.course_id
LEFT JOIN grades g ON g.submission_id = s.id
WHERE s.id = ? AND c.teacher_id = ?
LIMIT 1
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $submissionId, $teacherId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$info = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$info) {
    echo "Forbidden Action";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = trim($_POST['grade'] ?? '');
    $feedback = trim($_POST['feedback'] ?? '');

    if ($grade === '' || !is_numeric($grade)) {
        $msg = 'Δώσε έγκυρο βαθμό.';
        $type = 'error';
    } else {
        $g = (float)$grade;

        if (!empty($info['grade_id'])) {
            $sql = "UPDATE grades SET grade = ?, feedback = ?, teacher_id = ?, graded_at = NOW() WHERE submission_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "dsii", $g, $feedback, $teacherId, $submissionId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            $sql = "INSERT INTO grades (submission_id, teacher_id, grade, feedback, graded_at) VALUES (?,?,?,?,NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iids", $submissionId, $teacherId, $g, $feedback);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header("Location: teacher_submissions.php?msg=Ο+βαθμός+αποθηκεύτηκε&type=success");
        exit();
    }
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function formatDt($s){
    $ts = strtotime($s);
    if ($ts === false) return $s;
    return date("d/m/Y H:i", $ts);
}

$currentGrade = $info['grade_value'] ?? '';
$currentFeedback = $info['feedback_value'] ?? '';

$hasFile = !empty($info['file_path']) || !empty($info['file_name']);
$fileHref = '';
if (!empty($info['file_path'])) {
    $fileHref = "../" . ltrim($info['file_path'], '/');
} elseif (!empty($info['file_name'])) {
    $fileHref = "../uploads/submissions/" . rawurlencode($info['file_name']);
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Βαθμολόγηση</title>

  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/login-register.css">

  <style>
    .home-container{ width:90%; max-width:900px; margin:0 auto; }
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

    .panel{
      background: rgba(255,255,255,0.95);
      border-radius: 18px;
      box-shadow: 0 12px 35px rgba(0,0,0,0.18);
      padding: 18px;
      margin-top: 14px;
    }
    .meta{ margin:0; opacity:.92; font-family:"Lato",sans-serif; }
    .field{ margin-top:12px; }
    .field label{ display:block; font-weight:900; margin-bottom:6px; font-family:"Lato",sans-serif; color:#333; }
    .field input, .field textarea{
      width:100%;
      padding: 0.85rem;
      border: 1px solid #ccc;
      border-radius: 10px;
      outline:none;
      transition:.25s;
      font-size: 1rem;
      background:#fff;
    }
    .field input:focus, .field textarea:focus{
      border-color:#b71c1c;
      box-shadow:0 0 6px rgba(183,28,28,0.28);
    }
    .save-btn{
      width:100%;
      margin-top:14px;
      padding: 0.95rem;
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
    .save-btn:hover{ background:#1b5e20; }
  </style>
</head>
<body>
<div class="form-bg">
  <div class="form-wrapper home-container">

    <div class="topbar">
      <div>
        <img src="../images/logo1.png" class="form-logo" alt="Logo">
        <h1 class="title"> Βαθμολόγηση</h1>
        <div class="msg success">Συνδεδεμένος ως <?php echo h($username); ?></div>
      </div>

      <div class="actions">
        <a class="btn-like btn-secondary" href="teacher_submissions.php"> Πίσω</a>
        <form action="../php/logout.php" method="POST" style="margin:0;">
          <button type="submit" class="btn-like btn-danger">Αποσύνδεση</button>
        </form>
      </div>
    </div>

    <?php if ($msg !== ''): ?>
      <div class="msg <?php echo h($type); ?>"><?php echo h($msg); ?></div>
    <?php endif; ?>

    <div class="panel">
      <p class="meta"><b>Φοιτητής:</b> <?php echo h($info['student_username']); ?></p>
      <p class="meta"><b>Μάθημα:</b> <?php echo h($info['course']); ?> (<?php echo h($info['course_code']); ?>)</p>
      <p class="meta"><b>Εργασία:</b> <?php echo h($info['assignment_title']); ?></p>
      <p class="meta"><b>Υποβλήθηκε:</b> <?php echo h(formatDt($info['submitted_at'] ?? '')); ?></p>

      <div style="margin-top:12px;">
        <?php if ($hasFile && $fileHref !== ''): ?>
          <a class="btn-like btn-primary" href="<?php echo h($fileHref); ?>" target="_blank">📎 Άνοιγμα αρχείου</a>
        <?php else: ?>
          <span class="btn-like btn-secondary" style="cursor:default;">— Χωρίς αρχείο</span>
        <?php endif; ?>
      </div>

      <form method="POST" action="" style="margin-top:14px;">
        <div class="field">
          <label>Βαθμός</label>
          <input type="number" step="0.5" name="grade" value="<?php echo h($currentGrade); ?>" required>
        </div>

        <div class="field">
          <label>Feedback (προαιρετικό)</label>
          <textarea name="feedback" rows="4"><?php echo h($currentFeedback); ?></textarea>
        </div>

        <button class="save-btn" type="submit">Αποθήκευση</button>
      </form>
    </div>

  </div>
</div>
</body>
</html>
