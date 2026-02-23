<?php
require_once __DIR__ . "/../../app/config/db.php";
require_once __DIR__ . "/../../app/config/auth.php";
require_role("admin");

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($name === "" || $email === "" || strlen($password) < 6) {
        $msg = "All fields required (password ≥ 6 chars).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please enter a valid email address.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password_hash, role)
                 VALUES (?, ?, ?, 'trainer')"
            );
            $stmt->execute([$name, $email, $hash]);

            $msg = "✅ Trainer created successfully.";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Duplicate") !== false) {
                $msg = "❌ This email is already registered.";
            } else {
                $msg = "❌ Something went wrong. Try again.";
            }
        }
    }
}

?>
<?php include "../ui_header.php"; ?>

  <div class="card" >
    
    <span class="badge">Admin Panel</span>
    <h1 class="h1">Create Trainer</h1>
    <p class="p">Add a new trainer with login access.</p>

    <?php if ($msg): ?>
      <div class="card">
        <strong><?= htmlspecialchars($msg) ?></strong>
      </div>
    <?php endif; ?>

    <form method="post">

      <div>
        <label class="small"><strong>Trainer Name</strong></label>
        <input
          name="name"
          required
          placeholder="Enter full name"
      
        >
      </div>

      <div>
        <label class="small"><strong>Email Address</strong></label>
        <input
          type="email"
          name="email"
          required
          placeholder="trainer@email.com"
        >
      </div>

     <div>
        <label class="small"><strong>Password</strong></label>

        <div class="inline-form">
            <input
            id="trainer_password"
            type="password"
            name="password"
            required
            placeholder="Minimum 6 characters"
          
            >

            <button
            id="trainer_toggle_btn"
            type="button"
            class="btn secondary"
         
            onclick="togglePassword('trainer_password','trainer_toggle_btn')"
            >
            Show
            </button>
        </div>


        </div>


      <button class="btn mt-30" type="submit">
         Create Trainer
      </button>

    </form>
  </div>

<?php include "../ui_footer.php"; ?>
