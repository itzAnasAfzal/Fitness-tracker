<?php
session_start();

if (isset($_SESSION["user"])) {

    if ($_SESSION["user"]["role"] === "admin") {
        header("Location: admin/dashboard.php");
    }
    elseif ($_SESSION["user"]["role"] === "trainer") {
        header("Location: trainer/dashboard.php");
    }
    else {
        header("Location: user/dashboard.php");
    }

    exit;
}
?>



<<<<<<< HEAD
<?php include "ui_header.php"; ?>
=======
<!-- <!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Personal Fitness Tracker</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <div class="nav">
      <div class="brand">Fit<span>Track</span></div>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="index.php">Home</a>
        <a href="tips.php">Tips</a>
        <a href="routines.php">Routines</a>
        <a class="btn secondary" href="login.php">Login</a>
        <a class="btn" href="register.php">Register</a>
      </div>
    </div> -->
  <?php include "ui_header.php"; ?>
>>>>>>> 4ba565007834e00652d6c32b8716554a6c2e691c

    <div class="hero">
      <div class="card">
        <div class="badge">Neo-Brutal Fitness</div>
        <h1 class="h1">Track workouts, meals & water — in one place.</h1>
        <p class="p">
          Browse public tips and routines. Create an account to log your daily activity,
          see progress charts, and get suggestions from trainers.
        </p>
        <div >
          <a class="btn" href="register.php">Get Started</a>
          <a class="btn secondary" href="routines.php">Browse Routines</a>
        </div>
        <p class="small">
          Note: This is the public area. After login, your dashboard will appear.
        </p>
      </div>

      <div class="card">
        <h2>What you can do</h2>
        <div class="grid3" >
          <div class="card">
            <b>Log Daily</b><div class="small">Workouts • Meals • Water</div>
          </div>
          <div class="card">
            <b>See Progress</b><div class="small">Stats + Charts</div>
          </div>
          <div class="card">
            <b>Trainer Feedback</b><div class="small">Suggestions & Replies</div>
          </div>
        </div>
      </div>
    </div>

<<<<<<< HEAD
=======
<!-- </div> 
 </body>
</html> -->
>>>>>>> 4ba565007834e00652d6c32b8716554a6c2e691c
<?php include "ui_footer.php"; ?>