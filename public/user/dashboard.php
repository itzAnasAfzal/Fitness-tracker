<?php
require_once __DIR__ . "/../../app/config/auth.php";
require_once __DIR__ . "/../../app/config/db.php";
require_role("user");

$user = $_SESSION["user"];
$error = "";
$success = "";

// Handle Form Submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = $_POST["type"] ?? "";
    $content = trim($_POST["content"] ?? "");
    $value = intval($_POST["value"] ?? 0);

    if ($type && $content) {
        try {
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, type, content, value, date) VALUES (?, ?, ?, ?, CURDATE())");
            $stmt->execute([$user["id"], $type, $content, $value]);
            $success = ucfirst($type) . " logged successfully!";
            header("Location: dashboard.php"); // PRG pattern
            exit;
        } catch (PDOException $e) {
            $error = "Error logging activity: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle Delete/Update Log
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_log') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user['id']]);
        header("Location: dashboard.php");
        exit;
    }
    if ($_POST['action'] === 'update_log') {
        $id = $_POST['id'];
        $content = $_POST['content'];
        $value = $_POST['value'];
        $stmt = $pdo->prepare("UPDATE activity_logs SET content = ?, value = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$content, $value, $id, $user['id']]);
        header("Location: dashboard.php");
        exit;
    }
}

// Fetch Recent Activity (Fetch ALL for Load More JS)
$logs = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user["id"]]);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching logs: " . $e->getMessage();
}

// Fetch Trainer Replies (Recent)
$replies = [];
try {
    $stmt = $pdo->prepare("
        SELECT tr.*, r.title as routine_title, u.name as trainer_name, rf.comment as my_comment
        FROM trainer_replies tr 
        JOIN routine_feedback rf ON tr.feedback_id = rf.id 
        JOIN routines r ON rf.routine_id = r.id 
        JOIN users u ON tr.trainer_id = u.id
        WHERE rf.user_id = ? 
        ORDER BY tr.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user["id"]]);
    $replies = $stmt->fetchAll();
} catch (PDOException $e) {
    // ignore
}


// Fetch Stats
$stats = ['workouts' => 0, 'calories' => 0, 'water_avg' => 0];
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM activity_logs WHERE user_id = ? AND type = 'workout'");
    $stmt->execute([$user['id']]);
    $stats['workouts'] = $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT SUM(value) as total FROM activity_logs WHERE user_id = ? AND type = 'meal'");
    $stmt->execute([$user['id']]);
    $stats['calories'] = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("SELECT AVG(value) as avg FROM activity_logs WHERE user_id = ? AND type = 'water'");
    $stmt->execute([$user['id']]);
    $stats['water_avg'] = round($stmt->fetch()['avg'] ?? 0);
} catch (PDOException $e) {
    // ignore
}

