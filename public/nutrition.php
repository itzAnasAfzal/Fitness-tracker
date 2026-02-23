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
    if ($_GET['success'] == 'added')   $success = "Nutritional advice added successfully.";
    if ($_GET['success'] == 'updated') $success = "Nutritional advice updated successfully.";
    if ($_GET['success'] == 'deleted') $success = "Nutritional advice deleted successfully.";
}

// Handle POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && $is_admin_or_trainer) {
    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $title   = trim($_POST["title"]   ?? "");
        $content = trim($_POST["content"] ?? "");
        $category = trim($_POST["category"] ?? "general");
        if ($title && $content) {
            $stmt = $pdo->prepare("INSERT INTO nutrition_advice (title, content, category, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $content, $category, $_SESSION["user"]["id"]]);
            header("Location: nutrition.php?success=added");
            exit();
        } else {
            $error = "Title and content are required.";
        }

    } elseif ($action === "update") {
        $id = $_POST["id"] ?? "";
        $title   = trim($_POST["title"]   ?? "");
        $content = trim($_POST["content"] ?? "");
        $category = trim($_POST["category"] ?? "general");
        if ($id && $title && $content) {
            $stmt = $pdo->prepare("UPDATE nutrition_advice SET title=?, content=?, category=? WHERE id=?");
            $stmt->execute([$title, $content, $category, $id]);
            header("Location: nutrition.php?success=updated");
            exit();
        } else {
            $error = "All fields are required.";
        }

    } elseif ($action === "delete") {
        $id = $_POST["id"] ?? "";
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM nutrition_advice WHERE id=?");
            $stmt->execute([$id]);
            header("Location: nutrition.php?success=deleted");
            exit();
        }
    }
}

// Fetch all nutrition advice
$stmt  = $pdo->query("SELECT n.*, u.name as author FROM nutrition_advice n LEFT JOIN users u ON n.created_by = u.id ORDER BY n.created_at DESC");
$items = $stmt->fetchAll();

// Edit mode
$edit_item = null;
if (isset($_GET["edit"]) && $is_admin_or_trainer) {
    $stmt = $pdo->prepare("SELECT * FROM nutrition_advice WHERE id=?");
    $stmt->execute([$_GET["edit"]]);
    $edit_item = $stmt->fetch();
}

// Group by category for display
$categories = [];
foreach ($items as $item) {
    $categories[$item['category']][] = $item;
}

include "ui_header.php";
?>

