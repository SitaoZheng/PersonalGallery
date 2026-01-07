<?php
// 首页

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/init.php';
require_once 'includes/functions.php';

// 检查是否登录
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// 获取用户可访问的图库列表
$galleries = getUserGalleries();

// 默认选择第一个图库
$selected_gallery_id = isset($_GET['gallery']) ? $_GET['gallery'] : (empty($galleries) ? 0 : $galleries[0]['id']);

// 获取筛选条件
$filters = [
    'category' => isset($_GET['category']) && $_GET['category'] !== '' ? explode(',', $_GET['category']) : [],
    'tags' => isset($_GET['tags']) ? explode(',', $_GET['tags']) : [],
    'format' => isset($_GET['format']) && $_GET['format'] !== '' ? explode(',', $_GET['format']) : [],
    'orientation' => isset($_GET['orientation']) && $_GET['orientation'] !== '' ? explode(',', $_GET['orientation']) : []
];

// 获取排序方式
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'uploaded_at DESC';

// 获取页码
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

// 获取图库下的图片
$images_result = getGalleryImages($selected_gallery_id, $filters, $sort, $page);
$images = $images_result['images'];
$total = $images_result['total'];
$pages = $images_result['pages'];

// 获取图库下的分类
$categories = getGalleryCategories($selected_gallery_id);

// 获取所有标签
$all_tags = getAllTags();

// 生成页面URL模板
$page_url = '/index.php?gallery=' . $selected_gallery_id;
if (!empty($filters['category'])) {
    $page_url .= '&category=' . implode(',', $filters['category']);
}
if (!empty($filters['tags'])) {
    $page_url .= '&tags=' . implode(',', $filters['tags']);
}
if (!empty($filters['format'])) {
    $page_url .= '&format=' . implode(',', $filters['format']);
}
if (!empty($filters['orientation'])) {
    $page_url .= '&orientation=' . implode(',', $filters['orientation']);
}
$page_url .= '&sort=' . $sort . '&page={page}';

