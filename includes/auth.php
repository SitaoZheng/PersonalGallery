<?php
// 认证功能文件

require_once 'config.php';
require_once 'db.php';

// 启动会话
session_start();

/**
 * 检查用户是否已登录
 * @return bool 是否已登录
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && time() < $_SESSION['expires_at'];
}

/**
 * 检查用户是否为管理员
 * @return bool 是否为管理员
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1;
}

/**
 * 登录功能
 * @param string $username 用户名
 * @param string $password 密码
 * @return bool 登录是否成功
 */
function login($username, $password) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['expires_at'] = time() + SESSION_TIMEOUT;
        return true;
    }
    
    return false;
}

/**
 * 登出功能
 */
function logout() {
    session_destroy();
    header('Location: /login.php');
    exit;
}

// 处理登出请求
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}

/**
 * 检查用户是否有访问指定图库的权限
 * @param int $gallery_id 图库ID
 * @return bool 是否有访问权限
 */
function hasGalleryAccess($gallery_id) {
    if (isAdmin()) {
        return true;
    }
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT gallery_access FROM permissions WHERE user_id = ? AND gallery_id = ?");
    $stmt->execute([$_SESSION['user_id'], $gallery_id]);
    $permission = $stmt->fetch();
    
    return $permission && $permission['gallery_access'] === 1;
}

/**
 * 检查用户是否有管理指定图库的权限
 * @param int $gallery_id 图库ID
 * @return bool 是否有管理权限
 */
function hasGalleryManage($gallery_id) {
    if (isAdmin()) {
        return true;
    }
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT gallery_manage FROM permissions WHERE user_id = ? AND gallery_id = ?");
    $stmt->execute([$_SESSION['user_id'], $gallery_id]);
    $permission = $stmt->fetch();
    
    return $permission && $permission['gallery_manage'] === 1;
}

/**
 * 检查用户是否有管理指定图库分类的权限
 * @param int $gallery_id 图库ID
 * @return bool 是否有分类管理权限
 */
function hasCategoryManage($gallery_id) {
    if (isAdmin()) {
        return true;
    }
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT category_manage FROM permissions WHERE user_id = ? AND gallery_id = ?");
    $stmt->execute([$_SESSION['user_id'], $gallery_id]);
    $permission = $stmt->fetch();
    
    return $permission && $permission['category_manage'] === 1;
}

/**
 * 检查用户是否有管理指定图库图片的权限
 * @param int $gallery_id 图库ID
 * @return bool 是否有图片管理权限
 */
function hasImageManage($gallery_id) {
    if (isAdmin()) {
        return true;
    }
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT image_manage FROM permissions WHERE user_id = ? AND gallery_id = ?");
    $stmt->execute([$_SESSION['user_id'], $gallery_id]);
    $permission = $stmt->fetch();
    
    return $permission && $permission['image_manage'] === 1;
}

/**
 * 获取用户可访问的图库列表
 * @return array 图库列表
 */
function getUserGalleries() {
    $db = getDB();
    
    if (isAdmin()) {
        $stmt = $db->prepare("SELECT * FROM galleries ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    if (!isLoggedIn()) {
        return [];
    }
    
    $stmt = $db->prepare("SELECT g.* FROM galleries g 
                          JOIN permissions p ON g.id = p.gallery_id 
                          WHERE p.user_id = ? AND p.gallery_access = 1 
                          ORDER BY g.name");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchAll();
}

/**
 * 检查用户是否有管理任何图库的权限
 * @return bool 是否有图库管理权限
 */
function hasAnyGalleryManage() {
    if (isAdmin()) {
        return true;
    }
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = getDB();
    // 尝试直接查询权限表，不使用COUNT(*)
    $stmt = $db->prepare("SELECT gallery_id FROM permissions WHERE user_id = ? AND gallery_manage = 1 LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    return $result !== false;
}

/**
 * 检查用户是否有管理任何分类的权限
 * @return bool 是否有分类管理权限
 */
function hasAnyCategoryManage() {
    if (isAdmin()) {
        return true;
    }
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = getDB();
    // 尝试直接查询权限表，不使用COUNT(*)
    $stmt = $db->prepare("SELECT gallery_id FROM permissions WHERE user_id = ? AND category_manage = 1 LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    return $result !== false;
}

/**
 * 检查用户是否有管理任何图片的权限
 * @return bool 是否有图片管理权限
 */
function hasAnyImageManage() {
    if (isAdmin()) {
        return true;
    }
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM permissions WHERE user_id = ? AND image_manage = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchColumn() > 0;
}

/**
 * 权限验证中间件
 * @param string $required_level 所需权限级别 ('guest', 'user', 'admin')
 */
function checkPermission($required_level) {
    if ($required_level === 'admin' && !isAdmin()) {
        header('Location: /login.php');
        exit;
    }
    
    if ($required_level === 'user' && !isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}
