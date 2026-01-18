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

$studentId = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Φοιτητής';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$msg = '';
$type = '';

$assignmentId = (int)($_GET['assignment_id'] ?? $_POST['assignment_id'] ?? 0);
if ($assignmentId <= 0) die("Missing assignment_id");

$stmtA = mysqli_prepare($conn, "
    SELECT a.id, a.title, a.due_at,
           COALESCE(c.title, '—') AS course_title,
           COALESCE(c.code, '—') AS course_code
    FROM assignments a
    LEFT JOIN courses c ON c.id = a.course_id
    WHERE a.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmtA, "i", $assignmentId);
mysqli_stmt_execute($stmtA);
$resA = mysqli_stmt_get_result($stmtA);
$assignment = mysqli_fetch_assoc($resA);
mysqli_stmt_close($stmtA);

if (!$assignment) die("Assignment not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = __DIR__ . '/../uploads/submissions';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileNameToStore = null;

    if (!empty($_FILES['file']['name'])) {
        if (!isset($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $msg = 'Αποτυχία ανεβάσματος αρχείου.';
            $type = 'error';
        } else {
            $origName = basename($_FILES['file']['name']);
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);
            $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));

            $allowed = ['pdf','doc','docx','zip','rar','txt','png','jpg','jpeg'];
            if ($ext !== '' && !in_array($ext, $allowed, true)) {
                $msg = 'Μη επιτρεπτός τύπος αρχείου.';
                $type = 'error';
            } else {
                $newName = 'A'.$assignmentId.'_S'.$studentId.'_'.time().'_'.$safeName;
                $destAbs = $uploadDir . '/' . $newName;

                if (!move_uploaded_file($_FILES['file']['tmp_name'], $destAbs)) {
                    $msg = 'Δεν μπόρεσε να αποθηκευτεί το αρχείο.';
                    $type = 'error';
                } else {
                    $fileNameToStore = $newName;
                }
            }
        }
    }

    if ($type !== 'error') {
        $stmtC = mysqli_prepare($conn, "SELECT id FROM submissions WHERE assignment_id = ? AND student_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtC, "ii", $assignmentId, $studentId);
        mysqli_stmt_execute($stmtC);
        $resC = mysqli_stmt_get_result($stmtC);
        $existing = mysqli_fetch_assoc($resC);
        mysqli_stmt_close($stmtC);

        if ($existing) {
            if ($fileNameToStore === null) {
                $stmtU = mysqli_prepare($conn, "
                    UPDATE submissions
                    SET submitted_at = NOW()
                    WHERE id = ?
                ");
                $sid = (int)$existing['id'];
                mysqli_stmt_bind_param($stmtU, "i", $sid);
                mysqli_stmt_execute($stmtU);
                mysqli_stmt_close($stmtU);
            } else {
                $stmtU = mysqli_prepare($conn, "
                    UPDATE submissions
                    SET file_name = ?, submitted_at = NOW()
                    WHERE id = ?
                ");
                $sid = (int)$existing['id'];
                mysqli_stmt_bind_param($stmtU, "si", $fileNameToStore, $sid);
                mysqli_stmt_execute($stmtU);
                mysqli_stmt_close($stmtU);
            }

            header("Location: student_submissions.php?msg=" . urlencode("Η υποβολή ενημερώθηκε.") . "&type=success");
            exit();
        } else {
            if ($fileNameToStore === null) {
                $stmtI = mysqli_prepare($conn, "
                    INSERT INTO submissions (assignment_id, student_id, submitted_at)
                    VALUES (?,?,NOW())
                ");
                mysqli_stmt_bind_param($stmtI, "ii", $assignmentId, $studentId);
                mysqli_stmt_execute($stmtI);
                $newId = mysqli_insert_id($conn);
                mysqli_stmt_close($stmtI);

                header("Location: student_submissions.php?msg=" . urlencode("Η υποβολή μπήκε στη βάση (ID: ".$newId.")") . "&type=success");
                exit();
            } else {
                $stmtI = mysqli_prepare($conn, "
                    INSERT INTO submissions (assignment_id, student_id, file_name, submitted_at)
                    VALUES (?,?,?,NOW())
                ");
                mysqli_stmt_bind_param($stmtI, "iis", $assignmentId, $studentId, $fileNameToStore);
                mysqli_stmt_execute($stmtI);
                $newId = mysqli_insert_id($conn);
                mysqli_stmt_close($stmtI);

                header("Location: student_submissions.php?msg=" . urlencode("Η υποβολή μπήκε στη βάση (ID: ".$newId.")") . "&type=success");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Υποβολή</title>
  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/login-register.css">
</head>
<body>
<div class="form-bg">
  <div class="form-wrapper" style="max-width:900px;">
    <img src="../images/logo1.png" class="form-logo" alt="Logo">
    <h2> Υποβολή Εργασίας</h2>
    <div class="msg success">Συνδεδεμένος ως <?php echo h($username); ?></div>

    <?php if ($msg !== ''): ?>
      <div class="msg <?php echo h($type); ?>"><?php echo h($msg); ?></div>
    <?php endif; ?>

    <div class="msg" style="background:rgba(0,0,0,0.04); color:#333;">
      <b>Μάθημα:</b> <?php echo h($assignment['course_title']); ?> (<?php echo h($assignment['course_code']); ?>)<br>
      <b>Εργασία:</b> <?php echo h($assignment['title']); ?>
    </div>

    <form method="POST" action="" enctype="multipart/form-data">
      <input type="hidden" name="assignment_id" value="<?php echo (int)$assignmentId; ?>">

      <label>Κείμενο υποβολής (προαιρετικό)</label>
      <textarea name="text" rows="5" style="width:100%; padding:12px; border-radius:10px; border:1px solid #ccc;"></textarea>

      <label>Αρχείο (προαιρετικό)</label>
      <input type="file" name="file" />

      <button type="submit" style="margin-top:12px;">Υποβολή</button>

      <div style="margin-top:12px;">
        <a class="back-home" href="student_assignments.php"> Πίσω στις εργασίες</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
