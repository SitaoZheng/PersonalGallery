<?php
// 分类管理页面

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/init.php';

// 检查是否登录
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// 检查是否有分类管理权限
if (!hasAnyCategoryManage()) {
    header('Location: /index.php');
    exit;
}

// 获取数据库连接
$db = getDB();

// 获取用户有权限管理分类的图库
if (isAdmin()) {
    // 管理员可以看到所有图库
    $stmt = $db->query("SELECT id, name FROM galleries ORDER BY name");
    $galleries = $stmt->fetchAll();
} else {
    // 子用户只能看到自己有权限管理分类的图库
    $stmt = $db->prepare("SELECT g.id, g.name FROM galleries g 
                          JOIN permissions p ON g.id = p.gallery_id 
                          WHERE p.user_id = ? AND p.category_manage = 1 
                          ORDER BY g.name");
    $stmt->execute([$_SESSION['user_id']]);
    $galleries = $stmt->fetchAll();
}

// 默认选择第一个图库
$selected_gallery_id = isset($_GET['gallery_id']) ? $_GET['gallery_id'] : (empty($galleries) ? 0 : $galleries[0]['id']);

// 处理添加分类请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $gallery_id = $_POST['gallery_id'];
    $name = $_POST['name'];
    
    // 检查用户是否有权限管理该图库的分类
    if (!isAdmin() && !hasCategoryManage($gallery_id)) {
        $error = '您没有权限在该图库添加分类';
    } else {
        // 检查分类名称是否已存在
        $stmt = $db->prepare("SELECT id FROM categories WHERE gallery_id = ? AND name = ?");
        $stmt->execute([$gallery_id, $name]);
        if ($stmt->fetch()) {
            $error = '分类名称已存在';
        } else {
            // 添加分类
            $stmt = $db->prepare("INSERT INTO categories (gallery_id, name) VALUES (?, ?)");
            if ($stmt->execute([$gallery_id, $name])) {
                $success = '分类添加成功';
            } else {
                $error = '分类添加失败';
            }
        }
    }
}

// 处理修改分类请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $gallery_id = $_POST['gallery_id'];
    
    // 检查用户是否有权限管理该图库的分类
    if (!isAdmin() && !hasCategoryManage($gallery_id)) {
        $error = '您没有权限修改该分类';
    } else {
        // 检查分类名称是否已存在
        $stmt = $db->prepare("SELECT id FROM categories WHERE gallery_id = ? AND name = ? AND id != ?");
        $stmt->execute([$gallery_id, $name, $id]);
        if ($stmt->fetch()) {
            $error = '分类名称已存在';
        } else {
            // 修改分类
            $stmt = $db->prepare("UPDATE categories SET name = ? WHERE id = ?");
            if ($stmt->execute([$name, $id])) {
                $success = '分类修改成功';
            } else {
                $error = '分类修改失败';
            }
        }
    }
}

// 处理删除分类请求
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 获取分类所属的图库ID
    $stmt = $db->prepare("SELECT gallery_id FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        $error = '分类不存在';
    } else {
        $gallery_id = $category['gallery_id'];
        
        // 检查用户是否有权限管理该图库的分类
        if (!isAdmin() && !hasCategoryManage($gallery_id)) {
            $error = '您没有权限删除该分类';
        } else {
            // 删除分类（级联删除相关的图片分类）
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = '分类删除成功';
            } else {
                $error = '分类删除失败';
            }
        }
    }
}

// 获取当前图库的分类
$stmt = $db->prepare("SELECT id, name FROM categories WHERE gallery_id = ? ORDER BY name");
$stmt->execute([$selected_gallery_id]);
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - <?php echo SITE_NAME; ?></title>
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
                            <li class="nav-item"><a href="/admin/permissions.php">权限管理</a></li>
                        <?php endif; ?>
                        <?php if (hasAnyGalleryManage()): ?>
                            <li class="nav-item"><a href="/admin/galleries.php">图库管理</a></li>
                        <?php endif; ?>
                        <?php if (hasAnyCategoryManage()): ?>
                            <li class="nav-item active"><a href="/admin/categories.php">分类管理</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </aside>

            <!-- 内容区域 -->
            <section class="admin-content">
                <div class="content-header">
                    <h2>分类管理</h2>
                    <p>管理图库分类，包括添加、修改和删除分类。</p>
                </div>

                <!-- 消息提示 -->
                <?php if (isset($success)): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showSuccessMessage('<?php echo $success; ?>');
                        });
                    </script>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showErrorMessage('<?php echo $error; ?>');
                        });
                    </script>
                <?php endif; ?>

                <!-- 图库选择 -->
                <div class="admin-form">
                    <h3>选择图库</h3>
                    <form method="GET" action="/admin/categories.php">
                        <div class="form-group">
                            <label for="gallery-select">图库</label>
                            <select id="gallery-select" name="gallery_id" onchange="this.form.submit()">
                                <?php foreach ($galleries as $gallery): ?>
                                    <option value="<?php echo $gallery['id']; ?>" <?php echo $selected_gallery_id == $gallery['id'] ? 'selected' : ''; ?>>
                                        <?php echo $gallery['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- 添加分类表单 -->
                <div class="admin-form">
                    <h3>添加分类</h3>
                    <form method="POST" action="/admin/categories.php">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="gallery_id" value="<?php echo $selected_gallery_id; ?>">
                        <div class="form-group">
                            <label for="name">分类名称</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">添加</button>
                        </div>
                    </form>
                </div>

                <!-- 分类列表 -->
                <div class="categories-list">
                    <h3>分类列表</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>名称</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $index => $category): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $category['name']; ?></td>
                                    <td>
                                        <!-- 修改分类按钮 -->
                                        <button class="btn btn-secondary" onclick="toggleEditForm(<?php echo $category['id']; ?>)">修改</button>
                                        
                                        <!-- 删除分类按钮 -->
                                        <a href="/admin/categories.php?action=delete&id=<?php echo $category['id']; ?>&gallery_id=<?php echo $selected_gallery_id; ?>" class="btn btn-danger" data-action="delete" data-id="<?php echo $category['id']; ?>" data-name="<?php echo $category['name']; ?>">删除</a>
                                    </td>
                                </tr>
                                
                                <!-- 修改分类表单 -->
                                <tr id="edit-form-<?php echo $category['id']; ?>" style="display: none;">
                                    <td colspan="3">
                                        <form method="POST" action="/admin/categories.php">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                            <input type="hidden" name="gallery_id" value="<?php echo $selected_gallery_id; ?>">
                                            <div class="form-group" style="display: inline-block; width: 30%; margin-right: 20px;">
                                                <label for="edit-name-<?php echo $category['id']; ?>">分类名称</label>
                                                <input type="text" id="edit-name-<?php echo $category['id']; ?>" name="name" value="<?php echo $category['name']; ?>" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary">保存</button>
                                            <button type="button" class="btn btn-secondary" onclick="toggleEditForm(<?php echo $category['id']; ?>)">取消</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
        // 切换修改分类表单显示状态
        function toggleEditForm(categoryId) {
            const formRow = document.getElementById('edit-form-' + categoryId);
            if (formRow.style.display === 'none' || formRow.style.display === '') {
                formRow.style.display = 'table-row';
            } else {
                formRow.style.display = 'none';
            }
        }
    </script>
</body>
</html>
