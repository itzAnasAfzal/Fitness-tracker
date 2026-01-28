<?php
require_once __DIR__ . "/../../app/config/db.php";
require_once __DIR__ . "/../../app/config/auth.php";

require_login();

$viewer = $_SESSION["user"];
$viewerRole = $viewer["role"];

$idFromUrl = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

/*
|--------------------------------------------------------------------------
| Decide which user is being edited
|--------------------------------------------------------------------------
*/
if ($idFromUrl > 0) {
    // Someone is trying to edit another user
    if ($viewerRole !== "admin") {
        die("❌ Unauthorized access.");
    }
    $targetUserId = $idFromUrl;
} else {
    // Edit own profile
    $targetUserId = $viewer["id"];
}

$error = "";
$success = "";

/*
|--------------------------------------------------------------------------
| Fetch user
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id=?");
$stmt->execute([$targetUserId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

/*
|--------------------------------------------------------------------------
| Handle update
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name  = trim($_POST["name"]);
    $email = trim($_POST["email"]);

    // Default: role stays same
    $role = $user["role"];

    if ($name === "" || $email === "") {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        try {

            // Admin can change role ONLY for others
            if (
                $viewerRole === "admin" &&
                $user["id"] !== $viewer["id"] &&
                isset($_POST["role"])
            ) {
                $role = $_POST["role"];
            }

            $stmt = $pdo->prepare(
                "UPDATE users SET name=?, email=?, role=? WHERE id=?"
            );
            $stmt->execute([$name, $email, $role, $targetUserId]);

            $success = "Profile updated successfully.";

            // Refresh data
            $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id=?");
            $stmt->execute([$targetUserId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Sync session if user updated self
            if ($user["id"] === $viewer["id"]) {
                $_SESSION["user"]["name"]  = $user["name"];
                $_SESSION["user"]["email"] = $user["email"];
            }

        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), "Duplicate")) {
                $error = "This email is already in use.";
            } else {
                $error = "Something went wrong.";
            }
        }
    }
}
?>

<?php include "../ui_header.php"; ?>

<div class="card" >
  <span class="badge">
    <?= $viewerRole === "admin" ? "Admin Panel" : "My Account" ?>
  </span>

  <h1 class="h1" >
    <?= $user["id"] === $viewer["id"] ? "Edit My Profile" : "Update User" ?>
  </h1>

  <p class="p">Edit account details securely.</p>

  <?php if ($error): ?>
    <div class="card" >
      <b>❌ <?= htmlspecialchars($error) ?></b>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="card" >
      <b>✅ <?= htmlspecialchars($success) ?></b>
    </div>
  <?php endif; ?>

  <form method="post" >

    <div>
      <label><b>Name</b></label>
      <input name="name" value="<?= htmlspecialchars($user["name"]) ?>" required>
    </div>

    <div>
      <label><b>Email</b></label>
      <input type="email" name="email" value="<?= htmlspecialchars($user["email"]) ?>" required>
    </div>

    <!-- Role handling -->
    <?php if ($viewerRole === "admin"): ?>
      <div>
        <label><b>Role</b></label>

        <?php if ($user["id"] === $viewer["id"]): ?>
          <div class="small">You cannot change your own role.</div>
          <input type="hidden" name="role" value="<?= $user["role"] ?>">
          <div class="neo-select"><?= ucfirst($user["role"]) ?></div>
        <?php else: ?>
          <select name="role" class="neo-select">
            <option value="user" <?= $user["role"]==="user"?"selected":"" ?>>User</option>
            <option value="trainer" <?= $user["role"]==="trainer"?"selected":"" ?>>Trainer</option>
            <option value="admin" <?= $user["role"]==="admin"?"selected":"" ?>>Admin</option>
          </select>
        <?php endif; ?>
      </div>
    <?php endif; ?>

   <div >
  <button class="btn" type="submit">Update Changes</button>

  <?php if ($viewerRole === "admin"): ?>
    <a class="btn secondary" href="<?= $BASE_URL ?>/admin/manage_users.php">
      ← Back to Users
    </a>

  <?php elseif ($viewerRole === "trainer"): ?>
    <a class="btn secondary" href="<?= $BASE_URL ?>/trainer/dashboard.php">
      ← Back to Dashboard
    </a>

  <?php else: ?>
    <a class="btn secondary" href="<?= $BASE_URL ?>/user/dashboard.php">
      ← Back to Dashboard
    </a>
  <?php endif; ?>
</div>


  </form>
</div>

<?php include "../ui_footer.php"; ?>
