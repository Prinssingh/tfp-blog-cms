<?php

declare(strict_types=1);

// Run via SSH: php database/seeders/seed_super_admin.php

define('BASE_PATH', dirname(__DIR__, 2));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$email    = 'admin@tfptechnologies.in';
$password = 'Admin@1234';
$hash     = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $pdo->prepare(
    'INSERT INTO users (website_id, role_id, name, slug, email, password, status, created_at, updated_at)
     SELECT NULL, r.id, "Super Admin", "super-admin", ?, ?, "active", NOW(), NOW()
     FROM roles r WHERE r.slug = "super_admin" LIMIT 1
     ON DUPLICATE KEY UPDATE password = VALUES(password)'
);

$stmt->execute([$email, $hash]);

echo "Super admin seeded. Email: {$email} | Password: {$password}\n";
echo "Change your password after first login!\n";
