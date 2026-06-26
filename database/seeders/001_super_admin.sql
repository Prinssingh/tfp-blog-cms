-- Seed: Super Admin user
-- Password is: Admin@1234  (change immediately after first login)
-- Hash generated with: password_hash('Admin@1234', PASSWORD_BCRYPT)

INSERT IGNORE INTO `users`
    (`website_id`, `role_id`, `name`, `slug`, `email`, `password`, `status`, `created_at`, `updated_at`)
VALUES (
    NULL,
    (SELECT `id` FROM `roles` WHERE `slug` = 'super_admin' LIMIT 1),
    'Super Admin',
    'super-admin',
    'admin@tfptechnologies.in',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'active',
    NOW(),
    NOW()
);
