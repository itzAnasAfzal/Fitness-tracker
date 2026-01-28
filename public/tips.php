<?php
session_start();
require_once __DIR__ . "/../app/config/db.php";
require_once __DIR__ . "/../app/config/auth.php";

$is_admin_or_trainer = false;
if (is_logged_in()) {
    $role = $_SESSION["user"]["role"];
    if ($role === 'admin' || $role === 'trainer') {
        $is_admin_or_trainer = true;
    }
}

$error = "";
$success = "";

if (isset($_GET['success'])) {
    if ($_GET['success'] == 'added') $success = "Tip added successfully.";
    if ($_GET['success'] == 'updated') $success = "Tip updated successfully.";
    if ($_GET['success'] == 'deleted') $success = "Tip deleted successfully.";
}
// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST" && $is_admin_or_trainer) {
    $action = $_POST["action"] ?? "";
    
    if ($action === "add") {
        $title = trim($_POST["title"] ?? "");
        $content = trim($_POST["content"] ?? "");
        
        if ($title && $content) {
            $stmt = $pdo->prepare("INSERT INTO tips (title, content, created_by) VALUES (?, ?, ?)");
            $stmt->execute([$title, $content, $_SESSION["user"]["id"]]);

            header("Location: tips.php?success=added");
            exit();
        } else {
            $error = "Title and content are required.";
        }

    } elseif ($action === "update") {
        $id = $_POST["id"] ?? "";
        $title = trim($_POST["title"] ?? "");
        $content = trim($_POST["content"] ?? "");
        
        if ($id && $title && $content) {
            $stmt = $pdo->prepare("UPDATE tips SET title = ?, content = ? WHERE id = ?");
            $stmt->execute([$title, $content, $id]);

            header("Location: tips.php?success=updated");
            exit();
        } else {
            $error = "All fields are required.";
        }

    } elseif ($action === "delete") {
        $id = $_POST["id"] ?? "";
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM tips WHERE id = ?");
            $stmt->execute([$id]);

            header("Location: tips.php?success=deleted");
            exit();
        }
    }
}

// Fetch Tips
$stmt = $pdo->query("SELECT t.*, u.name as author FROM tips t LEFT JOIN users u ON t.created_by = u.id ORDER BY created_at DESC");
$tips = $stmt->fetchAll();

// Edit Mode
$edit_tip = null;
if (isset($_GET["edit"]) && $is_admin_or_trainer) {
    $stmt = $pdo->prepare("SELECT * FROM tips WHERE id = ?");
    $stmt->execute([$_GET["edit"]]);
    $edit_tip = $stmt->fetch();
}

include "ui_header.php";
?>

<div class="card">
    <div class="flex-between-center">
        <h1 class="h1">Fitness Tips</h1>
        <?php if ($is_admin_or_trainer && !$edit_tip): ?>
            <button onclick="document.getElementById('add-form').classList.toggle('active')" class="btn">Add New Tip</button>
        <?php endif; ?>
    </div>
    <p class="p">Discover daily health and fitness advice.</p>

    <?php if ($error): ?>
        <div class="msg-error"><b><?php echo htmlspecialchars($error); ?></b></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="msg-success"><b><?php echo htmlspecialchars($success); ?></b></div>
    <?php endif; ?>

    <!-- Add Form -->
    <div id="add-form" class="dashed-card">
        <h3>Add New Tip</h3>
        <form method="post" action="tips.php">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label><b>Title</b></label><br>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label><b>Content</b></label><br>
                <textarea name="content" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn">Save Tip</button>
            <button type="button" class="btn secondary" onclick="document.getElementById('add-form').classList.remove('active')">Cancel</button>
        </form>
    </div>

    <!-- Edit Form -->
    <?php if ($edit_tip): ?>
        <div class="dashed-card-edit">
            <h3>Edit Tip</h3>
            <form method="post" action="tips.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo $edit_tip['id']; ?>">
                <div class="form-group">
                    <label><b>Title</b></label><br>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($edit_tip['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label><b>Content</b></label><br>
                    <textarea name="content" rows="4" required><?php echo htmlspecialchars($edit_tip['content']); ?></textarea>
                </div>
                <button type="submit" class="btn">Update Tip</button>
                <a href="tips.php" class="btn secondary">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <!-- List -->
    <div class="mt-30">
        <?php foreach ($tips as $tip): ?>
            <div class="card card-item">
                <div class="flex-between">
                    <h2 style="margin-top: 0;"><?php echo htmlspecialchars($tip['title']); ?></h2>
                    <?php if ($is_admin_or_trainer): ?>
                        <div>
                            <a href="tips.php?edit=<?php echo $tip['id']; ?>" class="btn secondary btn-sm">Edit</a>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $tip['id']; ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="pre-line"><?php echo htmlspecialchars($tip['content']); ?></p>
                <small style="color: #666;">Posted by <?php echo htmlspecialchars($tip['author'] ?? 'Unknown'); ?> on <?php echo date('M d, Y', strtotime($tip['created_at'])); ?></small>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($tips)): ?>
            <p>No tips available yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include "ui_footer.php"; ?>
