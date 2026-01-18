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

$msg = '';
$type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $dueAt = trim($_POST['due_at'] ?? '');

    if ($courseId <= 0 || $title === '' || $dueAt === '') {
        $msg = 'Συμπλήρωσε όλα τα απαραίτητα πεδία.';
        $type = 'error';
    } else {
        $sql = "INSERT INTO assignments (course_id, title, description, due_at, created_by) VALUES (?,?,?,?,?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssi", $courseId, $title, $desc, $dueAt, $teacherId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: teacher_assignments.php?msg=Η+εργασία+δημιουργήθηκε&type=success");
        exit();
    }
}

$sql = "SELECT id, title, code FROM courses WHERE teacher_id = ? ORDER BY title ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$courses = [];
while ($row = mysqli_fetch_assoc($result)) $courses[] = $row;
mysqli_stmt_close($stmt);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Νέα Εργασία</title>

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

    .field{ margin-top:12px; }
    .field label{ display:block; font-weight:900; margin-bottom:6px; font-family:"Lato",sans-serif; color:#333; }
    .field input, .field textarea, .field select{
      width:100%;
      padding: 0.85rem;
      border: 1px solid #ccc;
      border-radius: 10px;
      outline:none;
      transition:.25s;
      font-size: 1rem;
      background:#fff;
    }
    .field input:focus, .field textarea:focus, .field select:focus{
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
        <h1 class="title">Νέα Εργασία</h1>
        <div class="msg success">Συνδεδεμένος ως <?php echo h($username); ?></div>
      </div>

      <div class="actions">
        <a class="btn-like btn-secondary" href="teacher_assignments.php">Πίσω</a>
        <form action="../php/logout.php" method="POST" style="margin:0;">
          <button type="submit" class="btn-like btn-danger">Αποσύνδεση</button>
        </form>
      </div>
    </div>

    <?php if ($msg !== ''): ?>
      <div class="msg <?php echo h($type); ?>"><?php echo h($msg); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="field">
        <label>Μάθημα</label>
        <select name="course_id" required>
          <option value="">Επίλεξε μάθημα</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['title']); ?> (<?php echo h($c['code']); ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>Τίτλος</label>
        <input type="text" name="title" required />
      </div>

      <div class="field">
        <label>Περιγραφή</label>
        <textarea name="description" rows="4"></textarea>
      </div>

      <div class="field">
        <label>Προθεσμία</label>
        <input type="datetime-local" name="due_at" required />
      </div>

      <button type="submit" class="save-btn">Αποθήκευση</button>
    </form>

  </div>
</div>
</body>
</html>
