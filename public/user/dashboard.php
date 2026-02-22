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

// ═══════════════════════════════════════════
// Fetch Chart Data — last 30 days
// ═══════════════════════════════════════════
$chart_labels   = [];
$chart_workouts = [];
$chart_calories = [];
$chart_water    = [];

try {
    // Build last-30-days array
    $today = new DateTime();
    for ($i = 29; $i >= 0; $i--) {
        $d = (clone $today)->modify("-$i days");
        $chart_labels[]   = $d->format('M d');
        $chart_workouts[] = 0;
        $chart_calories[] = 0;
        $chart_water[]    = 0;
    }

    // Workout counts per day
    $stmt = $pdo->prepare("
        SELECT date, COUNT(*) as cnt
        FROM activity_logs
        WHERE user_id = ? AND type = 'workout'
          AND date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY date ORDER BY date ASC
    ");
    $stmt->execute([$user['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $key = array_search(date('M d', strtotime($row['date'])), $chart_labels);
        if ($key !== false) $chart_workouts[$key] = (int)$row['cnt'];
    }

    // Calories per day
    $stmt = $pdo->prepare("
        SELECT date, SUM(value) as total
        FROM activity_logs
        WHERE user_id = ? AND type = 'meal'
          AND date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY date ORDER BY date ASC
    ");
    $stmt->execute([$user['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $key = array_search(date('M d', strtotime($row['date'])), $chart_labels);
        if ($key !== false) $chart_calories[$key] = (int)$row['total'];
    }

    // Water per day
    $stmt = $pdo->prepare("
        SELECT date, SUM(value) as total
        FROM activity_logs
        WHERE user_id = ? AND type = 'water'
          AND date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY date ORDER BY date ASC
    ");
    $stmt->execute([$user['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $key = array_search(date('M d', strtotime($row['date'])), $chart_labels);
        if ($key !== false) $chart_water[$key] = (int)$row['total'];
    }

} catch (PDOException $e) {
    // Charts just won't render if DB error
}

$json_labels   = json_encode($chart_labels);
$json_workouts = json_encode($chart_workouts);
$json_calories = json_encode($chart_calories);
$json_water    = json_encode($chart_water);

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

    <!-- Progress Charts -->
    <div class="mt-30">
        <h2 class="h1">📊 Progress Charts</h2>
        <p class="p" style="margin-bottom:20px;">Your activity over the last 30 days.</p>

        <?php
        $hasData = array_sum($chart_workouts) + array_sum($chart_calories) + array_sum($chart_water) > 0;
        if (!$hasData): ?>
            <div class="card">
                <p class="p">No activity data yet. Start logging workouts, meals, and water to see your charts!</p>
            </div>
        <?php else: ?>
        <!-- Chart Tabs -->
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
            <button class="btn btn-sm" id="tab-workout" onclick="showChart('workout')">🏋️ Workouts</button>
            <button class="btn secondary btn-sm" id="tab-meal"    onclick="showChart('meal')">🥗 Calories</button>
            <button class="btn secondary btn-sm" id="tab-water"   onclick="showChart('water')">💧 Water</button>
        </div>
        <div class="card" style="padding:20px;">
            <div id="chart-workout">
                <h3 style="margin:0 0 12px;">🏋️ Workouts Per Day</h3>
                <canvas id="workoutChart" height="100"></canvas>
            </div>
            <div id="chart-meal" style="display:none;">
                <h3 style="margin:0 0 12px;">🥗 Calories Logged Per Day (kcal)</h3>
                <canvas id="mealChart" height="100"></canvas>
            </div>
            <div id="chart-water" style="display:none;">
                <h3 style="margin:0 0 12px;">💧 Water Intake Per Day (ml)</h3>
                <canvas id="waterChart" height="100"></canvas>
            </div>
        </div>
        <?php endif; ?>
    </div>

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


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labels   = <?= $json_labels ?>;
const wData    = <?= $json_workouts ?>;
const calData  = <?= $json_calories ?>;
const h2oData  = <?= $json_water ?>;

const baseOpts = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
        y: { beginAtZero: true, grid: { color: '#f0f0f0' } }
    }
};

const wChart = document.getElementById('workoutChart');
const mChart = document.getElementById('mealChart');
const hChart = document.getElementById('waterChart');

if (wChart) new Chart(wChart, { type: 'bar', data: { labels, datasets: [{ label: 'Workouts', data: wData, backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 6, borderSkipped: false }] }, options: baseOpts });
if (mChart) new Chart(mChart, { type: 'bar', data: { labels, datasets: [{ label: 'Calories (kcal)', data: calData, backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 6, borderSkipped: false }] }, options: baseOpts });
if (hChart) new Chart(hChart, { type: 'line', data: { labels, datasets: [{ label: 'Water (ml)', data: h2oData, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', pointBackgroundColor: '#3b82f6', tension: 0.3, fill: true }] }, options: baseOpts });

function showChart(type) {
    ['workout','meal','water'].forEach(t => {
        document.getElementById('chart-' + t).style.display = t === type ? 'block' : 'none';
        const btn = document.getElementById('tab-' + t);
        if (btn) btn.className = t === type ? 'btn btn-sm' : 'btn secondary btn-sm';
    });
}
</script>

<?php include "../ui_footer.php"; ?>
