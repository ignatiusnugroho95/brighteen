<?php
require __DIR__ . '/../bootstrap/app.php';

$db = db();
$columns = [
    'first_name' => 'VARCHAR(100) NULL AFTER name',
    'last_name' => 'VARCHAR(100) NULL AFTER first_name',
    'author_name' => 'VARCHAR(150) NULL AFTER last_name',
    'whatsapp' => 'VARCHAR(50) NULL AFTER bio',
    'birth_date' => 'DATE NULL AFTER whatsapp',
    'institution' => 'VARCHAR(255) NULL AFTER birth_date',
    'grade_or_position' => 'VARCHAR(100) NULL AFTER institution'
];

foreach ($columns as $col => $def) {
    $check = $db->query("SHOW COLUMNS FROM users LIKE '{$col}'")->fetch();
    if (!$check) {
        $db->exec("ALTER TABLE users ADD COLUMN {$col} {$def}");
        echo "Added column: {$col}\n";
    } else {
        echo "Column already exists: {$col}\n";
    }
}

// Populate existing users with sensible profile defaults based on name and role
$users = $db->query("SELECT id, name, role_id FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    $parts = explode(' ', trim($u['name']), 2);
    $firstName = $parts[0] ?? '';
    $lastName = $parts[1] ?? '';
    
    // Check if user already has first_name set
    $cur = $db->query("SELECT first_name, institution, grade_or_position FROM users WHERE id = " . (int)$u['id'])->fetch(PDO::FETCH_ASSOC);
    if (empty($cur['first_name'])) {
        $inst = 'Brighton JHS Depok';
        $grade = '8A';
        if ($u['role_id'] == 1 || $u['role_id'] == 2) {
            $grade = 'Teacher';
        } elseif ($u['role_id'] == 3) {
            $grade = '9A';
        } elseif ($u['role_id'] == 4) {
            $grade = '8B';
        }
        
        $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, institution = ?, grade_or_position = ?, birth_date = '2009-08-15' WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $inst, $grade, $u['id']]);
        echo "Populated user ID {$u['id']} ({$u['name']})\n";
    }
}

// Also sync if Jordan Taylor already had application data
$app = $db->query("SELECT * FROM contributor_applications WHERE user_id = 5 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($app) {
    $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, author_name = ?, whatsapp = ?, birth_date = ?, institution = ?, grade_or_position = ? WHERE id = 5");
    $stmt->execute([
        $app['first_name'],
        $app['last_name'],
        $app['author_name'],
        $app['whatsapp'],
        $app['birth_date'],
        $app['institution'],
        $app['grade_or_position']
    ]);
    echo "Synced Jordan Taylor from application\n";
}

echo "Database migration complete!\n";
