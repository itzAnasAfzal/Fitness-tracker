<?php
// -------------------
// CONFIG
// -------------------
$host = 'localhost';
$db   = 'fitness_tracker';
$user = 'root';      // change if needed
$pass = '';          // change if needed
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// -------------------
// 1. USERS
// -------------------
$users = [
    ['Admin User','admin@gmail.com','Admin1234','admin'],
    ['Trainer One','trainer1@gmail.com','Trainer123','trainer'],
    ['Trainer Two','trainer2@gmail.com','Trainer123','trainer'],
    ['Trainer Three','trainer3@gmail.com','Trainer123','trainer'],
];
for ($i=1; $i<=11; $i++) {
    $users[] = ["User $i","user$i@gmail.com","User123",'user'];
}

$userIds = [];
$stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?, ?, ?, ?)");
foreach ($users as $u){
    [$name,$email,$password,$role] = $u;
    $hash = password_hash($password,PASSWORD_BCRYPT);
    $stmt->execute([$name,$email,$hash,$role]);
    $userIds[] = $pdo->lastInsertId();
}

// Map roles for convenience
$adminId = $userIds[0];
$trainerIds = array_slice($userIds,1,3);
$userIdsList = array_slice($userIds,4);

// -------------------
// 2. ROUTINES
// -------------------
$routineStmt = $pdo->prepare("INSERT INTO routines (title,content,created_by) VALUES (?,?,?)");
for($i=0;$i<15;$i++){
    $title = "Routine ".($i+1);
    $content = "Content for ".$title;
    $creator = ($i%4==3)?$adminId:$trainerIds[$i%3];
    $routineStmt->execute([$title,$content,$creator]);
}
$routineIds = $pdo->query("SELECT id FROM routines")->fetchAll(PDO::FETCH_COLUMN);

// -------------------
// 3. TIPS
// -------------------
$tipStmt = $pdo->prepare("INSERT INTO tips (title,content,created_by) VALUES (?,?,?)");
for($i=0;$i<15;$i++){
    $title = "Tip ".($i+1);
    $content = "Content for ".$title;
    $creator = ($i%4==3)?$adminId:$trainerIds[$i%3];
    $tipStmt->execute([$title,$content,$creator]);
}

// -------------------
// 4. NUTRITION ADVICE
// -------------------
$categories = ['general','macros','vitamins','hydration','diet_plans','supplements'];
$nutritionStmt = $pdo->prepare("INSERT INTO nutrition_advice (title,content,category,created_by) VALUES (?,?,?,?)");
for($i=0;$i<15;$i++){
    $title = "Nutrition ".($i+1);
    $content = "Content for ".$title;
    $category = $categories[$i % count($categories)];
    $creator = ($i%4==3)?$adminId:$trainerIds[$i%3];
    $nutritionStmt->execute([$title,$content,$category,$creator]);
}

// -------------------
// 5. ROUTINE FEEDBACK & TRAINER REPLIES
// -------------------
$feedbackStmt = $pdo->prepare("INSERT INTO routine_feedback (routine_id,user_id,comment) VALUES (?,?,?)");
$replyStmt = $pdo->prepare("INSERT INTO trainer_replies (feedback_id,trainer_id,reply) VALUES (?,?,?)");

foreach($routineIds as $rid){
    $user = $userIdsList[array_rand($userIdsList)];
    $comment = "Feedback from user $user on routine $rid";
    $feedbackStmt->execute([$rid,$user,$comment]);
    $feedbackId = $pdo->lastInsertId();

    // Trainer reply
    $trainer = $trainerIds[array_rand($trainerIds)];
    $replyStmt->execute([$feedbackId,$trainer,"Reply from trainer $trainer"]);
}

// -------------------
// 6. TRAINER SUGGESTIONS
// -------------------
$suggestStmt = $pdo->prepare("INSERT INTO trainer_suggestions (user_id,trainer_id,suggestion) VALUES (?,?,?)");
foreach($userIdsList as $uid){
    $trainer = $trainerIds[array_rand($trainerIds)];
    $suggestStmt->execute([$uid,$trainer,"Suggestion for user $uid from trainer $trainer"]);
}

// -------------------
// 7. ACTIVITY LOGS
// -------------------
$activityStmt = $pdo->prepare("INSERT INTO activity_logs (user_id,type,content,value,date) VALUES (?,?,?,?,?)");
$types = ['workout','meal','water'];
foreach($userIdsList as $uid){
    foreach($types as $i=>$type){
        $content = ucfirst($type)." content for user $uid";
        $value = rand(1,5)*10;
        $date = date('Y-m-d',strtotime("-$i days"));
        $activityStmt->execute([$uid,$type,$content,$value,$date]);
    }
}

echo "All data seeded successfully!\n";

// -------------------
// 8. EMAIL/PASSWORD REFERENCE
// -------------------
echo "\nEmail / Password reference:\n";
echo "ADMIN\n";
echo "admin@gmail.com : Admin1234\n";
echo "TRAINERS\n";
foreach(range(1,3) as $i){
    echo "trainer$i@gmail.com : Trainer123\n";
}
echo "USERS\n";
foreach(range(1,11) as $i){
    echo "user$i@gmail.com : User123\n";
}