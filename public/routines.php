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
    if ($_GET['success'] == 'added') $success = "Routine added successfully.";
    if ($_GET['success'] == 'updated') $success = "Routine updated successfully.";
    if ($_GET['success'] == 'deleted') $success = "Routine deleted successfully.";
}


// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST" && $is_admin_or_trainer) {
    $action = $_POST["action"] ?? "";
    
if ($action === "add") {
    $title = trim($_POST["title"] ?? "");
    $content = trim($_POST["content"] ?? "");
    
    if ($title && $content) {
            $stmt = $pdo->prepare("INSERT INTO routines (title, content, created_by) VALUES (?, ?, ?)");
            $stmt->execute([$title, $content, $_SESSION["user"]["id"]]);

            // 🔥 REDIRECT (VERY IMPORTANT)
            header("Location: routines.php?success=added");
            exit();
        } else {
            $error = "Title and content are required.";
        }
    } elseif ($action === "update") {
        $id = $_POST["id"] ?? "";
        $title = trim($_POST["title"] ?? "");
        $content = trim($_POST["content"] ?? "");
        
        if ($id && $title && $content) {
            $stmt = $pdo->prepare("UPDATE routines SET title = ?, content = ? WHERE id = ?");
            $stmt->execute([$title, $content, $id]);

            header("Location: routines.php?success=updated");
            exit();
        } else {
            $error = "All fields are required.";
        }
    } elseif ($action === "delete") {
        $id = $_POST["id"] ?? "";
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM routines WHERE id = ?");
            $stmt->execute([$id]);

            header("Location: routines.php?success=deleted");
            exit();
        }
    }
}

// Handle Feedback (Any logged in user)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'feedback' && is_logged_in()) {
    $routine_id = $_POST["routine_id"] ?? "";
    $comment = trim($_POST["comment"] ?? "");
    
    if ($routine_id && $comment) {
        $stmt = $pdo->prepare("INSERT INTO routine_feedback (routine_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$routine_id, $_SESSION["user"]["id"], $comment]);
        header("Location: routines.php?success=feedback_added");
        exit();
    }
}

// Handle Trainer Reply
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'reply' && $is_admin_or_trainer) {
    $feedback_id = $_POST["feedback_id"] ?? "";
    $reply = trim($_POST["reply"] ?? "");
    
    if ($feedback_id && $reply) {
        $stmt = $pdo->prepare("INSERT INTO trainer_replies (feedback_id, trainer_id, reply) VALUES (?, ?, ?)");
        $stmt->execute([$feedback_id, $_SESSION["user"]["id"], $reply]);
        header("Location: routines.php?success=reply_added");
        exit();
    }
}


// Fetch Routines
$stmt = $pdo->query("SELECT r.*, u.name as author FROM routines r LEFT JOIN users u ON r.created_by = u.id ORDER BY created_at DESC");
$routines = $stmt->fetchAll();

// Fetch Feedbacks (include poster's role for display)
$feedbacks = [];
$stmt = $pdo->query("SELECT rf.*, u.name as user_name, u.role as user_role FROM routine_feedback rf JOIN users u ON rf.user_id = u.id ORDER BY rf.created_at ASC");
while ($row = $stmt->fetch()) {
    $feedbacks[$row['routine_id']][] = $row;
}

// Fetch Replies (include replier role so we can show correct tag)
$replies = [];
$stmt = $pdo->query("SELECT tr.*, u.name as trainer_name, u.role as replier_role FROM trainer_replies tr JOIN users u ON tr.trainer_id = u.id ORDER BY tr.created_at ASC");
while ($row = $stmt->fetch()) {
    $replies[$row['feedback_id']][] = $row;
}

// Edit Mode
$edit_routine = null;
if (isset($_GET["edit"]) && $is_admin_or_trainer) {
    $stmt = $pdo->prepare("SELECT * FROM routines WHERE id = ?");
    $stmt->execute([$_GET["edit"]]);
    $edit_routine = $stmt->fetch();
}

include "ui_header.php";
?>

