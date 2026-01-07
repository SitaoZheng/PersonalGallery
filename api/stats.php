<?php
// 统计数据API

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

// 检查是否登录并为管理员
if (!isLoggedIn() || !isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => '无权限访问']);
    exit;
}

try {
    $db = getDB();
    
    // 获取统计数据
    $stats = [
        // 总用户数
        'users' => $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        // 总图库数
        'galleries' => $db->query('SELECT COUNT(*) FROM galleries')->fetchColumn(),
        // 总图片数
        'images' => $db->query('SELECT COUNT(*) FROM images')->fetchColumn(),
        // 总分类数
        'categories' => $db->query('SELECT COUNT(*) FROM categories')->fetchColumn()
    ];
    
    // 返回JSON格式数据
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            ['label' => '总用户数', 'value' => $stats['users']],
            ['label' => '总图库数', 'value' => $stats['galleries']],
            ['label' => '总图片数', 'value' => $stats['images']],
            ['label' => '总分类数', 'value' => $stats['categories']]
        ]
    ]);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => '获取统计数据失败: ' . $e->getMessage()]);
}
