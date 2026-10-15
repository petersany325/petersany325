# Deploy: Grants + Post Upload Menu

## 1) Database
Run (replace `{TABLE_PREFIX}` if any):

`forum-vbdlmanager/live/core/packages/vbdlmanager/db/mysql/upgrade_grants_2026.sql`

## 2) Files to copy onto forum root
From `forum-vbdlmanager/live/`:

- `core/packages/vbdlmanager/library/Acl.php`
- `core/packages/vbdlmanager/library/Repository.php`
- `core/packages/vbdlmanager/library/DownloadService.php`
- `core/packages/vbdlmanager/db/mysql/upgrade_grants_2026.sql`
- `core/admincp/vbdlmanager/_init.php`
- `core/admincp/vbdlmanager/pages/grants.php`
- `core/admincp/vbdlmanager/pages/categories.php`
- `core/packages/vbdlportal/hooks.php`
- `vbdlmanager/upload_api.php`
- `vbdlmanager/assets/post-upload.js`
- `vbdlmanager/assets/post-upload.css`

## 3) Admin checklist
1. AdminCP → Download Manager → Categories → set VIP category to **grant_required**
2. Access Grants → grant user/group Download (and Upload if needed)
3. Create new topic → confirm specialized upload menu appears
4. Guest/VIP without grant cannot download VIP files
5. Granted user can download

## 4) Settings flags
- `post_upload_enabled=1`
- `acl_vip_needs_grant=1`
- `acl_paid_needs_grant=1`
