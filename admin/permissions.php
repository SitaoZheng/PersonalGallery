<?php
// 权限管理页面

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/init.php';

// 检查是否登录
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// 检查是否为管理员
if (!isAdmin()) {
    header('Location: /index.php');
    exit;
}

// 获取数据库连接
$db = getDB();

// 获取所有用户
$stmt = $db->query("SELECT id, username, is_admin FROM users WHERE is_admin = 0 ORDER BY id");
$users = $stmt->fetchAll();

// 获取所有图库
$stmt = $db->query("SELECT id, name FROM galleries ORDER BY name");
$galleries = $stmt->fetchAll();

// 处理权限更新请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $user_id = $_POST['user_id'];
    
    // 获取所有图库ID
    $stmt = $db->query("SELECT id FROM galleries");
    $all_galleries = $stmt->fetchAll();
    
    // 删除旧权限
    $stmt = $db->prepare("DELETE FROM permissions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // 添加新权限
    foreach ($all_galleries as $gallery) {
        $gallery_id = $gallery['id'];
        $gallery_access = isset($_POST["gallery_access_{$gallery_id}"]) ? 1 : 0;
        
        // 只有当gallery_access为1时，才保存该图库的权限
        if ($gallery_access === 1) {
            $gallery_manage = isset($_POST["gallery_manage_{$gallery_id}"]) ? 1 : 0;
            $category_manage = isset($_POST["category_manage_{$gallery_id}"]) ? 1 : 0;
            $image_manage = isset($_POST["image_manage_{$gallery_id}"]) ? 1 : 0;
            
            $stmt = $db->prepare("INSERT INTO permissions (user_id, gallery_id, gallery_access, gallery_manage, category_manage, image_manage) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $gallery_id, $gallery_access, $gallery_manage, $category_manage, $image_manage]);
        }
    }
    
    $success = '权限更新成功';
}

// 获取当前选中用户的权限
$selected_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : (empty($users) ? 0 : $users[0]['id']);
$stmt = $db->prepare("SELECT gallery_id, gallery_access, gallery_manage, category_manage, image_manage FROM permissions WHERE user_id = ?");
$stmt->execute([$selected_user_id]);
$user_permissions = $stmt->fetchAll();

// 转换权限为数组，便于模板使用
$permissions = [];
foreach ($user_permissions as $perm) {
    $permissions[$perm['gallery_id']] = [
        'gallery_access' => $perm['gallery_access'],
        'gallery_manage' => $perm['gallery_manage'],
        'category_manage' => $perm['category_manage'],
        'image_manage' => $perm['image_manage']
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>权限管理 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body>
    <!-- 顶部导航栏 -->
    <header class="admin-header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo"><a href="/index.php"><?php echo SITE_NAME; ?></a></h1>
                <div class="header-actions">
                    <span class="welcome">欢迎, <?php echo $_SESSION['username']; ?></span>
                    <a href="/index.php" class="back-btn">返回首页</a>
                    <a href="/includes/auth.php?action=logout" class="logout-btn">退出</a>
                </div>
            </div>
        </div>
    </header>

    <!-- 主内容区 -->
    <main class="admin-main">
        <div class="container">
            <!-- 侧边导航 -->
            <aside class="admin-sidebar">
                <nav class="admin-nav">
                    <ul>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item"><a href="/admin/index.php">仪表盘</a></li>
                            <li class="nav-item"><a href="/admin/users.php">用户管理</a></li>
                            <li class="nav-item active"><a href="/admin/permissions.php">权限管理</a></li>
                            <li class="nav-item"><a href="/admin/galleries.php">图库管理</a></li>
                            <li class="nav-item"><a href="/admin/categories.php">分类管理</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </aside>

            <!-- 内容区域 -->
            <section class="admin-content">
                <div class="content-header">
                    <h2>权限管理</h2>
                    <p>管理用户的权限，包括访问权限、管理权限和上传权限等。</p>
                </div>

                <!-- 消息提示 -->
                <?php if (isset($success)): ?>
                    <div class="success-message">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <!-- 用户选择 -->
                <div class="admin-form">
                    <h3>选择用户</h3>
                    <form method="GET" action="/admin/permissions.php">
                        <div class="form-group">
                            <label for="user-select">用户</label>
                            <select id="user-select" name="user_id" onchange="this.form.submit()">
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $selected_user_id == $user['id'] ? 'selected' : ''; ?>><?php echo $user['username']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- 权限设置 -->
                <div class="admin-form">
                    <h3>权限设置</h3>
                    <form method="POST" action="/admin/permissions.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" value="<?php echo $selected_user_id; ?>">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>图库</th>
                                    <th>图库访问权限</th>
                                    <th>图库管理权限</th>
                                    <th>分类管理权限</th>
                                    <th>图片管理权限</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($galleries as $gallery): ?>
                                    <tr>
                                        <td><?php echo $gallery['name']; ?></td>
                                        <td>
                                            <input type="checkbox" name="gallery_access_<?php echo $gallery['id']; ?>" id="gallery_access_<?php echo $gallery['id']; ?>" <?php echo isset($permissions[$gallery['id']]) && $permissions[$gallery['id']]['gallery_access'] ? 'checked' : ''; ?>>
                                            <label for="gallery_access_<?php echo $gallery['id']; ?>">允许访问</label>
                                        </td>
                                        <td>
                                            <input type="checkbox" name="gallery_manage_<?php echo $gallery['id']; ?>" id="gallery_manage_<?php echo $gallery['id']; ?>" <?php echo isset($permissions[$gallery['id']]) && $permissions[$gallery['id']]['gallery_manage'] ? 'checked' : ''; ?>>
                                            <label for="gallery_manage_<?php echo $gallery['id']; ?>">允许管理</label>
                                        </td>
                                        <td>
                                            <input type="checkbox" name="category_manage_<?php echo $gallery['id']; ?>" id="category_manage_<?php echo $gallery['id']; ?>" <?php echo isset($permissions[$gallery['id']]) && $permissions[$gallery['id']]['category_manage'] ? 'checked' : ''; ?>>
                                            <label for="category_manage_<?php echo $gallery['id']; ?>">允许管理分类</label>
                                        </td>
                                        <td>
                                            <input type="checkbox" name="image_manage_<?php echo $gallery['id']; ?>" id="image_manage_<?php echo $gallery['id']; ?>" <?php echo isset($permissions[$gallery['id']]) && $permissions[$gallery['id']]['image_manage'] ? 'checked' : ''; ?>>
                                            <label for="image_manage_<?php echo $gallery['id']; ?>">允许管理图片</label>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">保存权限</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <!-- 页脚 -->
    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. 保留所有权利。</p>
        </div>
    </footer>

    <script src="/js/admin.js"></script>
    <script>
        // 权限依赖关系
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const id = this.id;
                    if (id.startsWith('gallery_access_')) {
                        const galleryId = id.split('_')[2];
                        const galleryManageCheckbox = document.getElementById(`gallery_manage_${galleryId}`);
                        const categoryManageCheckbox = document.getElementById(`category_manage_${galleryId}`);
                        const imageManageCheckbox = document.getElementById(`image_manage_${galleryId}`);
                        
                        if (!this.checked) {
                            galleryManageCheckbox.checked = false;
                            categoryManageCheckbox.checked = false;
                            imageManageCheckbox.checked = false;
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
