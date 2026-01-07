<?php
// 通用函数文件

require_once 'config.php';
require_once 'db.php';

/**
 * 生成唯一文件名
 * @param string $filename 原始文件名
 * @return string 唯一文件名
 */
function generateUniqueFilename($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $ext;
}

/**
 * 获取文件大小的可读格式，始终返回MB单位
 * @param int $bytes 字节大小
 * @return string 可读格式的大小（MB）
 */
function formatFileSize($bytes) {
    return round($bytes / 1048576, 2) . ' MB';
}

/**
 * 获取图片方向
 * @param int $width 宽度
 * @param int $height 高度
 * @return string 方向 (portrait/landscape/square)
 */
function getImageOrientation($width, $height) {
    if ($width > $height) {
        return 'landscape';
    } elseif ($width < $height) {
        return 'portrait';
    } else {
        return 'square';
    }
}

/**
 * 格式化日期时间
 * @param string $datetime 日期时间字符串
 * @return string 格式化后的日期时间
 */
function formatDateTime($datetime) {
    return date('Y-m-d H:i:s', strtotime($datetime));
}

/**
 * 获取图库下的所有图片
 * @param int $gallery_id 图库ID
 * @param array $filters 筛选条件
 * @param string $sort 排序方式
 * @param int $page 页码
 * @return array 图片列表和总数
 */
function getGalleryImages($gallery_id, $filters = [], $sort = 'uploaded_at DESC', $page = 1) {
    $db = getDB();
    
    $where = ['gallery_id = ?'];
    $params = [$gallery_id];
    
    // 分类筛选
    if (!empty($filters['category']) && $filters['category'] !== 'all') {
        if (is_array($filters['category']) && !empty($filters['category'])) {
            $placeholders = implode(',', array_fill(0, count($filters['category']), '?'));
            $where[] = "category_id IN ($placeholders)";
            $params = array_merge($params, $filters['category']);
        } else {
            $where[] = 'category_id = ?';
            $params[] = $filters['category'];
        }
    }
    
    // 标签筛选
    if (!empty($filters['tags'])) {
        foreach ($filters['tags'] as $tag) {
            $where[] = 'tags LIKE ?';
            $params[] = '%' . $tag . '%';
        }
    }
    
    // 格式筛选
    if (!empty($filters['format']) && $filters['format'] !== 'all') {
        if (is_array($filters['format']) && !empty($filters['format'])) {
            $placeholders = implode(',', array_fill(0, count($filters['format']), '?'));
            $where[] = "format IN ($placeholders)";
            $params = array_merge($params, $filters['format']);
        } else {
            $where[] = 'format = ?';
            $params[] = $filters['format'];
        }
    }
    
    // 版型筛选
    if (!empty($filters['orientation']) && $filters['orientation'] !== 'all') {
        if (is_array($filters['orientation']) && !empty($filters['orientation'])) {
            $placeholders = implode(',', array_fill(0, count($filters['orientation']), '?'));
            $where[] = "orientation IN ($placeholders)";
            $params = array_merge($params, $filters['orientation']);
        } else {
            $where[] = 'orientation = ?';
            $params[] = $filters['orientation'];
        }
    }
    
    $whereClause = implode(' AND ', $where);
    
    // 获取总数
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM images WHERE $whereClause");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // 分页
    $offset = ($page - 1) * ITEMS_PER_PAGE;
    
    // 获取图片列表
    $stmt = $db->prepare("SELECT * FROM images WHERE $whereClause ORDER BY $sort LIMIT ? OFFSET ?");
    $params[] = ITEMS_PER_PAGE;
    $params[] = $offset;
    $stmt->execute($params);
    $images = $stmt->fetchAll();
    
    return [
        'images' => $images,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / ITEMS_PER_PAGE)
    ];
}

/**
 * 获取图库下的所有分类
 * @param int $gallery_id 图库ID
 * @return array 分类列表
 */
function getGalleryCategories($gallery_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM categories WHERE gallery_id = ? ORDER BY name");
    $stmt->execute([$gallery_id]);
    return $stmt->fetchAll();
}

