<?php
// 图库管理页面

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/init.php';
require_once '../includes/functions.php';

// 检查是否登录
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// 检查是否有图库管理权限
if (!hasAnyGalleryManage()) {
    header('Location: /index.php');
    exit;
}

// 获取数据库连接
$db = getDB();

// 处理添加图库请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    // 只有管理员可以添加图库
    if (!isAdmin()) {
        $error = '您没有权限添加图库';
    } else {
        $name = $_POST['name'];
        
        // 检查图库名称是否已存在
        $stmt = $db->prepare("SELECT id FROM galleries WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $error = '图库名称已存在';
        } else {
            // 创建图库目录
            createGalleryDirectory($name);
            
            // 添加图库
            $stmt = $db->prepare("INSERT INTO galleries (name) VALUES (?) ");
            if ($stmt->execute([$name])) {
                $success = '图库添加成功';
            } else {
                $error = '图库添加失败';
            }
        }
    }
}

// 处理修改图库请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $old_name = $_POST['old_name'];
    
    // 检查用户是否有权限修改该图库
    if (!isAdmin() && !hasGalleryManage($id)) {
        $error = '您没有权限修改该图库';
    } else {
        // 检查图库名称是否已存在
        $stmt = $db->prepare("SELECT id FROM galleries WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetch()) {
            $error = '图库名称已存在';
        } else {
            // 重命名图库目录
            renameGalleryDirectory($old_name, $name);
            
            // 修改图库名称
            $stmt = $db->prepare("UPDATE galleries SET name = ? WHERE id = ?");
            if ($stmt->execute([$name, $id])) {
                // 更新图片路径
                $stmt = $db->prepare("UPDATE images SET path = REPLACE(path, ?, ?) WHERE gallery_id = ?");
                $stmt->execute([$old_name, $name, $id]);
                
                $success = '图库修改成功';
            } else {
                $error = '图库修改失败';
            }
        }
    }
}

// 处理删除图库请求
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 检查用户是否有权限删除该图库
    if (!isAdmin() && !hasGalleryManage($id)) {
        $error = '您没有权限删除该图库';
        // 重定向到图库列表页面
        header('Location: /admin/galleries.php?error=' . urlencode($error));
        exit;
    }
    
    // 获取图库名称
    $stmt = $db->prepare("SELECT name FROM galleries WHERE id = ?");
    $stmt->execute([$id]);
    $gallery = $stmt->fetch();
    
    if ($gallery) {
        // 删除图库目录
        deleteGalleryDirectory($gallery['name']);
        
        // 删除图库（级联删除相关的图片和分类）
        $stmt = $db->prepare("DELETE FROM galleries WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = '图库删除成功';
        } else {
            $error = '图库删除失败';
        }
    } else {
        $error = '图库不存在';
    }
}

// 获取用户有权限管理的图库
if (isAdmin()) {
    // 管理员可以看到所有图库
    $stmt = $db->query("SELECT id, name, created_at FROM galleries ORDER BY name");
    $galleries = $stmt->fetchAll();
} else {
    // 子用户只能看到自己有权限管理的图库
    $db = getDB();
    $stmt = $db->prepare("SELECT g.id, g.name, g.created_at FROM galleries g 
                          JOIN permissions p ON g.id = p.gallery_id 
                          WHERE p.user_id = ? AND p.gallery_manage = 1 
                          ORDER BY g.name");
    $stmt->execute([$_SESSION['user_id']]);
    $galleries = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>图库管理 - <?php echo SITE_NAME; ?></title>
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
                            <li class="nav-item active"><a href="/admin/galleries.php">图库管理</a></li>
                        <?php endif; ?>
                        <?php if (hasAnyCategoryManage()): ?>
                            <li class="nav-item"><a href="/admin/categories.php">分类管理</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </aside>

            <!-- 内容区域 -->
            <section class="admin-content">
                <div class="content-header">
                    <h2>图库管理</h2>
                    <p>管理系统图库，包括修改和删除图库，以及同步服务器上的图片文件夹。</p>
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

                <!-- 添加图库表单 -->
                <?php if (isAdmin()): ?>
                <div class="admin-form">
                    <h3>添加图库</h3>
                    <form method="POST" action="/admin/galleries.php">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label for="name">图库名称</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">添加</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <!-- 图库列表 -->
                <div class="galleries-list">
                    <h3>图库列表</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>名称</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($galleries as $index => $gallery): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $gallery['name']; ?></td>
                                    <td><?php echo $gallery['created_at']; ?></td>
                                    <td>
                                        <!-- 修改图库按钮 -->
                                        <button class="btn btn-secondary" onclick="toggleEditForm(<?php echo $gallery['id']; ?>)">修改</button>
                                        
                                        <!-- 删除图库按钮 -->
                                        <a href="/admin/galleries.php?action=delete&id=<?php echo $gallery['id']; ?>" class="btn btn-danger" data-action="delete" data-id="<?php echo $gallery['id']; ?>" data-name="<?php echo $gallery['name']; ?>">删除</a>
                                    </td>
                                </tr>
                                
                                <!-- 修改图库表单 -->
                                <tr id="edit-form-<?php echo $gallery['id']; ?>" style="display: none;">
                                    <td colspan="4">
                                        <form method="POST" action="/admin/galleries.php">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?php echo $gallery['id']; ?>">
                                            <input type="hidden" name="old_name" value="<?php echo $gallery['name']; ?>">
                                            <div class="form-group" style="display: inline-block; width: 30%; margin-right: 20px;">
                                                <label for="edit-name-<?php echo $gallery['id']; ?>">图库名称</label>
                                                <input type="text" id="edit-name-<?php echo $gallery['id']; ?>" name="name" value="<?php echo $gallery['name']; ?>" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary">保存</button>
                                            <button type="button" class="btn btn-secondary" onclick="toggleEditForm(<?php echo $gallery['id']; ?>)">取消</button>
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
        // 切换修改图库表单显示状态
        function toggleEditForm(galleryId) {
            const formRow = document.getElementById('edit-form-' + galleryId);
            if (formRow.style.display === 'none' || formRow.style.display === '') {
                formRow.style.display = 'table-row';
            } else {
                formRow.style.display = 'none';
            }
        }
    </script>
</body>
</html>
