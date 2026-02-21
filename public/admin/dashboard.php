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

<<<<<<< HEAD
// Fetch Recent Logs (no SQL LIMIT — JS handles pagination)
=======
// Fetch Recent Logs
>>>>>>> 4ba565007834e00652d6c32b8716554a6c2e691c
$recent_logs = [];
try {
    $stmt = $pdo->query("
        SELECT al.*, u.name as user_name 
        FROM activity_logs al 
        JOIN users u ON al.user_id = u.id 
<<<<<<< HEAD
        ORDER BY al.created_at DESC
=======
        ORDER BY al.created_at DESC 
        LIMIT 10
>>>>>>> 4ba565007834e00652d6c32b8716554a6c2e691c
    ");
    $recent_logs = $stmt->fetchAll();
} catch (PDOException $e) {
    // ignore
}

<<<<<<< HEAD
// Fetch Trainer Replies (no SQL LIMIT — JS handles pagination)
$trainer_replies = [];
try {
    $stmt = $pdo->query("
        SELECT tr.*, u.name as trainer_name, u.role as replier_role, rf.comment as user_comment, r.title as routine_title
=======
// Fetch Trainer Replies
$trainer_replies = [];
try {
    $stmt = $pdo->query("
        SELECT tr.*, u.name as trainer_name, rf.comment as user_comment, r.title as routine_title
>>>>>>> 4ba565007834e00652d6c32b8716554a6c2e691c
        FROM trainer_replies tr 
        JOIN users u ON tr.trainer_id = u.id
        JOIN routine_feedback rf ON tr.feedback_id = rf.id
        JOIN routines r ON rf.routine_id = r.id
<<<<<<< HEAD
        ORDER BY tr.created_at DESC
=======
        ORDER BY tr.created_at DESC 
        LIMIT 10
>>>>>>> 4ba565007834e00652d6c32b8716554a6c2e691c
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
<<<<<<< HEAD
                <?php foreach ($recent_logs as $lIndex => $log): ?>
                    <div class="card admin-log-item <?php echo $lIndex >= 10 ? 'hidden-log-item' : ''; ?>" style="padding: 15px; margin-bottom: 10px; <?php echo $lIndex >= 10 ? 'display:none;' : ''; ?>">
=======
                <?php foreach ($recent_logs as $log): ?>
                    <div class="card" style="padding: 15px; margin-bottom: 10px;">
>>>>>>> 4ba565007834e00652d6c32b8716554a6c2e691c
                        <div class="flex-between-center">
                            <strong><?php echo htmlspecialchars($log['user_name']); ?></strong>
                            <span class="badge"><?php echo htmlspecialchars(ucfirst($log['type'])); ?></span>
                        </div>
                        <p class="small" style="margin: 5px 0;"><?php echo htmlspecialchars($log['content']); ?> (<?php echo $log['value']; ?>)</p>
                        <small style="color: #999;"><?php echo $log['created_at']; ?></small>
                    </div>
                <?php endforeach; ?>
<<<<<<< HEAD
                <?php if (count($recent_logs) > 10): ?>
                    <div style="text-align: center; margin-top: 10px;">
                        <button id="show-more-logs" class="btn secondary" onclick="showMoreLogs()">Show More Activities</button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Replies -->
=======
            <?php endif; ?>
        </div>

        <!-- Recent Trainer Replies -->
>>>>>>> 4ba565007834e00652d6c32b8716554a6c2e691c
        <div>
            <h2 class="h1">Trainer Replies</h2>
            <?php if (empty($trainer_replies)): ?>
                <div class="card"><p>No replies found.</p></div>
            <?php else: ?>
<<<<<<< HEAD
                <?php foreach ($trainer_replies as $rIdx => $reply): ?>
                    <?php
                        $replyTag = match($reply['replier_role'] ?? 'trainer') {
                            'admin'   => 'Admin',
                            'trainer' => 'Trainer',
                            default   => 'User',
                        };
                    ?>
                    <div class="card admin-reply-item <?php echo $rIdx >= 10 ? 'hidden-reply-item' : ''; ?>" style="padding: 15px; margin-bottom: 10px; border-left: 4px solid var(--accent); <?php echo $rIdx >= 10 ? 'display:none;' : ''; ?>">
                        <div class="flex-between-center" style="margin-bottom: 5px;">
                            <strong><?php echo htmlspecialchars($reply['trainer_name']); ?> <span class="badge" style="font-size:11px;"><?php echo $replyTag; ?></span></strong>
                            <small style="color:#999;"><?php echo date('M d, Y', strtotime($reply['created_at'])); ?></small>
                        </div>
=======
                <?php foreach ($trainer_replies as $reply): ?>
                    <div class="card" style="padding: 15px; margin-bottom: 10px; border-left: 4px solid var(--accent);">
                        <strong style="display: block; margin-bottom: 5px;"><?php echo htmlspecialchars($reply['trainer_name']); ?></strong>
>>>>>>> 4ba565007834e00652d6c32b8716554a6c2e691c
                        <p class="small" style="margin-bottom: 5px;">On: <?php echo htmlspecialchars($reply['routine_title']); ?></p>
                        <p style="font-style: italic; background: #f9f9f9; padding: 5px;">"<?php echo htmlspecialchars($reply['reply']); ?>"</p>
                        <form method="post" onsubmit="return confirm('Delete this reply?');" style="margin-top: 10px; text-align: right;">
                            <input type="hidden" name="action" value="delete_reply">
                            <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                            <button class="btn-delete" style="font-size: 12px;">Delete Reply</button>
                        </form>
                    </div>
                <?php endforeach; ?>
<<<<<<< HEAD
                <?php if (count($trainer_replies) > 10): ?>
                    <div style="text-align: center; margin-top: 10px;">
                        <button id="show-more-replies" class="btn secondary" onclick="showMoreReplies()">Show More Replies</button>
                    </div>
                <?php endif; ?>
=======
>>>>>>> 4ba565007834e00652d6c32b8716554a6c2e691c
            <?php endif; ?>
        </div>
    </div>
</div>

<<<<<<< HEAD
<script>
function showMoreLogs() {
    const hidden = document.querySelectorAll('.hidden-log-item');
    let count = 0;
    hidden.forEach(el => {
        if (count < 10) { el.style.display = 'block'; el.classList.remove('hidden-log-item'); count++; }
    });
    if (document.querySelectorAll('.hidden-log-item').length === 0) {
        document.getElementById('show-more-logs').style.display = 'none';
    }
}

function showMoreReplies() {
    const hidden = document.querySelectorAll('.hidden-reply-item');
    let count = 0;
    hidden.forEach(el => {
        if (count < 10) { el.style.display = 'block'; el.classList.remove('hidden-reply-item'); count++; }
    });
    if (document.querySelectorAll('.hidden-reply-item').length === 0) {
        document.getElementById('show-more-replies').style.display = 'none';
    }
}
</script>

=======
>>>>>>> 4ba565007834e00652d6c32b8716554a6c2e691c
<?php include "../ui_footer.php"; ?>
