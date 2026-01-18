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

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Φοιτητής';
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Student Home</title>

  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/login-register.css">

  <style>

    .home-container{
      width: 90%;
      max-width: 1100px;
      margin: 0 auto;
    }

    .home-top{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 16px;
      margin-bottom: 18px;
    }

    .home-top h2{
      margin: 0;
      font-size: 1.6rem;
      color: #b71c1c;
      font-family: "Lato", sans-serif;
    }

    .home-actions{
      display:flex;
      gap: 10px;
      align-items:center;
      flex-wrap: wrap;
    }

    .btn-like{
      display:inline-block;
      padding: 10px 14px;
      border-radius: 10px;
      text-decoration:none;
      font-weight: 700;
      font-family: "Lato", sans-serif;
      border: none;
      cursor: pointer;
    }

    .btn-secondary{
      background: #eee;
      color: #333;
    }

    .btn-secondary:hover{ opacity: 0.9; }

    .btn-danger{
      background: #b71c1c;
      color: #fff;
    }
    .btn-danger:hover{ background:#8e0000; }

    .cards{
      display:grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
      margin-top: 16px;
    }

    .card{
      background: rgba(255,255,255,0.95);
      border-radius: 18px;
      box-shadow: 0 12px 35px rgba(0,0,0,0.18);
      padding: 18px;
    }

    .card h3{
      margin: 0 0 8px 0;
      color: #b71c1c;
      font-family: "Lato", sans-serif;
      font-size: 1.25rem;
    }

    .card p{
      margin: 0 0 14px 0;
      opacity: 0.9;
    }

    .card a{
      display:inline-block;
      padding: 10px 12px;
      border-radius: 10px;
      background: #2e7d32;
      color: #fff;
      text-decoration:none;
      font-weight: 700;
      font-family: "Lato", sans-serif;
      transition: 0.25s;
    }
    .card a:hover{ background:#1b5e20; }

    @media (max-width: 768px){
      .cards{ grid-template-columns: 1fr; }
      .home-top{ flex-direction: column; align-items:flex-start; }
    }
  </style>
</head>

<body>

<div class="form-bg">
  <div class="form-wrapper home-container">

    <div class="home-top">
      <div>
        <img src="../images/logo1.png" class="form-logo" alt="Logo">
        <h2>Καλώς ήρθες, <?php echo htmlspecialchars($username); ?> </h2>
        <div class="msg success">Student Home</div>
      </div>

      <div class="home-actions">
        <a class="btn-like btn-secondary" href="student_dashboard.php">Επιστροφή</a>

        <form action="../php/logout.php" method="POST" style="margin:0;">
          <button type="submit" class="btn-like btn-danger">Αποσύνδεση</button>
        </form>
      </div>
    </div>

    <div class="cards">
      <div class="card">
        <h3> Τα Μαθήματά μου</h3>
        <p>Δες τα μαθήματα στα οποία είσαι εγγεγραμμένος.</p>
        <a href="student_courses.php">Άνοιγμα</a>
      </div>

      <div class="card">
        <h3> Εργασίες</h3>
        <p>Δες τις διαθέσιμες εργασίες ανά μάθημα.</p>
        <a href="student_assignments.php">Άνοιγμα</a>
      </div>

      <div class="card">
        <h3> Υποβολές</h3>
        <p>Κάνε υποβολή εργασίας και δες την κατάσταση υποβολών.</p>
        <a href="student_submissions.php">Άνοιγμα</a>
      </div>

      <div class="card">
        <h3>Βαθμολογίες</h3>
        <p>Δες τους βαθμούς σου ανά μάθημα και εργασία.</p>
        <a href="student_grades.php">Άνοιγμα</a>
      </div>
    </div>

  </div>
</div>

</body>
</html>
