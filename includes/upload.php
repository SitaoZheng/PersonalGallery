<?php
// 文件上传处理文件

require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

/**
 * 处理文件上传
 * @param array $file 文件数组
 * @param array $image_data 图片信息
 * @return array 上传结果
 */
function handleFileUpload($file, $image_data) {
    // 检查文件是否有错误
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => getUploadErrorMessage($file['error'])
        ];
    }
    
    // 检查文件大小
    if ($file['size'] > MAX_FILE_SIZE) {
        return [
            'success' => false,
            'message' => '文件大小超过限制，最大允许 ' . formatFileSize(MAX_FILE_SIZE)
        ];
    }
    
    // 检查文件类型
    $filename = $file['name'];
    if (!isAllowedImageFormat($filename)) {
        return [
            'success' => false,
            'message' => '不允许的文件格式，只允许 ' . implode(', ', ALLOWED_IMAGE_TYPES)
        ];
    }
    
    // 获取图库信息
    $db = getDB();
    $stmt = $db->prepare("SELECT name FROM galleries WHERE id = ?");
    $stmt->execute([$image_data['gallery_id']]);
    $gallery = $stmt->fetch();
    
    if (!$gallery) {
        return [
            'success' => false,
            'message' => '图库不存在'
        ];
    }
    
    // 创建图库目录
    $gallery_dir = createGalleryDirectory($gallery['name']);
    
    // 生成唯一文件名
    $unique_filename = generateUniqueFilename($filename);
    $file_path = $gallery_dir . $unique_filename;
    
    // 移动文件
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        return [
            'success' => false,
            'message' => '文件上传失败'
        ];
    }
    
    // 获取图片信息
    list($width, $height) = getimagesize($file_path);
    $format = getFileExtension($filename);
    $orientation = getImageOrientation($width, $height);
    $size = filesize($file_path);
    
    // 处理标签
    $tags = processTags($image_data['tags']);
    saveTags($tags);
    $tags_str = formatTags($tags);
    
    // 保存图片信息到数据库
    $stmt = $db->prepare("INSERT INTO images (gallery_id, category_id, name, filename, path, size, width, height, format, orientation, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $image_data['gallery_id'],
        $image_data['category_id'],
        $image_data['name'],
        $unique_filename,
        $gallery['name'] . '/' . $unique_filename,
        $size,
        $width,
        $height,
        $format,
        $orientation,
        $tags_str
    ]);
    
    return [
        'success' => true,
        'message' => '文件上传成功',
        'image_id' => $db->lastInsertId()
    ];
}

/**
 * 获取上传错误信息
 * @param int $error_code 错误代码
 * @return string 错误信息
 */
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return '文件大小超过了php.ini中的限制';
        case UPLOAD_ERR_FORM_SIZE:
            return '文件大小超过了表单中的限制';
        case UPLOAD_ERR_PARTIAL:
            return '文件只上传了一部分';
        case UPLOAD_ERR_NO_FILE:
            return '没有选择文件';
        case UPLOAD_ERR_NO_TMP_DIR:
            return '缺少临时文件夹';
        case UPLOAD_ERR_CANT_WRITE:
            return '文件写入失败';
        case UPLOAD_ERR_EXTENSION:
            return '文件上传被扩展程序中断';
        default:
            return '未知错误';
    }
}

/**
 * 删除图片
 * @param int $image_id 图片ID
 * @return bool 删除是否成功
 */
function deleteImage($image_id) {
    $db = getDB();
    
    // 获取图片信息
    $stmt = $db->prepare("SELECT path FROM images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch();
    
    if (!$image) {
        return false;
    }
    
    // 删除文件
    $file_path = IMAGE_DIR . $image['path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // 删除数据库记录
    $stmt = $db->prepare("DELETE FROM images WHERE id = ?");
    $stmt->execute([$image_id]);
    
    return true;
}

/**
 * 更新图片信息
 * @param int $image_id 图片ID
 * @param array $image_data 图片信息
 * @return bool 更新是否成功
 */
function updateImage($image_id, $image_data) {
    $db = getDB();
    
    // 处理标签
    $tags = processTags($image_data['tags']);
    saveTags($tags);
    $tags_str = formatTags($tags);
    
    // 更新数据库记录
    $stmt = $db->prepare("UPDATE images SET name = ?, category_id = ?, tags = ? WHERE id = ?");
    $stmt->execute([
        $image_data['name'],
        $image_data['category_id'],
        $tags_str,
        $image_id
    ]);
    
    return $stmt->rowCount() > 0;
}
