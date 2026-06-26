-- Seed: Default role permissions
-- website_admin gets everything except website.manage
-- editor gets post editing and category/tag management
-- writer gets post creation and media upload

-- Website Admin
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'website_admin'
  AND p.slug IN (
    'post.create', 'post.edit', 'post.publish', 'post.delete',
    'category.manage', 'tag.manage',
    'media.upload', 'media.delete',
    'user.manage', 'settings.manage',
    'seo.manage', 'redirect.manage', 'audit.view'
  );

-- Editor
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'editor'
  AND p.slug IN (
    'post.edit', 'post.publish',
    'category.manage', 'tag.manage',
    'media.upload', 'seo.manage'
  );

-- Writer
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'writer'
  AND p.slug IN (
    'post.create', 'post.edit',
    'media.upload'
  );
