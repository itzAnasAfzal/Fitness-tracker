<?php
session_start();
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . "/../app/config/db.php";
require_once __DIR__ . "/../app/config/auth.php";


$error = "";
$success = "";

$name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($name === "" || $email === "" || $password === "") {
        $error = "Please fill all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Hash password (never store raw password)
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$name, $email, $hash]);

            $success = "Account created! You can now login.";
        } catch (PDOException $e) {
            // Duplicate email error (most common)
            if (strpos($e->getMessage(), "Duplicate") !== false) {
                $error = "This email is already registered. Try login.";
            } else {
                $error = "Something went wrong: " . $e->getMessage();
            }
        }
    }
}
?>

<?php include "ui_header.php"; ?>

<div class="card" >
  <h1 class="h1">Create Account</h1>
  <p class="p">Register to log workouts, meals, water intake, and track progress.</p>

  <?php if ($error): ?>
    <div class="card" >
      <b>❌ <?php echo htmlspecialchars($error); ?></b>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="card" >
      <b>✅ <?php echo htmlspecialchars($success); ?></b>
      <div class="small">
        <a href="login.php"><b>Go to Login</b></a>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" >
    <div>
      <label><b>Full Name</b></label><br>
      <input name="name" required value="<?php echo htmlspecialchars($name); ?>" placeholder="e.g. Ali Khan"
        />
    </div>

    <div>
      <label><b>Email</b></label><br>
      <input type="email" name="email" required value="<?php echo htmlspecialchars($email); ?>" placeholder="e.g. ali@gmail.com"
        />
    </div>

    <div>
  <label><b>Password</b></label><br>

  <div class="inline-form">
    <input id="reg_password" type="password" name="password" required placeholder="Create a password (Minimum 6 characters)"
       />

    <button
      id="reg_toggle_btn"
      type="button"
      class="btn secondary"
      
      onclick="togglePassword('reg_password','reg_toggle_btn')">
      Show
    </button>
  </div>

</div>


    <button class="btn mt-30" type="submit">Register</button>

    <div class="small">
      Already have an account? <a href="login.php"><b>Login here</b></a>
    </div>
  </form>
</div>

<?php include "ui_footer.php"; ?>