<div class="card">
    <div class="flex-between-center">
        <h1 class="h1">Workout Routines</h1>
        <?php if ($is_admin_or_trainer && !$edit_routine): ?>
            <button onclick="document.getElementById('add-form').classList.toggle('active')" class="btn">Add New Routine</button>
        <?php endif; ?>
    </div>
    <p class="p">Browse workout plans curated by our trainers.</p>

    <?php if ($error): ?>
        <div class="msg-error"><b><?php echo htmlspecialchars($error); ?></b></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="msg-success"><b><?php echo htmlspecialchars($success); ?></b></div>
    <?php endif; ?>

    <!-- Add Form -->
    <div id="add-form" class="dashed-card">
        <h3>Add New Routine</h3>
        <form method="post" action="routines.php">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label><b>Title</b></label><br>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label><b>Content</b></label><br>
                <textarea name="content" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn">Save Routine</button>
            <button type="button" class="btn secondary" onclick="document.getElementById('add-form').classList.remove('active')">Cancel</button>
        </form>
    </div>

    <!-- Edit Form -->
    <?php if ($edit_routine): ?>
        <div class="dashed-card-edit">
            <h3>Edit Routine</h3>
            <form method="post" action="routines.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo $edit_routine['id']; ?>">
                <div class="form-group">
                    <label><b>Title</b></label><br>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($edit_routine['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label><b>Content</b></label><br>
                    <textarea name="content" rows="4" required><?php echo htmlspecialchars($edit_routine['content']); ?></textarea>
                </div>
                <button type="submit" class="btn">Update Routine</button>
                <a href="routines.php" class="btn secondary">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <!-- List -->
    <div class="mt-30">
        <?php foreach ($routines as $rIndex => $routine): ?>
            <div class="card card-item <?php echo $rIndex >= 10 ? 'hidden-routine' : ''; ?>" style="<?php echo $rIndex >= 10 ? 'display: none;' : ''; ?>">
                <div class="flex-between">
                    <h2 style="margin-top: 0;"><?php echo htmlspecialchars($routine['title']); ?></h2>
                    <?php if ($is_admin_or_trainer): ?>
                        <div>
                            <a href="routines.php?edit=<?php echo $routine['id']; ?>" class="btn secondary btn-sm">Edit</a>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $routine['id']; ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="pre-line"><?php echo htmlspecialchars($routine['content']); ?></p>
                <small style="color: #666;">Posted by <?php echo htmlspecialchars($routine['author'] ?? 'Unknown'); ?> on <?php echo date('M d, Y', strtotime($routine['created_at'])); ?></small>
                
                <!-- Feedback Section -->
                <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                    <h4 style="margin: 0 0 10px;">Feedback</h4>
                    
                    <?php 
                    $current_feedbacks = $feedbacks[$routine['id']] ?? [];
                    if (empty($current_feedbacks)): 
                    ?>
                        <p class="small">No feedback yet.</p>
                    <?php else: ?>
                        <?php foreach ($current_feedbacks as $fIndex => $fb): ?>
                            <div class="feedback-item-<?php echo $routine['id']; ?> <?php echo $fIndex >= 10 ? 'hidden-feedback' : ''; ?>" style="background: #f9f9f9; padding: 10px; border-radius: 8px; margin-bottom: 10px; <?php echo $fIndex >= 10 ? 'display: none;' : ''; ?>">
                                <div class="flex-between-center">
                                    <?php
                                        $fbTag = match($fb['user_role'] ?? 'user') {
                                            'admin'   => 'Admin',
                                            'trainer' => 'Trainer',
                                            default   => 'User',
                                        };
                                    ?>
                                    <strong><?php echo htmlspecialchars($fb['user_name']); ?> <span class="badge" style="font-size:11px;"><?php echo $fbTag; ?></span></strong>
                                    <span class="small"><?php echo date('M d, H:i', strtotime($fb['created_at'])); ?></span>
                                </div>
                                <p style="margin: 5px 0;"><?php echo htmlspecialchars($fb['comment']); ?></p>
                                
                                <!-- Replies -->
                                <?php 
                                $current_replies = $replies[$fb['id']] ?? [];
                                foreach ($current_replies as $reply):
                                ?>
                                    <div style="margin-left: 20px; background: #eef2ff; padding: 8px; border-radius: 6px; margin-top: 5px;">
                                        <div class="flex-between-center">
                                            <?php
                                                $tag = match($reply['replier_role'] ?? 'user') {
                                                    'admin'   => 'Admin',
                                                    'trainer' => 'Trainer',
                                                    default   => 'User',
                                                };
                                            ?>
                                            <strong style="color: var(--accent);"><?php echo htmlspecialchars($reply['trainer_name']); ?> <span class="badge" style="font-size:11px;"><?php echo $tag; ?></span></strong>
                                            <span class="small"><?php echo date('M d, H:i', strtotime($reply['created_at'])); ?></span>
                                        </div>
                                        <p style="margin: 5px 0;"><?php echo htmlspecialchars($reply['reply']); ?></p>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Reply Form for Trainer -->
                                <?php if ($is_admin_or_trainer): ?>
                                    <button onclick="document.getElementById('reply-form-<?php echo $fb['id']; ?>').classList.toggle('active')" class="btn secondary btn-sm" style="margin-top: 5px;">Reply</button>
                                    <div id="reply-form-<?php echo $fb['id']; ?>" class="dashed-card" style="margin-top: 10px; padding: 10px;">
                                        <form method="post">
                                            <input type="hidden" name="action" value="reply">
                                            <input type="hidden" name="feedback_id" value="<?php echo $fb['id']; ?>">
                                            <textarea name="reply" rows="2" placeholder="Write a reply..." required style="width: 100%; margin-bottom: 5px;"></textarea>
                                            <button type="submit" class="btn btn-sm">Post Reply</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($current_feedbacks) > 10): ?>
                            <button id="load-more-fb-<?php echo $routine['id']; ?>" class="btn secondary btn-sm" onclick="loadMoreFeedbacks(<?php echo $routine['id']; ?>)">Load More Feedbacks</button>
                        <?php endif; ?>

                    <?php endif; ?>

                    <!-- Add Feedback Form -->
                    <?php if (is_logged_in()): ?>
                        <button onclick="document.getElementById('feedback-form-<?php echo $routine['id']; ?>').classList.toggle('active')" class="btn secondary btn-sm" style="margin-top: 10px;">Add Feedback</button>
                        <div id="feedback-form-<?php echo $routine['id']; ?>" class="dashed-card" style="margin-top: 10px;">
                            <form method="post">
                                <input type="hidden" name="action" value="feedback">
                                <input type="hidden" name="routine_id" value="<?php echo $routine['id']; ?>">
                                <textarea name="comment" rows="2" placeholder="Share your thoughts..." required style="width: 100%; margin-bottom: 10px;"></textarea>
                                <button type="submit" class="btn btn-sm">Post Feedback</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p class="small"><i><a href="login.php">Login</a> to share feedback.</i></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($routines)): ?>
            <p>No routines available yet.</p>
        <?php elseif (count($routines) > 10): ?>
            <div style="text-align: center; margin-top: 20px;">
                <button id="load-more-routines" class="btn secondary" onclick="loadMoreRoutines()">Load More Routines</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function loadMoreRoutines() {
    const hidden = document.querySelectorAll('.hidden-routine');
    let count = 0;
    hidden.forEach(el => {
        if (count < 10) {
            el.style.display = 'block';
            el.classList.remove('hidden-routine');
            count++;
        }
    });
    if (document.querySelectorAll('.hidden-routine').length === 0) {
        document.getElementById('load-more-routines').style.display = 'none';
    }
}

function loadMoreFeedbacks(routineId) {
    const hidden = document.querySelectorAll(`.feedback-item-${routineId}.hidden-feedback`);
    let count = 0;
    hidden.forEach(el => {
        if (count < 4) {
            el.style.display = 'block';
            el.classList.remove('hidden-feedback');
            count++;
        }
    });
    if (document.querySelectorAll(`.feedback-item-${routineId}.hidden-feedback`).length === 0) {
        const btn = document.getElementById(`load-more-fb-${routineId}`);
        if (btn) btn.style.display = 'none';
    }
}
</script>

<?php include "ui_footer.php"; ?>
