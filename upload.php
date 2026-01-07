<?php
// 处理图片上传请求

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/upload.php';

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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => '只允许POST请求'
    ]);
    exit;
}

// 获取请求参数
$action = isset($_POST['action']) ? $_POST['action'] : '';

// 根据不同的操作执行不同的逻辑
switch ($action) {
    case 'upload':
        // 检查是否有上传文件
        if (!isset($_FILES['file'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => '请选择要上传的图片'
            ]);
            exit;
        }
        
        // 检查图库ID
        $gallery_id = isset($_POST['gallery_id']) ? intval($_POST['gallery_id']) : 0;
        if (!$gallery_id) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => '请选择图库'
            ]);
            exit;
        }
        
        // 检查用户是否有上传权限
        if (!hasImageManage($gallery_id)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => '您没有权限上传图片到该图库'
            ]);
            exit;
        }
        
        // 构建图片数据
        $image_data = [
            'gallery_id' => $gallery_id,
            'category_id' => isset($_POST['category_id']) ? intval($_POST['category_id']) : null,
            'name' => $_POST['name'],
            'tags' => $_POST['tags']
        ];
        
        // 处理文件上传
        $result = handleFileUpload($_FILES['file'], $image_data);
        
        // 返回响应
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
        
    case 'update':
        // 检查图片ID
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
        if (!$image_id) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => '请选择要编辑的图片'
            ]);
            exit;
        }
        
        // 获取图片所属图库ID
        $db = getDB();
        $stmt = $db->prepare("SELECT gallery_id FROM images WHERE id = ?");
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
        
        // 检查用户是否有图片管理权限
        if (!hasImageManage($image['gallery_id'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => '您没有权限编辑该图片'
            ]);
            exit;
        }
        
        // 构建图片数据
        $image_data = [
            'name' => $_POST['name'],
            'category_id' => isset($_POST['category_id']) ? intval($_POST['category_id']) : null,
            'tags' => $_POST['tags']
        ];
        
        // 更新图片信息
        $result = updateImage($image_id, $image_data);
        
        // 返回响应
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result,
            'message' => $result ? '图片信息更新成功' : '图片信息更新失败'
        ]);
        exit;
        
    case 'delete':
        // 检查图片ID
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
        if (!$image_id) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => '请选择要删除的图片'
            ]);
            exit;
        }
        
        // 获取图片所属图库ID
        $db = getDB();
        $stmt = $db->prepare("SELECT gallery_id FROM images WHERE id = ?");
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
        
        // 检查用户是否有图片管理权限
        if (!hasImageManage($image['gallery_id'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => '您没有权限删除该图片'
            ]);
            exit;
        }
        
        // 删除图片
        $result = deleteImage($image_id);
        
        // 返回响应
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result,
            'message' => $result ? '图片删除成功' : '图片删除失败'
        ]);
        exit;
        
    default:
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => '无效的操作'
        ]);
        exit;
}