// Fetch Trainer Suggestions
$suggestions = [];
try {
    $stmt = $pdo->prepare("SELECT ts.*, u.name as trainer_name FROM trainer_suggestions ts JOIN users u ON ts.trainer_id = u.id WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $suggestions = $stmt->fetchAll();
} catch (PDOException $e) {
    // ignore
}

?>

<?php include "../ui_header.php"; ?>

<div class="container">
    <div class="card">
        <div class="badge">My Fitness</div>
        <h1 class="h1">Welcome, <?php echo htmlspecialchars($user["name"]); ?> 💪</h1>
        <p class="p">Track your daily progress and stay consistent!</p>

        <?php if ($error): ?>
            <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="grid3" style="margin-bottom: 20px;">
            <div style="background: #eef2ff; padding: 15px; border-radius: 12px; text-align: center;">
                <h3 style="margin: 0; font-size: 24px; color: var(--accent);"><?php echo $stats['workouts']; ?></h3>
                <small>Total Workouts</small>
            </div>
            <div style="background: #ecfdf5; padding: 15px; border-radius: 12px; text-align: center;">
                <h3 style="margin: 0; font-size: 24px; color: var(--accent2);"><?php echo $stats['calories']; ?></h3>
                <small>Total Calories Logged</small>
            </div>
            <div style="background: #eff6ff; padding: 15px; border-radius: 12px; text-align: center;">
                <h3 style="margin: 0; font-size: 24px; color: #3b82f6;"><?php echo $stats['water_avg']; ?> ml</h3>
                <small>Avg Water Intake</small>
            </div>
        </div>

        <div class="grid3">
            <button onclick="toggleForm('workout-form')" class="btn">🏋️ Log Workout</button>
            <button onclick="toggleForm('meal-form')" class="btn secondary">🥗 Log Meal</button>
            <button onclick="toggleForm('water-form')" class="btn secondary">💧 Log Water</button>
        </div>

        <!-- Workout Form -->
        <div id="workout-form" class="dashed-card">
            <h3>Log Workout</h3>
            <form method="post">
                <input type="hidden" name="type" value="workout">
                <div class="form-group">
                    <label><b>Workout Details</b></label>
                    <input type="text" name="content" required placeholder="e.g. 30 mins running, Chest Day">
                </div>
                <div class="form-group">
                    <label><b>Duration (mins)</b></label>
                    <input type="number" name="value" required placeholder="e.g. 45">
                </div>
                <button type="submit" class="btn">Save Workout</button>
                <button type="button" class="btn secondary" onclick="toggleForm('workout-form')">Cancel</button>
            </form>
        </div>

        <!-- Meal Form -->
        <div id="meal-form" class="dashed-card">
            <h3>Log Meal</h3>
            <form method="post">
                <input type="hidden" name="type" value="meal">
                <div class="form-group">
                    <label><b>Meal Description</b></label>
                    <input type="text" name="content" required placeholder="e.g. Grilled Chicken Salad">
                </div>
                <div class="form-group">
                    <label><b>Calories (kcal)</b></label>
                    <input type="number" name="value" required placeholder="e.g. 500">
                </div>
                <button type="submit" class="btn">Save Meal</button>
                <button type="button" class="btn secondary" onclick="toggleForm('meal-form')">Cancel</button>
            </form>
        </div>

        <!-- Water Form -->
        <div id="water-form" class="dashed-card">
            <h3>Log Water Intake</h3>
            <form method="post">
                <input type="hidden" name="type" value="water">
                <div class="form-group">
                    <label><b>Amount</b></label>
                    <input type="hidden" name="content" value="Water Intake">
                    <div class="flex-between-center">
                        <input type="number" name="value" required placeholder="e.g. 250 (ml)">
                        <span style="margin-left: 10px;">ml</span>
                    </div>
                </div>
                <button type="submit" class="btn">Save Water</button>
                <button type="button" class="btn secondary" onclick="toggleForm('water-form')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Trainer Replies -->
    <?php if (!empty($replies)): ?>
        <div class="mt-30">
            <h2 class="h1">New Replies from Trainers</h2>
            <div class="grid3">
                <?php foreach ($replies as $reply): ?>
                    <div class="card" style="border: 1px solid var(--accent2);">
                        <div class="flex-between-center">
                            <strong style="color: var(--accent);">Trainer: <?php echo htmlspecialchars($reply['trainer_name']); ?></strong>
                            <span class="small"><?php echo date('M d', strtotime($reply['created_at'])); ?></span>
                        </div>
                        <p class="small" style="color: #666; margin: 5px 0;">On: <?php echo htmlspecialchars($reply['routine_title']); ?></p>
                        <p class="small" style="font-style: italic;">"<?php echo htmlspecialchars($reply['my_comment']); ?>"</p>
                        <hr style="border: 0; border-top: 1px solid #eee;">
                        <p class="p">"<?php echo htmlspecialchars($reply['reply']); ?>"</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Trainer Suggestions -->
    <?php if (!empty($suggestions)): ?>
        <div class="mt-30">
            <h2 class="h1">Trainer Suggestions</h2>
            <div class="grid3">
                <?php foreach ($suggestions as $sugg): ?>
                    <div class="card" style="border: 1px solid var(--accent);">
                        <div class="flex-between-center">
                            <strong style="color: var(--accent);">From: <?php echo htmlspecialchars($sugg['trainer_name']); ?></strong>
                            <span class="small"><?php echo date('M d', strtotime($sugg['created_at'])); ?></span>
                        </div>
                        <p class="p" style="margin-top: 10px;">
                            "<?php echo htmlspecialchars($sugg['suggestion']); ?>"
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-30">
        <h2 class="h1">Recent Activity</h2>
        <?php if (empty($logs)): ?>
            <div class="card">
                <p class="p">No activity logged yet. Start today!</p>
            </div>
        <?php else: ?>
            <div class="grid3" id="logs-container">
                <?php foreach ($logs as $index => $log): ?>
                    <div class="card log-item <?php echo $index >= 10 ? 'hidden-log' : ''; ?>" style="<?php echo $index >= 10 ? 'display: none;' : ''; ?>">
                        <div class="flex-between-center">
                            <span class="badge">
                                <?php 
                                    if($log['type'] == 'workout') echo '🏋️ Workout';
                                    elseif($log['type'] == 'meal') echo '🥗 Meal';
                                    else echo '💧 Water';
                                ?>
                            </span>
                            <span class="small"><?php echo htmlspecialchars($log['date']); ?></span>
                        </div>
                        
                        <div id="view-log-<?php echo $log['id']; ?>">
                            <h3 style="margin: 10px 0; font-size: 18px;">
                                <?php echo htmlspecialchars($log['content']); ?>
                            </h3>
                            <p class="p">
                                <?php 
                                    if($log['type'] == 'workout') echo 'Duration: ' . $log['value'] . ' mins';
                                    elseif($log['type'] == 'meal') echo 'Calories: ' . $log['value'] . ' kcal';
                                    else echo 'Amount: ' . $log['value'] . ' ml';
                                ?>
                            </p>
                            <div style="margin-top: 10px;">
                                <button class="btn secondary btn-sm" onclick="toggleEdit('<?php echo $log['id']; ?>')">Edit</button>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Delete this log?');">
                                    <input type="hidden" name="action" value="delete_log">
                                    <input type="hidden" name="id" value="<?php echo $log['id']; ?>">
                                    <button class="btn-delete">Delete</button>
                                </form>
                            </div>
                        </div>

                        <!-- Edit Form -->
                        <div id="edit-log-<?php echo $log['id']; ?>" style="display: none; margin-top: 10px;">
                            <form method="post">
                                <input type="hidden" name="action" value="update_log">
                                <input type="hidden" name="id" value="<?php echo $log['id']; ?>">
                                <div class="form-group">
                                    <label>Content</label>
                                    <input type="text" name="content" value="<?php echo htmlspecialchars($log['content']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Value</label>
                                    <input type="number" name="value" value="<?php echo htmlspecialchars($log['value']); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-sm">Update</button>
                                <button type="button" class="btn secondary btn-sm" onclick="toggleEdit('<?php echo $log['id']; ?>')">Cancel</button>
                            </form>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($logs) > 10): ?>
                <div style="text-align: center; margin-top: 20px;">
                    <button id="load-more-btn" class="btn secondary" onclick="loadMoreLogs()">Load More</button>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<script>
function toggleForm(id) {
    document.querySelectorAll('.dashed-card').forEach(el => {
        if (el.id !== id) el.classList.remove('active');
    });
    const form = document.getElementById(id);
    if (form) form.classList.toggle('active');
}

function toggleEdit(id) {
    const view = document.getElementById('view-log-' + id);
    const edit = document.getElementById('edit-log-' + id);
    if (view.style.display === 'none') {
        view.style.display = 'block';
        edit.style.display = 'none';
    } else {
        view.style.display = 'none';
        edit.style.display = 'block';
    }
}

let visibleLogs = 10;
function loadMoreLogs() {
    const hiddenLogs = document.querySelectorAll('.hidden-log');
    let count = 0;
    hiddenLogs.forEach(log => {
        if (count < 10) {
            log.style.display = 'block';
            log.classList.remove('hidden-log');
            count++;
        }
    });
    
    // Hide button if no more logs
    if (document.querySelectorAll('.hidden-log').length === 0) {
        document.getElementById('load-more-btn').style.display = 'none';
    }
}
</script>

<?php include "../ui_footer.php"; ?>
