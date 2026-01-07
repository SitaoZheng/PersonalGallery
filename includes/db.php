<?php
// 数据库连接文件

require_once 'config.php';

/**
 * 获取数据库连接实例
 * @return PDO 数据库连接实例
 */
function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $db = new PDO('sqlite:' . DB_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('数据库连接失败: ' . $e->getMessage());
        }
    }
    
    return $db;
}

/**
 * 初始化数据库表
 */
function initDatabase() {
    $db = getDB();
    
    // 创建用户表
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        is_admin INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 创建图库表
    $db->exec("CREATE TABLE IF NOT EXISTS galleries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 创建分类表
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        gallery_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        FOREIGN KEY (gallery_id) REFERENCES galleries(id) ON DELETE CASCADE
    )");
    
    // 创建标签表
    $db->exec("CREATE TABLE IF NOT EXISTS tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE
    )");
    
    // 创建图片表
    $db->exec("CREATE TABLE IF NOT EXISTS images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        gallery_id INTEGER NOT NULL,
        category_id INTEGER,
        name TEXT NOT NULL,
        filename TEXT NOT NULL,
        path TEXT NOT NULL,
        size INTEGER NOT NULL,
        width INTEGER NOT NULL,
        height INTEGER NOT NULL,
        format TEXT NOT NULL,
        orientation TEXT NOT NULL,
        tags TEXT,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (gallery_id) REFERENCES galleries(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )");
    
    // 创建权限表
    $db->exec("CREATE TABLE IF NOT EXISTS permissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        gallery_id INTEGER NOT NULL,
        gallery_access INTEGER DEFAULT 0,
        gallery_manage INTEGER DEFAULT 0,
        category_manage INTEGER DEFAULT 0,
        image_manage INTEGER DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (gallery_id) REFERENCES galleries(id) ON DELETE CASCADE
    )");
    
    // 创建默认超管账号
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 1");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        $password = password_hash('admin', PASSWORD_HASH_ALGO);
        $stmt = $db->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, 1)");
        $stmt->execute(['admin', $password]);
    }
    
    // 创建默认图库
    $stmt = $db->prepare("SELECT COUNT(*) FROM galleries");
    $stmt->execute();
    $galleryCount = $stmt->fetchColumn();
    
    if ($galleryCount == 0) {
        $stmt = $db->prepare("INSERT INTO galleries (name) VALUES (?)");
        $stmt->execute(['默认图库']);
    }
}
