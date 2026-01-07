<?php
// 图片详情API端点

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

// 获取图片ID
$image_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$image_id) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => '请提供图片ID'
    ]);
    exit;
}

// 获取图片详情
$db = getDB();
$stmt = $db->prepare("SELECT * FROM images WHERE id = ?");
$stmt->execute([$image_id]);
$image = $stmt->fetch();

if (!$image) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => '图片不存在'
    ]);
    exit;
}

// 检查用户是否有访问该图片所属图库的权限
if (!hasGalleryAccess($image['gallery_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => '您没有权限访问该图片'
    ]);
    exit;
}

// 处理标签
$image['tags'] = !empty($image['tags']) ? explode(', ', $image['tags']) : [];

// 格式化文件大小
$image['size_text'] = formatFileSize($image['size']);

// 格式化上传时间
$image['uploaded_at_text'] = formatDateTime($image['uploaded_at']);

// 获取版型文本
$image['orientation_text'] = getOrientationText($image['orientation']);

// 返回图片详情
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'image' => $image
]);
exit;
