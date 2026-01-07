<?php
// 后台管理主页面

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/init.php';

// 检查是否登录
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// 检查是否为管理员，子账号无法访问仪表盘，需要重定向到有权限的页面
if (!isAdmin()) {
    // 检查子账号是否有图库管理权限
    if (hasAnyGalleryManage()) {
        header('Location: /admin/galleries.php');
        exit;
    }
    // 检查子账号是否有分类管理权限
    if (hasAnyCategoryManage()) {
        header('Location: /admin/categories.php');
        exit;
    }
    // 没有任何管理权限，重定向回首页
    header('Location: /index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - <?php echo SITE_NAME; ?></title>
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
                        <li class="nav-item active"><a href="/admin/index.php">仪表盘</a></li>
                        <li class="nav-item"><a href="/admin/users.php">用户管理</a></li>
                        <li class="nav-item"><a href="/admin/permissions.php">权限管理</a></li>
                        <li class="nav-item"><a href="/admin/galleries.php">图库管理</a></li>
                        <li class="nav-item"><a href="/admin/categories.php">分类管理</a></li>
                    </ul>
                </nav>
            </aside>

            <!-- 内容区域 -->
            <section class="admin-content">
                <div class="content-header">
                    <h2>欢迎来到后台管理</h2>
                    <p>这里是个人图库的后台管理中心，您可以在这里管理用户、权限、图库和分类。</p>
                </div>

                <div class="dashboard-stats">
                    <!-- 统计卡片将通过JavaScript动态加载 -->
                </div>

                <div class="dashboard-info">
                    <h3>系统信息</h3>
                    <ul>
                        <li>系统名称: <?php echo SITE_NAME; ?></li>
                        <li>版本: 1.0.0</li>
                        <li>PHP版本: <?php echo phpversion(); ?></li>
                        <li>SQLite版本: <?php echo SQLite3::version()['versionString']; ?></li>
                        <li>服务器时间: <?php echo date('Y-m-d H:i:s'); ?></li>
                    </ul>
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
</body>
</html>