<div class="card">
    <div class="flex-between-center">
        <h1 class="h1">Nutritional Advice</h1>
        <?php if ($is_admin_or_trainer && !$edit_item): ?>
            <button onclick="document.getElementById('add-form').classList.toggle('active')" class="btn">Add Advice</button>
        <?php endif; ?>
    </div>
    <p class="p">Expert nutritional guidance to fuel your fitness journey. Browse by category or read all advice below.</p>

    <?php if ($error): ?>
        <div class="msg-error"><b><?php echo htmlspecialchars($error); ?></b></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="msg-success"><b><?php echo htmlspecialchars($success); ?></b></div>
    <?php endif; ?>

    <!-- Add Form -->
    <div id="add-form" class="dashed-card">
        <h3>Add Nutritional Advice</h3>
        <form method="post" action="nutrition.php">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label><b>Title</b></label><br>
                <input type="text" name="title" required placeholder="e.g. Importance of Protein">
            </div>
            <div class="form-group">
                <label><b>Category</b></label><br>
                <select name="category" class="neo-select">
                    <option value="general">General</option>
                    <option value="macros">Macronutrients</option>
                    <option value="vitamins">Vitamins & Minerals</option>
                    <option value="hydration">Hydration</option>
                    <option value="diet_plans">Diet Plans</option>
                    <option value="supplements">Supplements</option>
                </select>
            </div>
            <div class="form-group">
                <label><b>Content</b></label><br>
                <textarea name="content" rows="4" required placeholder="Write the nutritional advice here..."></textarea>
            </div>
            <button type="submit" class="btn">Save Advice</button>
            <button type="button" class="btn secondary" onclick="document.getElementById('add-form').classList.remove('active')">Cancel</button>
        </form>
    </div>

    <!-- Edit Form -->
    <?php if ($edit_item): ?>
        <div class="dashed-card-edit">
            <h3>Edit Nutritional Advice</h3>
            <form method="post" action="nutrition.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo $edit_item['id']; ?>">
                <div class="form-group">
                    <label><b>Title</b></label><br>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($edit_item['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label><b>Category</b></label><br>
                    <select name="category" class="neo-select">
                        <?php
                        $cats = ['general'=>'General','macros'=>'Macronutrients','vitamins'=>'Vitamins & Minerals','hydration'=>'Hydration','diet_plans'=>'Diet Plans','supplements'=>'Supplements'];
                        foreach ($cats as $val => $label):
                        ?>
                            <option value="<?php echo $val; ?>" <?php echo $edit_item['category']===$val?'selected':''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><b>Content</b></label><br>
                    <textarea name="content" rows="4" required><?php echo htmlspecialchars($edit_item['content']); ?></textarea>
                </div>
                <button type="submit" class="btn">Update</button>
                <a href="nutrition.php" class="btn secondary">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <!-- Category Filter Tabs -->
    <?php if (!empty($items)): ?>
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin: 24px 0 10px;">
        <button class="btn btn-sm cat-filter active" onclick="filterCat('all', this)">All</button>
        <?php foreach (array_keys($categories) as $cat): ?>
            <button class="btn secondary btn-sm cat-filter" onclick="filterCat('<?php echo $cat; ?>', this)">
                <?php echo ucwords(str_replace('_',' ',$cat)); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- List -->
    <div class="mt-30" id="nutrition-list">
        <?php foreach ($items as $nIndex => $item): ?>
            <div class="card card-item nutrition-card" data-cat="<?php echo htmlspecialchars($item['category']); ?>"
                 style="<?php echo $nIndex >= 10 ? 'display:none;' : ''; ?>"
                 data-index="<?php echo $nIndex; ?>">
                <div class="flex-between">
                    <div>
                        <span class="badge" style="font-size:11px; margin-bottom:6px; display:inline-block;">
                            <?php echo ucwords(str_replace('_',' ',$item['category'])); ?>
                        </span>
                        <h2 style="margin:4px 0 0;"><?php echo htmlspecialchars($item['title']); ?></h2>
                    </div>
                    <?php if ($is_admin_or_trainer): ?>
                        <div>
                            <a href="nutrition.php?edit=<?php echo $item['id']; ?>" class="btn secondary btn-sm">Edit</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this advice?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="pre-line" style="margin-top:10px;"><?php echo htmlspecialchars($item['content']); ?></p>
                <small style="color:#666;">
                    Posted by <?php echo htmlspecialchars($item['author'] ?? 'Unknown'); ?> on <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                </small>
            </div>
        <?php endforeach; ?>

        <?php if (empty($items)): ?>
            <p>No nutritional advice available yet.
                <?php if (!$is_admin_or_trainer): ?> Check back soon!<?php endif; ?>
            </p>
        <?php elseif (count($items) > 10): ?>
            <div style="text-align:center; margin-top:20px;">
                <button id="load-more-nutrition" class="btn secondary" onclick="loadMoreNutrition()">Load More</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Load More
function loadMoreNutrition() {
    const hidden = document.querySelectorAll('.nutrition-card[style*="display:none"]');
    let count = 0;
    hidden.forEach(el => {
        if (count < 10) {
            el.style.display = 'block';
            count++;
        }
    });
    if (document.querySelectorAll('.nutrition-card[style*="display:none"]').length === 0) {
        const btn = document.getElementById('load-more-nutrition');
        if (btn) btn.style.display = 'none';
    }
}

// Category filter
function filterCat(cat, btn) {
    document.querySelectorAll('.cat-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.nutrition-card');
    let visible = 0;
    cards.forEach(card => {
        if (cat === 'all' || card.dataset.cat === cat) {
            card.style.display = visible >= 10 ? 'none' : 'block';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    const loadBtn = document.getElementById('load-more-nutrition');
    if (loadBtn) {
        loadBtn.style.display = visible > 10 ? 'block' : 'none';
    }
}
</script>

<?php include "ui_footer.php"; ?>
