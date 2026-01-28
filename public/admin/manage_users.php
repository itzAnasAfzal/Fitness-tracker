<?php
require_once __DIR__ . "/../../app/config/db.php";
require_once __DIR__ . "/../../app/config/auth.php";
require_role("admin");

// Fetch all users
$stmt = $pdo->query("SELECT id, name, email, role FROM users ORDER BY role, name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle role update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["user_id"], $_POST["role"])) {
    $uid = (int)$_POST["user_id"];
    $role = $_POST["role"];

    // Prevent admin locking himself out
    if ($uid !== $_SESSION["user"]["id"]) {
        $stmt = $pdo->prepare("UPDATE users SET role=? WHERE id=?");
        $stmt->execute([$role, $uid]);
    }

    header("Location: manage_users.php");
    exit;
}

// Handle delete
if (isset($_GET["delete"])) {
    $uid = (int)$_GET["delete"];

    // Prevent self-delete
    if ($uid !== $_SESSION["user"]["id"]) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$uid]);
    }

    header("Location: manage_users.php");
    exit;
}
?>

<?php include "../ui_header.php"; ?>

<div class="card" >
  <span class="badge">Admin Panel</span>
  <h1 class="h1">Manage Users</h1>
  <p class="p">View, update roles, or remove users.</p>

  <div>
    <table class="user-table">
   
     <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
     </thead>


     <?php $i = 1; ?>
     <tbody>
    <?php foreach ($users as $u): ?>
    <tr class="user-row">
    <td><strong><?= $i++ ?></strong></td>

    <td>
        <strong><?= htmlspecialchars($u["name"]) ?></strong>
        <?php if ($u["id"] === $_SESSION["user"]["id"]): ?>
        <span class="you-badge">YOU</span>
        <?php endif; ?>
    </td>

    <td><?= htmlspecialchars($u["email"]) ?></td>

    <td>
        <form method="post" onsubmit="return protectSelf(<?= $u['id'] ?>)">
        <input type="hidden" name="user_id" value="<?= $u["id"] ?>">

        <select name="role" class="neo-select">
            <option value="user" <?= $u["role"]==="user"?"selected":"" ?>>User</option>
            <option value="trainer" <?= $u["role"]==="trainer"?"selected":"" ?>>Trainer</option>
            <option value="admin" <?= $u["role"]==="admin"?"selected":"" ?>>Admin</option>
        </select>

        <button class="btn secondary btn-sm">Update</button>
        </form>
    </td>

    <td >
    <a href="<?= $BASE_URL ?>/account/edit_profile.php?id=<?= $u['id'] ?>" class="btn secondary btn-sm">
        Edit
    </a>

    <?php if ($u["id"] !== $_SESSION["user"]["id"]): ?>
        <a
        class="btn btn-sm"
        href="?delete=<?= $u["id"] ?>"
        onclick="return confirm('Delete this user?')"
        >
        Delete
        </a>
    <?php else: ?>
        <button class="btn secondary btn-sm" onclick="showSelfWarning()" type="button">
        Delete
        </button>
    <?php endif; ?>
    </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
  </div>

  <div >
    <a class="btn secondary" href="dashboard.php">← Back to Dashboard</a>
  </div>
</div>

<?php include "../ui_footer.php"; ?>
