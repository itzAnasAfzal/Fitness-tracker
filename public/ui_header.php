<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$BASE_URL = '/fitness-tracker/public';
?>


<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FitTrack</title>
<link rel="stylesheet" href="<?= $BASE_URL ?>/assets/css/style2.css">
</head>
<body>
  <div class="container">
  <div class="nav">
    <div class="brand">Fit<span>Track</span></div>

    <div>

  <?php if (!isset($_SESSION["user"])): ?>
    <a href="<?= $BASE_URL ?>/index.php">Home</a>
  <?php endif; ?>

  <a href="<?= $BASE_URL ?>/tips.php">Tips</a>
  <a href="<?= $BASE_URL ?>/routines.php">Routines</a>
  <a href="<?= $BASE_URL ?>/nutrition.php">Nutrition</a>

  <?php if (isset($_SESSION["user"])): ?>

    <?php if ($_SESSION["user"]["role"] === "admin"): ?>
      <a class="btn secondary" href="<?= $BASE_URL ?>/admin/dashboard.php">Admin Panel</a>

    <?php elseif ($_SESSION["user"]["role"] === "trainer"): ?>
      <a class="btn secondary" href="<?= $BASE_URL ?>/trainer/dashboard.php">Trainer Panel</a>

    <?php else: ?>
      <a class="btn secondary" href="<?= $BASE_URL ?>/user/dashboard.php">Dashboard</a>
    <?php endif; ?>

    <a class="btn" href="<?= $BASE_URL ?>/logout.php">Logout</a>

  <?php else: ?>
    <a class="btn secondary" href="<?= $BASE_URL ?>/login.php">Login</a>
    <a class="btn" href="<?= $BASE_URL ?>/register.php">Register</a>
  <?php endif; ?>

    </div>
  </div>

  <div class="content">
  </div>
