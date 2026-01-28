<?php
session_start();
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit;
}
require_once __DIR__ . "/../app/config/db.php";
require_once __DIR__ . "/../app/config/auth.php";



$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user["password_hash"])) {
            $error = "Invalid email or password.";
        } else {
            // Store only safe info in session (not password hash)
            $_SESSION["user"] = [
                "id" => $user["id"],
                "name" => $user["name"],
                "email" => $user["email"],
                "role" => $user["role"]
            ];

            if ($user["role"] === "admin") {
                header("Location: admin/dashboard.php");
            } elseif ($user["role"] === "trainer") {
                header("Location: trainer/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit;

        }
    }
}
?>

<?php include "ui_header.php"; ?>

<div class="card" >
  <h1 class="h1" >Login</h1>
  <p class="p">Enter your email and password to access your dashboard.</p>

  <?php if ($error): ?>
    <div class="card" >
      <b>❌ <?php echo htmlspecialchars($error); ?></b>
    </div>
  <?php endif; ?>

  <form method="post" >
    <div>
      <label><b>Email</b></label><br>
      <input type="email" name="email" required value="<?php echo htmlspecialchars($email); ?>" placeholder="e.g. ali@gmail.com"
        />
    </div>

    <div>
  <label><b>Password</b></label><br>

  <div >
    <input id="login_password" type="password" name="password" required placeholder="Your password"
     />

    <button
      id="login_toggle_btn"
      type="button"
      class="btn secondary"
     
      onclick="togglePassword('login_password','login_toggle_btn')">
      Show
    </button>
  </div>
</div>


    <button class="btn" type="submit">Login</button>

    <div class="small">
      Don’t have an account? <a href="register.php"><b>Create one</b></a>
    </div>
  </form>
</div>

<?php include "ui_footer.php"; ?>
