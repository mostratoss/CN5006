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

$username  = $_SESSION['username'] ?? 'Φοιτητής';

require_once __DIR__ . '/../config/db.php';

if (!isset($conn) || !$conn) {
    die("Database connection failed.");
}

$sql = "
    SELECT c.id, c.code, c.title, c.semester, u.username AS teacher_name
    FROM courses c
    LEFT JOIN users u ON u.id = c.teacher_id
    ORDER BY c.title ASC
";


$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    $dbErr = htmlspecialchars(mysqli_error($conn));
    die("DB error: $dbErr");
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$courses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $courses[] = $row;
}

mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Τα Μαθήματά μου</title>

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
    .search-wrap input{
      width:100%;
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

    .grid{ display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
    .card{
      background: rgba(255,255,255,0.95);
      border-radius: 18px;
      box-shadow: 0 12px 35px rgba(0,0,0,0.18);
      padding: 18px;
    }
    .card h3{ margin:0 0 6px; color:#b71c1c; font-family:"Lato",sans-serif; font-size:1.2rem; }
    .meta{ margin:0; opacity:.9; }
    .badge{
      display:inline-block;
      margin-top:10px;
      padding:6px 10px;
      border-radius:999px;
      background: rgba(183,28,28,0.12);
      color:#7b0f0f;
      font-weight:700;
      font-family:"Lato",sans-serif;
      font-size:.9rem;
    }

    .empty{
      text-align:center;
      padding: 18px;
      border-radius: 14px;
      background: rgba(0,0,0,0.04);
      font-weight:700;
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
        <h1 class="title"> Τα Μαθήματά μου</h1>
        <div class="msg success">Συνδεδεμένος ως <?php echo htmlspecialchars($username); ?></div>
      </div>

      <div class="actions">
        <a class="btn-like btn-secondary" href="student_home.php">Πίσω</a>

        <form action="../php/logout.php" method="POST" style="margin:0;">
          <button type="submit" class="btn-like btn-danger">Αποσύνδεση</button>
        </form>
      </div>
    </div>

    <div class="search-wrap">
      <input id="search" type="text" placeholder="Αναζήτηση μαθήματος (τίτλος/κωδικός/καθηγητής)..." />
    </div>

    <?php if (count($courses) === 0): ?>
      <div class="empty">
        Δεν βρέθηκαν μαθήματα για τον λογαριασμό σου.
      </div>
    <?php else: ?>
      <div class="grid" id="courseGrid">
        <?php foreach ($courses as $c): ?>
          <?php
            $title   = htmlspecialchars($c['title'] ?? '');
            $code    = htmlspecialchars($c['code'] ?? '');
            $sem     = htmlspecialchars($c['semester'] ?? '');
            $teacher = htmlspecialchars($c['teacher_name'] ?? '—');
            $id      = (int) ($c['id'] ?? 0);
          ?>
          <div class="card course-item"
               data-search="<?php echo strtolower($title.' '.$code.' '.$teacher.' '.$sem); ?>">
            <h3><?php echo $title; ?></h3>
            <p class="meta"><b>Κωδικός:</b> <?php echo $code !== '' ? $code : '—'; ?></p>
            <p class="meta"><b>Εξάμηνο:</b> <?php echo $sem !== '' ? $sem : '—'; ?></p>
            <p class="meta"><b>Καθηγητής:</b> <?php echo $teacher; ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
  const search = document.getElementById('search');
  const items = document.querySelectorAll('.course-item');

  function normalize(s){ return (s || '').toLowerCase().trim(); }

  search?.addEventListener('input', () => {
    const q = normalize(search.value);
    items.forEach(el => {
      const hay = el.getAttribute('data-search') || '';
      el.style.display = hay.includes(q) ? '' : 'none';
    });
  });
</script>
</body>
</html>
