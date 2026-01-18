<?php
session_start();

require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn, "utf8mb4");

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$role     = trim($_POST['role'] ?? '');
$secret   = trim($_POST['secret'] ?? '');

if ($username === '' || $email === '' || $password === '' || ($role !== 'student' && $role !== 'teacher')) {
    header("Location: ../pages/register.php?msg=Λάθος+στοιχεία&type=error");
    exit();
}

$teacherSecret = "PROF2025";
$studentSecret = "STUD2025";

if ($role === 'teacher' && $secret !== $teacherSecret) {
    header("Location: ../pages/register.php?msg=Λάθος+ειδικός+κωδικός+καθηγητή&type=error");
    exit();
}
if ($role === 'student' && $secret !== $studentSecret) {
    header("Location: ../pages/register.php?msg=Λάθος+ειδικός+κωδικός+φοιτητή&type=error");
    exit();
}

try {
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $exists = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($exists) {
        header("Location: ../pages/register.php?msg=Υπάρχει+ήδη+χρήστης+με+αυτά+τα+στοιχεία&type=error");
        exit();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hash, $role);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: ../pages/login.php?msg=Η+εγγραφή+ολοκληρώθηκε&type=success");
    exit();
} catch (mysqli_sql_exception $e) {
    header("Location: ../pages/register.php?msg=DB+Error&type=error");
    exit();
}
