<?php
require_once __DIR__ . "/../app/config/auth.php";
require_login();

$user = $_SESSION["user"];
?>

<?php include "ui_header.php"; ?>

<div class="card" >
  <div class="badge">Dashboard</div>
  <h1 class="h1">
    Welcome, <?php echo htmlspecialchars($user["name"]); ?> 👋
  </h1>
  <p class="p">Role: <b><?php echo htmlspecialchars($user["role"]); ?></b></p>

  <div>
    <a class="btn secondary" href="logout.php">Logout</a>
  </div>

  <div class="card" >
    <b>Next:</b>
    <div class="small">We will add workout/meal/water logging here.</div>
  </div>
</div>

<?php include "ui_footer.php"; ?>
