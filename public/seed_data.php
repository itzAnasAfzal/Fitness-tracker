<?php
require_once __DIR__ . "/../app/config/db.php";

try {
    echo "Starting seeding...<br>";

    // 1. Clear Tables (Order matters because of Foreign Keys)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE trainer_suggestions");
    $pdo->exec("TRUNCATE TABLE trainer_replies");
    $pdo->exec("TRUNCATE TABLE routine_feedback");
    $pdo->exec("TRUNCATE TABLE activity_logs");
    $pdo->exec("TRUNCATE TABLE routines");
    $pdo->exec("TRUNCATE TABLE tips");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Tables cleared.<br>";

    // 2. Ensure Users/Trainers Exist
    $stmt = $pdo->query("SELECT id, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $regular_users = [];
    $trainers = [];
    
    foreach ($users as $u) {
        if ($u['role'] === 'trainer') $trainers[] = $u['id'];
        else $regular_users[] = $u['id'];
    }

    // Create dummy trainers if needed
    if (count($trainers) < 2) {
        for ($i = 1; $i <= 3; $i++) {
            $name = "Trainer " . $i;
            $email = "trainer$i@test.com";
            $pass = password_hash("123456", PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'trainer')");
            $stmt->execute([$name, $email, $pass]);
            $trainers[] = $pdo->lastInsertId();
            echo "Created Trainer: $name<br>";
        }
    }

    // Create dummy users if needed
    if (count($regular_users) < 5) {
        for ($i = 1; $i <= 10; $i++) {
            $name = "User " . $i;
            $email = "user$i@test.com";
            $pass = password_hash("123456", PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$name, $email, $pass]);
            $regular_users[] = $pdo->lastInsertId();
            echo "Created User: $name<br>";
        }
    }
    
    // Refresh lists
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'trainer'");
    $trainers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'user'");
    $regular_users = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Create Routines (Create 25)
    $routine_titles = ["Full Body Blast", "HIIT Cardio", "Yoga for Beginners", "Strength 101", "Abs Crusher", "Leg Day from Hell", "Upper Body Power", "Morning Stretch", "Evening Wind Down", "Marathon Prep"];
    $routine_ids = [];
    
    for ($i = 0; $i < 25; $i++) {
        $title = $routine_titles[array_rand($routine_titles)] . " " . ($i + 1);
        $content = "Do 3 sets of 12 reps. \nRest 60 seconds. \nRepeat for 20 minutes.";
        $trainer_id = $trainers[array_rand($trainers)];
        
        $stmt = $pdo->prepare("INSERT INTO routines (title, content, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$title, $content, $trainer_id]);
        $routine_ids[] = $pdo->lastInsertId();
    }
    echo "Created 25 Routines.<br>";

    // 4. Create Activity Logs (Create 50 for random users)
    $log_types = ['workout', 'meal', 'water'];
    $log_contents = [
        'workout' => ['Running 5km', 'Chest Press', 'Squats', 'Deadlifts', 'Cycling', 'Swimming'],
        'meal' => ['Chicken Salad', 'Oatmeal', 'Protein Shake', 'Steak and Eggs', 'Rice and Beans'],
        'water' => ['Water Intake']
    ];

    for ($i = 0; $i < 60; $i++) {
        $user_id = $regular_users[array_rand($regular_users)];
        $type = $log_types[array_rand($log_types)];
        $content = $log_contents[$type][array_rand($log_contents[$type])];
        $value = ($type == 'water') ? rand(200, 500) : (($type == 'meal') ? rand(300, 800) : rand(15, 90));
        
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, type, content, value, date) VALUES (?, ?, ?, ?, CURDATE())");
        $stmt->execute([$user_id, $type, $content, $value]);
    }
    echo "Created 60 Activity Logs.<br>";

    // 5. Create Feedbacks (Create 60)
    $feedback_comments = ["Great routine!", "Too hard for me.", "Loved it!", "Can you explain step 2?", "Will try this tomorrow.", "Effective!", "My legs represent jelly."];
    $feedback_ids = [];

    for ($i = 0; $i < 60; $i++) {
        $routine_id = $routine_ids[array_rand($routine_ids)];
        $user_id = $regular_users[array_rand($regular_users)];
        $comment = $feedback_comments[array_rand($feedback_comments)];
        
        $stmt = $pdo->prepare("INSERT INTO routine_feedback (routine_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$routine_id, $user_id, $comment]);
        $feedback_ids[] = $pdo->lastInsertId();
    }
    echo "Created 60 Feedbacks.<br>";

    // 6. Create Trainer Replies (Create 30)
    for ($i = 0; $i < 30; $i++) {
        $feedback_id = $feedback_ids[array_rand($feedback_ids)];
        $trainer_id = $trainers[array_rand($trainers)];
        $reply = "Thanks for the feedback! Keep pushing.";
        
        $stmt = $pdo->prepare("INSERT INTO trainer_replies (feedback_id, trainer_id, reply) VALUES (?, ?, ?)");
        $stmt->execute([$feedback_id, $trainer_id, $reply]);
    }
    echo "Created 30 Trainer Replies.<br>";
    
    echo "Seeding Complete!";

} catch (PDOException $e) {
    echo "Seeding Failed: " . $e->getMessage();
}