/**
 * 获取所有标签
 * @return array 标签列表
 */
function getAllTags() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM tags ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * 处理标签字符串
 * @param string $tags_str 标签字符串
 * @return array 标签数组
 */
function processTags($tags_str) {
    // 替换中文逗号为英文逗号
    $tags_str = str_replace('，', ',', $tags_str);
    // 分割标签
    $tags = explode(',', $tags_str);
    // 去除空格和空标签
    $tags = array_map('trim', $tags);
    $tags = array_filter($tags);
    // 去重
    $tags = array_unique($tags);
    return array_values($tags);
}

/**
 * 保存标签到数据库
 * @param array $tags 标签数组
 */
function saveTags($tags) {
    $db = getDB();
    foreach ($tags as $tag) {
        $stmt = $db->prepare("INSERT OR IGNORE INTO tags (name) VALUES (?)");
        $stmt->execute([$tag]);
    }
}

/**
 * 格式化标签数组为字符串
 * @param array $tags 标签数组
 * @return string 标签字符串
 */
function formatTags($tags) {
    return implode(', ', $tags);
}

/**
 * 获取版型文本
 * @param string $orientation 方向值 (portrait/landscape/square)
 * @return string 版型中文文本
 */
function getOrientationText($orientation) {
    $orientationMap = [
        'landscape' => '横版',
        'portrait' => '竖版',
        'square' => '方版'
    ];
    return isset($orientationMap[$orientation]) ? $orientationMap[$orientation] : $orientation;
}

/**
 * 生成分页HTML
 * @param int $current_page 当前页码
 * @param int $total_pages 总页数
 * @param string $url URL模板
 * @return string 分页HTML
 */
function generatePagination($current_page, $total_pages, $url) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<div class="pagination">';
    
    // 上一页
    if ($current_page > 1) {
        $prev_url = str_replace('{page}', $current_page - 1, $url);
        $pagination .= '<a href="' . $prev_url . '" class="prev">上一页</a>';
    }
    
    // 页码
    for ($i = 1; $i <= $total_pages; $i++) {
        $page_url = str_replace('{page}', $i, $url);
        if ($i === $current_page) {
            $pagination .= '<span class="current">' . $i . '</span>';
        } else {
            $pagination .= '<a href="' . $page_url . '">' . $i . '</a>';
        }
    }
    
    // 下一页
    if ($current_page < $total_pages) {
        $next_url = str_replace('{page}', $current_page + 1, $url);
        $pagination .= '<a href="' . $next_url . '" class="next">下一页</a>';
    }
    
    $pagination .= '</div>';
    
    return $pagination;
}

/**
 * 获取文件扩展名
 * @param string $filename 文件名
 * @return string 扩展名
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * 检查文件是否为允许的图片格式
 * @param string $filename 文件名
 * @return bool 是否为允许的格式
 */
function isAllowedImageFormat($filename) {
    $ext = getFileExtension($filename);
    return in_array($ext, ALLOWED_IMAGE_TYPES);
}

/**
 * 创建图库目录
 * @param string $gallery_name 图库名称
 * @return string 目录路径
 */
function createGalleryDirectory($gallery_name) {
    $dir = IMAGE_DIR . $gallery_name . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * 删除图库目录
 * @param string $gallery_name 图库名称
 */
function deleteGalleryDirectory($gallery_name) {
    $dir = IMAGE_DIR . $gallery_name . '/';
    if (is_dir($dir)) {
        // 删除目录下的所有文件
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                unlink($dir . $file);
            }
        }
        // 删除目录
        rmdir($dir);
    }
}

/**
 * 重命名图库目录
 * @param string $old_name 旧名称
 * @param string $new_name 新名称
 */
function renameGalleryDirectory($old_name, $new_name) {
    $old_dir = IMAGE_DIR . $old_name . '/';
    $new_dir = IMAGE_DIR . $new_name . '/';
    if (is_dir($old_dir) && !is_dir($new_dir)) {
        rename($old_dir, $new_dir);
    }
}
