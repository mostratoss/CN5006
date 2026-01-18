<?php
session_start();

require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/login.php?msg=Μη+έγκυρο+αίτημα&type=error");
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header("Location: ../pages/login.php?msg=Συμπλήρωσε+email+και+κωδικό&type=error");
    exit();
}

try {
    $query = "SELECT id, username, password, role FROM users WHERE email = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        header("Location: ../pages/login.php?msg=Το+email+είναι+λάθος&type=error");
        exit();
    }

    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!password_verify($password, $user['password'])) {
        header("Location: ../pages/login.php?msg=Ο+κωδικός+είναι+λάθος&type=error");
        exit();
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    if ($user['role'] === 'student') {
        header("Location: ../php/student_home.php?msg=Καλώς+ήρθες&type=success");
        exit();
    }

    if ($user['role'] === 'teacher') {
        header("Location: ../php/teacher_home.php?msg=Καλώς+ήρθες&type=success");
        exit();
    }

    header("Location: ../pages/login.php?msg=Μη+έγκυρος+ρόλος&type=error");
    exit();

} catch (mysqli_sql_exception $e) {
    header("Location: ../pages/login.php?msg=DB+Error&type=error");
    exit();
}
