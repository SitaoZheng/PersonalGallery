<?php
// 分类列表API端点

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// 检查是否登录
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => '请先登录'
    ]);
    exit;
}

// 检查请求方式
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => '只允许GET请求'
    ]);
    exit;
}

// 获取图库ID
$gallery_id = isset($_GET['gallery_id']) ? intval($_GET['gallery_id']) : 0;
if (!$gallery_id) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => '请提供图库ID'
    ]);
    exit;
}

// 检查用户是否有访问该图库的权限
if (!hasGalleryAccess($gallery_id)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => '您没有权限访问该图库的分类'
    ]);
    exit;
}

// 获取分类列表
$categories = getGalleryCategories($gallery_id);

// 返回分类列表
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'categories' => $categories
]);
exit;
