<?php
// 用户管理页面

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

// 处理添加用户请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // 检查用户名是否已存在
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $error = '用户名已存在';
    } else {
        // 哈希密码
        $hashed_password = password_hash($password, PASSWORD_HASH_ALGO);
        
        // 添加用户
        $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        if ($stmt->execute([$username, $hashed_password])) {
            $success = '用户添加成功';
        } else {
            $error = '用户添加失败';
        }
    }
}

// 处理修改用户请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];
    
    // 检查是否为当前用户
    if ($user_id == $_SESSION['user_id']) {
        // 哈希密码
        $hashed_password = password_hash($password, PASSWORD_HASH_ALGO);
        
        // 修改密码
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            $success = '密码修改成功';
        } else {
            $error = '密码修改失败';
        }
    } else {
        $error = '您只能修改自己的密码';
    }
}

// 处理删除用户请求
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // 检查是否为当前用户
    if ($user_id == $_SESSION['user_id']) {
        $error = '您不能删除自己的账号';
    } else {
        // 删除用户
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
        if ($stmt->execute([$user_id])) {
            $success = '用户删除成功';
        } else {
            $error = '用户删除失败';
        }
    }
}

// 获取所有用户
$stmt = $db->query("SELECT * FROM users ORDER BY id");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - <?php echo SITE_NAME; ?></title>
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
                            <li class="nav-item active"><a href="/admin/users.php">用户管理</a></li>
                            <li class="nav-item"><a href="/admin/permissions.php">权限管理</a></li>
                            <li class="nav-item"><a href="/admin/galleries.php">图库管理</a></li>
                            <li class="nav-item"><a href="/admin/categories.php">分类管理</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </aside>

            <!-- 内容区域 -->
            <section class="admin-content">
                <div class="content-header">
                    <h2>用户管理</h2>
                    <p>管理系统用户，包括添加、修改和删除用户。</p>
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

                <!-- 添加用户表单 -->
                <div class="admin-form">
                    <h3>添加用户</h3>
                    <form method="POST" action="/admin/users.php">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label for="username">用户名</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">密码</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">添加</button>
                        </div>
                    </form>
                </div>

                <!-- 用户列表 -->
                <div class="users-list">
                    <h3>用户列表</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>类型</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $index => $user): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['is_admin'] ? '管理员' : '普通用户'; ?></td>
                                    <td><?php echo $user['created_at']; ?></td>
                                    <td>
                                        <!-- 修改密码按钮 -->
                                        <button class="btn btn-secondary" onclick="togglePasswordForm(<?php echo $user['id']; ?>)">修改密码</button>
                                        
                                        <!-- 删除用户按钮 -->
                                        <?php if (!$user['is_admin'] && $user['id'] != $_SESSION['user_id']): ?>
                                            <a href="/admin/users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger" data-action="delete" data-id="<?php echo $user['id']; ?>" data-name="<?php echo $user['username']; ?>">删除</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- 修改密码表单 -->
                                <tr id="password-form-<?php echo $user['id']; ?>" style="display: none;">
                                    <td colspan="5">
                                        <form method="POST" action="/admin/users.php">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <div class="form-group" style="display: inline-block; width: 30%; margin-right: 20px;">
                                                <label for="password-<?php echo $user['id']; ?>">新密码</label>
                                                <input type="password" id="password-<?php echo $user['id']; ?>" name="password" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary">保存</button>
                                            <button type="button" class="btn btn-secondary" onclick="togglePasswordForm(<?php echo $user['id']; ?>)">取消</button>
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
        // 切换密码修改表单显示状态
        function togglePasswordForm(userId) {
            const formRow = document.getElementById('password-form-' + userId);
            if (formRow.style.display === 'none' || formRow.style.display === '') {
                formRow.style.display = 'table-row';
            } else {
                formRow.style.display = 'none';
            }
        }
    </script>
</body>
</html>
