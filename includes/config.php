<?php
// 配置文件

// 数据库配置
define('DB_PATH', dirname(__DIR__) . '/database.db');

// 图片存储配置
define('IMAGE_DIR', dirname(__DIR__) . '/images/');
define('IMAGE_URL', '/images/');

// 允许的图片格式
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// 最大文件大小 (100MB)
define('MAX_FILE_SIZE', 100 * 1024 * 1024);

// 分页配置
define('ITEMS_PER_PAGE', 20);

// 会话配置
define('SESSION_TIMEOUT', 3600); // 1小时

// 网站配置
define('SITE_NAME', '个人图库');
define('ADMIN_EMAIL', 'admin@example.com');

// 密码哈希算法
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);

// 调试模式
define('DEBUG', true);
