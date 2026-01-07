<?php
/**
 * 数据库重置脚本
 * 用于重置数据库并初始化，保留admin超管账号
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查是否是命令行运行
$is_cli = php_sapi_name() === 'cli';

// 只有管理员或命令行可以运行此脚本
if (!$is_cli) {
    // 检查是否登录且是管理员
    require_once 'includes/auth.php';
    session_start();
    
    if (!isLoggedIn() || !isAdmin()) {
        die('您没有权限执行此操作');
    }
}

// 输出函数
function output($message) {
    global $is_cli;
    if ($is_cli) {
        echo $message . PHP_EOL;
    } else {
        echo $message . '<br>';
    }
    flush();
}

// 开始重置
output('开始重置数据库...');

try {
    // 获取数据库连接
    $db = getDB();
    
    // 1. 备份当前数据库（可选）
    output('备份当前数据库...');
    $backup_path = DB_PATH . '.' . date('YmdHis') . '.bak';
    if (copy(DB_PATH, $backup_path)) {
        output('数据库备份成功：' . $backup_path);
    } else {
        output('警告：数据库备份失败');
    }
    
    // 2. 开始事务
    $db->beginTransaction();
    
    // 3. 获取所有表名
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 4. 禁用外键约束
    $db->exec("PRAGMA foreign_keys = OFF");
    
    // 5. 清空所有表
    foreach ($tables as $table) {
        if ($table !== 'sqlite_sequence') { // 跳过SQLite内部表
            output('清空表：' . $table);
            $db->exec("DELETE FROM $table");
        }
    }
    
    // 6. 重置自增ID
    $db->exec("DELETE FROM sqlite_sequence");
    
    // 7. 重新启用外键约束
    $db->exec("PRAGMA foreign_keys = ON");
    
    // 8. 重新初始化数据库表和默认数据
    output('重新初始化数据库表...');
    initDatabase();
    
    // 9. 确保admin账号存在且密码正确
    output('检查并更新admin账号...');
    $password_hash = password_hash('admin', PASSWORD_HASH_ALGO);
    $stmt = $db->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetch()) {
        // 更新现有admin账号的密码和权限
        $stmt = $db->prepare("UPDATE users SET password = ?, is_admin = 1 WHERE username = 'admin'");
        $stmt->execute([$password_hash]);
        output('已更新admin账号');
    } else {
        // 创建新的admin账号
        $stmt = $db->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, 1)");
        $stmt->execute(['admin', $password_hash]);
        output('已创建admin账号');
    }
    
    // 10. 提交事务
    $db->commit();
    
    // 11. 输出成功信息
    output('数据库重置完成！');
    output('admin账号已重置，用户名：admin，密码：admin');
    
} catch (Exception $e) {
    // 回滚事务
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    output('错误：' . $e->getMessage());
    exit(1);
}

// 如果是命令行运行，结束执行
if ($is_cli) {
    exit(0);
}

// 如果是Web访问，显示返回链接
echo '<br><a href="/admin/index.php">返回后台管理</a>';
