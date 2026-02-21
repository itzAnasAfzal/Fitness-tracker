<?php
require_once __DIR__ . "/../../app/config/auth.php";
require_once __DIR__ . "/../../app/config/db.php";
require_role("trainer");

$trainer_id = $_SESSION["user"]["id"];
$error = "";
$success = "";

// Handle Form Submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "reply") {
        $feedback_id = $_POST["feedback_id"] ?? "";
        $reply = trim($_POST["reply"] ?? "");
        if ($feedback_id && $reply) {
            $stmt = $pdo->prepare("INSERT INTO trainer_replies (feedback_id, trainer_id, reply) VALUES (?, ?, ?)");
            $stmt->execute([$feedback_id, $trainer_id, $reply]);
            $success = "Reply posted successfully.";
        }
    } elseif ($action === "suggest") {
        $user_id = $_POST["user_id"] ?? "";
        $suggestion = trim($_POST["suggestion"] ?? "");
        if ($user_id && $suggestion) {
            $stmt = $pdo->prepare("INSERT INTO trainer_suggestions (trainer_id, user_id, suggestion) VALUES (?, ?, ?)");
            $stmt->execute([$trainer_id, $user_id, $suggestion]);
            $success = "Suggestion sent to user.";
        }
    }
}

// Fetch Feedbacks on Trainer's Routines
$feedbacks = [];
try {
    $stmt = $pdo->prepare("
        SELECT rf.*, u.name as user_name, r.title as routine_title 
        FROM routine_feedback rf 
        JOIN routines r ON rf.routine_id = r.id 
        JOIN users u ON rf.user_id = u.id 
        WHERE r.created_by = ? 
        ORDER BY rf.created_at DESC
    ");
    $stmt->execute([$trainer_id]);
    $feedbacks = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching feedbacks: " . $e->getMessage();
}

// Fetch Active Users (for suggestions)
$users = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT u.id, u.name, u.email FROM users u JOIN activity_logs al ON u.id = al.user_id ORDER BY al.created_at DESC LIMIT 20");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    // $error = "Error fetching users: " . $e->getMessage();
}

// Specific User View
$view_user = null;
$user_logs = [];
if (isset($_GET['view_user'])) {
    $user_id = $_GET['view_user'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $view_user = $stmt->fetch();

    if ($view_user) {
        $stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$user_id]);
        $user_logs = $stmt->fetchAll();
    }
}
?>

<?php include "../ui_header.php"; ?>

<div class="container">
    <div class="card">
        <div class="badge">Trainer Panel</div>
        <h1 class="h1">Trainer Dashboard</h1>
        <p class="p">Manage routines, feedback, and user suggestions.</p>

        <?php if ($error): ?>
            <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div  style="margin-top: 20px;">
            <a class="btn" href="../routines.php">Manage Routines</a>
            <a class="btn secondary" href="../tips.php">Manage Tips</a>
        </div>
    </div>

    <?php if ($view_user): ?>
        <div class="mt-30">
            <a href="dashboard.php" class="btn secondary btn-sm">&larr; Back to Dashboard</a>
            <h2 class="h1" style="margin-top: 20px;">Activity Log: <?php echo htmlspecialchars($view_user['name']); ?></h2>
            
            <div class="grid3">
                <!-- Suggestion Form -->
                <div class="card" style="grid-column: span 1;">
                    <h3>Send Suggestion</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="suggest">
                        <input type="hidden" name="user_id" value="<?php echo $view_user['id']; ?>">
                        <div class="form-group">
                            <label><b>Advice / Modification</b></label>
                            <textarea name="suggestion" rows="5" required placeholder="Suggest changes to their routine or diet..."></textarea>
                        </div>
                        <button type="submit" class="btn">Send Suggestion</button>
                    </form>
                </div>

                <!-- Logs -->
                <div style="grid-column: span 2;">
                    <?php if (empty($user_logs)): ?>
                        <div class="card"><p>No activity logged.</p></div>
                    <?php else: ?>
                        <?php foreach ($user_logs as $log): ?>
                            <div class="card card-item">
                                <div class="flex-between-center">
                                    <span class="badge">
                                        <?php echo ucfirst($log['type']); ?>
                                    </span>
                                    <span class="small"><?php echo $log['date']; ?></span>
                                </div>
                                <h4><?php echo htmlspecialchars($log['content']); ?></h4>
                                <p class="small">Value: <?php echo $log['value']; ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        
        <div class="grid3">
            <!-- Feedbacks Column -->
            <div style="grid-column: span 2;">
                <h2 class="h1">Recent Feedback on Your Routines</h2>
                <?php if (empty($feedbacks)): ?>
                    <div class="card"><p>No feedback yet.</p></div>
                <?php else: ?>
                    <?php foreach ($feedbacks as $fb): ?>
                        <div class="card card-item">
                            <div class="flex-between-center">
                                <strong><?php echo htmlspecialchars($fb['user_name']); ?></strong>
                                <span class="small">on: <a href="../routines.php"><?php echo htmlspecialchars($fb['routine_title']); ?></a></span>
                            </div>
                            <p class="p">"<?php echo htmlspecialchars($fb['comment']); ?>"</p>
                            
                            <button onclick="document.getElementById('reply-<?php echo $fb['id']; ?>').classList.toggle('active')" class="btn secondary btn-sm">Reply</button>
                            
                            <div id="reply-<?php echo $fb['id']; ?>" class="dashed-card">
                                <form method="post">
                                    <input type="hidden" name="action" value="reply">
                                    <input type="hidden" name="feedback_id" value="<?php echo $fb['id']; ?>">
                                    <textarea name="reply" rows="2" placeholder="Write a reply..." required></textarea>
                                    <button type="submit" class="btn btn-sm mt-30">Send Reply</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Active Users Column -->
            <div>
                <h2 class="h1">Active Users</h2>
                <div class="card">
                    <?php if (empty($users)): ?>
                        <p>No active users.</p>
                    <?php else: ?>
                        <ul style="list-style: none; padding: 0;">
                            <?php foreach ($users as $u): ?>
                                <li style="margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                                    <div class="flex-between-center">
                                        <span><?php echo htmlspecialchars($u['name']); ?></span>
                                        <a href="?view_user=<?php echo $u['id']; ?>" class="btn secondary btn-sm">View</a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php include "../ui_footer.php"; ?>
