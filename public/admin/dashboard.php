<?php
require_once __DIR__ . "/../../app/config/auth.php";
require_once __DIR__ . "/../../app/config/db.php";
require_role("admin");

$success = "";
$error = "";

// Handle Actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_reply') {
        $reply_id = $_POST['reply_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM trainer_replies WHERE id = ?");
            $stmt->execute([$reply_id]);
            $success = "Trainer reply deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting reply: " . $e->getMessage();
        }
    }
}

// Fetch Stats
$stats = [];
try {
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
    $stats['trainers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='trainer'")->fetchColumn();
    $stats['routines'] = $pdo->query("SELECT COUNT(*) FROM routines")->fetchColumn();
    $stats['logs'] = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
} catch (PDOException $e) {
    // ignore
}

// Fetch Recent Logs
$recent_logs = [];
try {
    $stmt = $pdo->query("
        SELECT al.*, u.name as user_name 
        FROM activity_logs al 
        JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    $recent_logs = $stmt->fetchAll();
} catch (PDOException $e) {
    // ignore
}

// Fetch Trainer Replies
$trainer_replies = [];
try {
    $stmt = $pdo->query("
        SELECT tr.*, u.name as trainer_name, rf.comment as user_comment, r.title as routine_title
        FROM trainer_replies tr 
        JOIN users u ON tr.trainer_id = u.id
        JOIN routine_feedback rf ON tr.feedback_id = rf.id
        JOIN routines r ON rf.routine_id = r.id
        ORDER BY tr.created_at DESC 
        LIMIT 10
    ");
    $trainer_replies = $stmt->fetchAll();
} catch (PDOException $e) {
    // ignore
}

?>

<?php include "../ui_header.php"; ?>

<div class="container">
    <div class="card">
        <div class="badge">Admin Panel</div>
        <h1 class="h1">Admin Dashboard</h1>
        <p class="p">Overview of system activity and management.</p>

        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="grid3" style="margin-top: 20px;">
            <a class="btn" href="manage_users.php">Manage Users</a>
            <a class="btn secondary" href="create_trainer.php">Create Trainer</a>
            <a class="btn" href="../tips.php">Manage Tips</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid3 mt-30">
        <div class="card" style="text-align: center;">
            <h2 style="color: var(--accent); font-size: 32px; margin: 0;"><?php echo $stats['users']; ?></h2>
            <small>Registered Users</small>
        </div>
        <div class="card" style="text-align: center;">
            <h2 style="color: var(--accent2); font-size: 32px; margin: 0;"><?php echo $stats['trainers']; ?></h2>
            <small>Trainers</small>
        </div>
        <div class="card" style="text-align: center;">
            <h2 style="color: #f59e0b; font-size: 32px; margin: 0;"><?php echo $stats['routines']; ?></h2>
            <small>Routines</small>
        </div>
        <div class="card" style="text-align: center;">
            <h2 style="color: #ec4899; font-size: 32px; margin: 0;"><?php echo $stats['logs']; ?></h2>
            <small>Total Activities Logged</small>
        </div>
    </div>

    <div class="grid3 mt-30">
        <!-- Recent User Activities -->
        <div style="grid-column: span 2;">
            <h2 class="h1">Recent User Activities</h2>
            <?php if (empty($recent_logs)): ?>
                <div class="card"><p>No activities found.</p></div>
            <?php else: ?>
                <?php foreach ($recent_logs as $log): ?>
                    <div class="card" style="padding: 15px; margin-bottom: 10px;">
                        <div class="flex-between-center">
                            <strong><?php echo htmlspecialchars($log['user_name']); ?></strong>
                            <span class="badge"><?php echo htmlspecialchars(ucfirst($log['type'])); ?></span>
                        </div>
                        <p class="small" style="margin: 5px 0;"><?php echo htmlspecialchars($log['content']); ?> (<?php echo $log['value']; ?>)</p>
                        <small style="color: #999;"><?php echo $log['created_at']; ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Recent Trainer Replies -->
        <div>
            <h2 class="h1">Trainer Replies</h2>
            <?php if (empty($trainer_replies)): ?>
                <div class="card"><p>No replies found.</p></div>
            <?php else: ?>
                <?php foreach ($trainer_replies as $reply): ?>
                    <div class="card" style="padding: 15px; margin-bottom: 10px; border-left: 4px solid var(--accent);">
                        <strong style="display: block; margin-bottom: 5px;"><?php echo htmlspecialchars($reply['trainer_name']); ?></strong>
                        <p class="small" style="margin-bottom: 5px;">On: <?php echo htmlspecialchars($reply['routine_title']); ?></p>
                        <p style="font-style: italic; background: #f9f9f9; padding: 5px;">"<?php echo htmlspecialchars($reply['reply']); ?>"</p>
                        <form method="post" onsubmit="return confirm('Delete this reply?');" style="margin-top: 10px; text-align: right;">
                            <input type="hidden" name="action" value="delete_reply">
                            <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                            <button class="btn-delete" style="font-size: 12px;">Delete Reply</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include "../ui_footer.php"; ?>
