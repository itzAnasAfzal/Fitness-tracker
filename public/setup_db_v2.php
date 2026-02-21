<?php
require_once __DIR__ . "/../app/config/db.php";

try {
    // Activity Logs Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('workout', 'meal', 'water') NOT NULL,
        content TEXT,
        value INT DEFAULT 0,
        date DATE DEFAULT CURRENT_DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Activity Logs table created/checked.<br>";

    // Routine Feedback Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS routine_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        routine_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (routine_id) REFERENCES routines(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Routine Feedback table created/checked.<br>";

    // Trainer Replies Table (for feedback)
    $pdo->exec("CREATE TABLE IF NOT EXISTS trainer_replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        feedback_id INT NOT NULL,
        trainer_id INT NOT NULL,
        reply TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (feedback_id) REFERENCES routine_feedback(id) ON DELETE CASCADE,
        FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Trainer Replies table created/checked.<br>";

    // Trainer Suggestions Table (for user activity)
    $pdo->exec("CREATE TABLE IF NOT EXISTS trainer_suggestions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        trainer_id INT NOT NULL,
        suggestion TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Trainer Suggestions table created/checked.<br>";

} catch (PDOException $e) {
    die("DB Setup V2 failed: " . $e->getMessage());
}
?>