// 生成排序选项
$sort_options = [
    'uploaded_at DESC' => '最新上传',
    'uploaded_at ASC' => '最早上传',
    'size DESC' => '最大尺寸',
    'size ASC' => '最小尺寸',
    'name ASC' => '名称升序',
    'name DESC' => '名称降序'
];
$sort_select = '';
foreach ($sort_options as $value => $label) {
    $selected = $sort == $value ? 'selected' : '';
    $sort_select .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/css/main.css">
</head>
<body>
    <!-- 顶部导航栏 -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo"><a href="/index.php"><?php echo SITE_NAME; ?></a></h1>
                <div class="header-actions">
                    <span class="welcome">欢迎, <?php echo $_SESSION['username']; ?></span>
                    <?php if (isAdmin() || hasAnyGalleryManage() || hasAnyCategoryManage() || hasAnyImageManage()): ?>
                        <a href="/admin/index.php" class="admin-btn">后台管理</a>
                    <?php endif; ?>
                    <a href="/includes/auth.php?action=logout" class="logout-btn">退出</a>
                </div>
            </div>
        </div>
    </header>

    <!-- 主内容区 -->
    <main class="main">
        <div class="container">
            <!-- 图片筛选区域 -->
            <section class="filters">
                <div class="filter-row  filter-row-last">
                    <div class="filter-section galleryselect-section">
                        <h3>图库选择</h3>
                        <select id="gallery-select" class="filter-select">
                            <?php foreach ($galleries as $gallery): ?>
                                <option value="<?php echo $gallery['id']; ?>" <?php echo $selected_gallery_id == $gallery['id'] ? 'selected' : ''; ?>><?php echo $gallery['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (hasImageManage($selected_gallery_id)): ?>
                        <button id="upload-btn" class="upload-btn">上传图片</button>
                    <?php endif; ?>
                </div>

                <div class="filter-row">
                    <div class="filter-section">
                        <h3>分类</h3>
                        <div class="filter-options" id="category-filter">
                            <div class="filter-option <?php echo is_array($filters['category']) && in_array('all', $filters['category']) ? 'active' : (empty($filters['category']) ? 'active' : ''); ?>" data-value="all">全部</div>
                            <?php foreach ($categories as $category): ?>
                                <div class="filter-option <?php echo is_array($filters['category']) && in_array($category['id'], $filters['category']) ? 'active' : ''; ?>" data-value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-section">
                        <h3>标签</h3>
                        <div class="filter-options" id="tag-filter">
                            <div class="filter-option <?php echo empty($filters['tags']) ? 'active' : ''; ?>" data-value="all">全部</div>
                            <?php foreach ($all_tags as $tag): ?>
                                <div class="filter-option <?php echo in_array($tag['name'], $filters['tags']) ? 'active' : ''; ?>" data-value="<?php echo $tag['name']; ?>"><?php echo $tag['name']; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-section">
                        <h3>格式</h3>
                        <div class="filter-options" id="format-filter">
                            <div class="filter-option <?php echo is_array($filters['format']) && in_array('all', $filters['format']) ? 'active' : (empty($filters['format']) ? 'active' : ''); ?>" data-value="all">全部</div>
                            <?php foreach (ALLOWED_IMAGE_TYPES as $format): ?>
                                <div class="filter-option <?php echo is_array($filters['format']) && in_array($format, $filters['format']) ? 'active' : ''; ?>" data-value="<?php echo $format; ?>"><?php echo strtoupper($format); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-section">
                        <h3>版型</h3>
                        <div class="filter-options" id="orientation-filter">
                            <div class="filter-option <?php echo is_array($filters['orientation']) && in_array('all', $filters['orientation']) ? 'active' : (empty($filters['orientation']) ? 'active' : ''); ?>" data-value="all">全部</div>
                            <div class="filter-option <?php echo is_array($filters['orientation']) && in_array('portrait', $filters['orientation']) ? 'active' : ''; ?>" data-value="portrait">竖版</div>
                            <div class="filter-option <?php echo is_array($filters['orientation']) && in_array('landscape', $filters['orientation']) ? 'active' : ''; ?>" data-value="landscape">横版</div>
                            <div class="filter-option <?php echo is_array($filters['orientation']) && in_array('square', $filters['orientation']) ? 'active' : ''; ?>" data-value="square">正方形</div>
                        </div>
                    </div>
                </div>

                <div class="filter-row filter-row-last">
                    <div class="filter-section sort-section">
                        <h3>排序</h3>
                        <select id="sort-select" class="filter-select">
                            <?php echo $sort_select; ?>
                        </select>
                    </div>

                    <button id="reset-filters" class="reset-btn">重置筛选</button>
                </div>
            </section>

            <!-- 瀑布流图片展示区域 -->
            <section class="waterfall-container">
                <div class="waterfall-grid" id="waterfall-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="waterfall-item" data-image-id="<?php echo $image['id']; ?>">
                            <div class="image-wrapper">
                                <img src="/images/<?php echo $image['path']; ?>" alt="<?php echo $image['name']; ?>" class="gallery-image">
                                <div class="image-overlay">
                                    <div class="image-info">
                                        <h4><?php echo $image['name']; ?></h4>
                                        <p><?php echo formatFileSize($image['size']); ?></p>
                                        <p><?php echo $image['width'] . '×' . $image['height']; ?></p>
                                    </div>
                                    <div class="image-actions">
                                        <button class="view-btn" data-image-id="<?php echo $image['id']; ?>">查看详情</button>
                                        <?php if (hasImageManage($image['gallery_id'])): ?>
                                            <button class="edit-btn" data-image-id="<?php echo $image['id']; ?>">编辑</button>
                                            <a href="/images/<?php echo $image['path']; ?>" download class="download-btn btn">下载</a>
                                            <button class="delete-btn" data-image-id="<?php echo $image['id']; ?>">删除</button>
                                        <?php else: ?>
                                            <a href="/images/<?php echo $image['path']; ?>" download class="download-btn btn">下载</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- 分页组件 -->
            <section class="pagination-section">
                <?php echo generatePagination($page, $pages, $page_url); ?>
            </section>
        </div>
    </main>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. 保留所有权利。</p>
        </div>
    </footer>

    <!-- 图片详情模态框 -->
    <div class="modal" id="image-modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <div class="modal-body" id="modal-body">
                <!-- 图片详情将通过JavaScript动态加载 -->
            </div>
        </div>
    </div>

    <!-- 图片编辑模态框 -->
    <div class="modal" id="edit-modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <div class="modal-body" id="edit-modal-body">
                <!-- 图片编辑表单将通过JavaScript动态加载 -->
            </div>
        </div>
    </div>

    <!-- 图片上传模态框 -->
    <div class="modal" id="upload-modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <div class="modal-body">
                <h3>上传图片</h3>
                <form id="upload-form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="gallery_id" value="<?php echo $selected_gallery_id; ?>">
                    <div class="form-group">
                        <label for="upload-name">图片名称</label>
                        <input type="text" id="upload-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="upload-category">分类</label>
                        <select id="upload-category" name="category_id">
                            <option value="">无分类</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="upload-tags">标签 (用逗号分隔)</label>
                        <input type="text" id="upload-tags" name="tags" placeholder="例如: 风景, 自然, 山水">
                    </div>
                    <div class="form-group">
                        <label for="upload-file">选择图片</label>
                        <input type="file" id="upload-file" name="file" accept="image/*" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="submit-btn">上传</button>
                        <button type="button" class="cancel-btn">取消</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/js/main.js"></script>
    <script src="/js/waterfall.js"></script>
</body>
</html>
