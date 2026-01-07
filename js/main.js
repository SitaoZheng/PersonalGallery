// 主脚本文件

// DOM加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 初始化筛选功能
    initFilters();
    
    // 初始化模态框
    initModals();
    
    // 初始化上传功能
    initUpload();
    
    // 初始化瀑布流
    initWaterfall();
});

// 初始化筛选功能
function initFilters() {
    // 图库选择
    const gallerySelect = document.getElementById('gallery-select');
    if (gallerySelect) {
        gallerySelect.addEventListener('change', function() {
            const galleryId = this.value;
            window.location.href = `/index.php?gallery=${galleryId}`;
        });
    }
    
    // 分类筛选 - 多选
    initFilterOptions('category-filter', 'category', true);
    
    // 标签筛选 - 多选
    initFilterOptions('tag-filter', 'tags', true);
    
    // 格式筛选 - 多选
    initFilterOptions('format-filter', 'format', true);
    
    // 版型筛选 - 多选
    initFilterOptions('orientation-filter', 'orientation', true);
    
    // 排序
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            updateUrlParams({'sort': this.value});
        });
    }
    
    // 重置筛选
    const resetBtn = document.getElementById('reset-filters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            // 重置所有筛选条件
            const galleryId = gallerySelect ? gallerySelect.value : '';
            window.location.href = `/index.php?gallery=${galleryId}`;
        });
    }
}

// 初始化筛选选项
function initFilterOptions(containerId, paramName, isMultiSelect = false) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const options = container.querySelectorAll('.filter-option');
    options.forEach(option => {
        option.addEventListener('click', function() {
            const value = this.dataset.value;
            
            if (isMultiSelect) {
                // 多选逻辑
                if (value === 'all') {
                    // 点击'全部'，取消所有选中
                    options.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    updateUrlParams({[paramName]: ''});
                } else {
                    // 点击其他选项，切换选中状态
                    const allOption = container.querySelector('.filter-option[data-value="all"]');
                    allOption.classList.remove('active');
                    
                    this.classList.toggle('active');
                    
                    // 获取所有选中的选项值
                    const selectedValues = Array.from(container.querySelectorAll('.filter-option.active'))
                        .map(opt => opt.dataset.value);
                    
                    updateUrlParams({[paramName]: selectedValues.join(',')});
                }
            } else {
                // 单选逻辑
                options.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                updateUrlParams({[paramName]: value});
            }
        });
    });
}

// 更新URL参数并刷新页面
function updateUrlParams(params) {
    const url = new URL(window.location.href);
    const searchParams = new URLSearchParams(url.search);
    
    // 更新参数
    Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === null) {
            searchParams.delete(key);
        } else {
            searchParams.set(key, params[key]);
        }
    });
    
    // 重置页码
    searchParams.set('page', '1');
    
    // 更新URL并刷新
    url.search = searchParams.toString();
    window.location.href = url.toString();
}

// 初始化模态框
function initModals() {
    // 获取模态框元素
    const modals = document.querySelectorAll('.modal');
    const closeBtns = document.querySelectorAll('.close-btn');
    
    // 打开图片详情模态框
    const viewBtns = document.querySelectorAll('.view-btn');
    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const imageId = this.dataset.imageId;
            openImageModal(imageId);
        });
    });
    
    // 打开图片编辑模态框
    const editBtns = document.querySelectorAll('.edit-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const imageId = this.dataset.imageId;
            openEditModal(imageId);
        });
    });
    
    // 打开上传模态框
    const uploadBtn = document.getElementById('upload-btn');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function() {
            const uploadModal = document.getElementById('upload-modal');
            uploadModal.classList.add('show');
        });
    }
    
    // 关闭模态框
    closeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.classList.remove('show');
        });
    });
    
    // 点击模态框外部关闭
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    });
    
    // ESC键关闭模态框
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modals.forEach(modal => {
                modal.classList.remove('show');
            });
        }
    });
}

// 打开图片详情模态框
function openImageModal(imageId) {
    const modal = document.getElementById('image-modal');
    const modalBody = document.getElementById('modal-body');
    
    // 显示加载状态
    modalBody.innerHTML = '<div class="loading">加载中...</div>';
    
    // 显示模态框
    modal.classList.add('show');
    
    // 使用AJAX请求获取图片详情
    fetch(`/api/image.php?id=${imageId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 渲染图片详情
            renderImageDetail(modalBody, data.image);
        } else {
            modalBody.innerHTML = `<div class="error">${data.message}</div>`;
        }
    })
    .catch(error => {
        modalBody.innerHTML = '<div class="error">加载图片详情失败</div>';
        console.error('Error:', error);
    });
}

// 渲染图片详情
function renderImageDetail(container, image) {
    const html = `
        <div class="image-detail">
            <div class="image-detail-left">
                <img src="/images/${image.path}" alt="${image.name}" class="detail-image">
            </div>
            <div class="image-detail-right">
                <div class="detail-info">
                    <h4>${image.name}</h4>
                    <p><strong>图片ID:</strong> ${image.id}</p>
                    <p><strong>文件大小:</strong> ${image.size_text}</p>
                    <p><strong>尺寸:</strong> ${image.width} × ${image.height}</p>
                    <p><strong>格式:</strong> ${image.format.toUpperCase()}</p>
                    <p><strong>版型:</strong> ${getOrientationText(image.orientation)}</p>
                    <p><strong>上传时间:</strong> ${image.uploaded_at_text}</p>
                    <div class="detail-tags">
                        ${image.tags.map(tag => `<span class="detail-tag">${tag}</span>`).join('')}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

// 获取版型文本
function getOrientationText(orientation) {
    const orientationMap = {
        'portrait': '竖版',
        'landscape': '横版',
        'square': '正方形'
    };
    
    return orientationMap[orientation] || orientation;
}

// 打开图片编辑模态框
function openEditModal(imageId) {
    const modal = document.getElementById('edit-modal');
    const modalBody = document.getElementById('edit-modal-body');
    
    // 显示加载状态
    modalBody.innerHTML = '<div class="loading">加载中...</div>';
    
    // 显示模态框
    modal.classList.add('show');
    
    // 使用AJAX请求获取图片详情
    fetch(`/api/image.php?id=${imageId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 渲染编辑表单
            renderEditForm(modalBody, data.image);
        } else {
            modalBody.innerHTML = `<div class="error">${data.message}</div>`;
        }
    })
    .catch(error => {
        modalBody.innerHTML = '<div class="error">加载图片详情失败</div>';
        console.error('Error:', error);
    });
}

// 渲染编辑表单
function renderEditForm(container, image) {
    const html = `
        <h3>编辑图片信息</h3>
        <form id="edit-form" method="POST" action="/includes/upload.php">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="image_id" value="${image.id}">
            <div class="form-group">
                <label for="edit-name">图片名称</label>
                <input type="text" id="edit-name" name="name" value="${image.name}" required>
            </div>
            <div class="form-group">
                <label for="edit-category">分类</label>
                <select id="edit-category" name="category_id">
                    <option value="">无分类</option>
                    <!-- 分类选项将通过PHP动态生成 -->
                </select>
            </div>
            <div class="form-group">
                <label for="edit-tags">标签 (用逗号分隔)</label>
                <input type="text" id="edit-tags" name="tags" value="${image.tags}" placeholder="例如: 风景, 自然, 山水">
            </div>
            <div class="form-actions">
                <button type="submit" class="submit-btn">保存更改</button>
                <button type="button" class="cancel-btn">取消</button>
            </div>
        </form>
    `;
    
    container.innerHTML = html;
    
    // 绑定表单事件
    const editForm = document.getElementById('edit-form');
    const cancelBtn = editForm.querySelector('.cancel-btn');
    
    // 取消按钮事件
    cancelBtn.addEventListener('click', function() {
        const modal = document.getElementById('edit-modal');
        modal.classList.remove('show');
    });
    
    // 获取分类列表并填充到选择框
    function loadCategories() {
        const categorySelect = document.getElementById('edit-category');
        
        // 清空现有选项（保留"无分类"选项）
        categorySelect.innerHTML = '<option value="">无分类</option>';
        
        // 调用API获取分类列表
        fetch(`/api/categories.php?gallery_id=${image.gallery_id}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 动态生成分类选项
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    // 设置当前选中的分类
                    if (category.id == image.category_id) {
                        option.selected = true;
                    }
                    categorySelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('获取分类列表失败:', error);
        });
    }
    
    // 加载分类列表
    loadCategories();
    
    // 表单提交事件
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // 创建FormData对象
        const formData = new FormData(this);
        formData.append('action', 'update');
        
        // 禁用提交按钮，防止重复提交
        const submitBtn = this.querySelector('.submit-btn');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = '保存中...';
        
        // 使用AJAX提交编辑后的图片信息
        fetch('/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 关闭模态框
                const modal = document.getElementById('edit-modal');
                modal.classList.remove('show');
                
                // 刷新页面
                window.location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            alert('保存图片信息失败');
            console.error('Error:', error);
        })
        .finally(() => {
            // 恢复提交按钮
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
}

// 初始化上传功能
function initUpload() {
    const uploadForm = document.getElementById('upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // 创建FormData对象
            const formData = new FormData(this);
            formData.append('action', 'upload');
            
            // 禁用提交按钮，防止重复提交
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '上传中...';
            
            // 使用AJAX上传图片
            fetch('/upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 关闭模态框
                    const modal = document.getElementById('upload-modal');
                    modal.classList.remove('show');
                    
                    // 重置表单
                    uploadForm.reset();
                    
                    // 刷新页面
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('图片上传失败');
                console.error('Error:', error);
            })
            .finally(() => {
                // 恢复提交按钮
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
        
        // 取消按钮事件
        const cancelBtn = uploadForm.querySelector('.cancel-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                const modal = document.getElementById('upload-modal');
                modal.classList.remove('show');
                
                // 重置表单
                uploadForm.reset();
            });
        }
    }
}

// 初始化瀑布流
function initWaterfall() {
    // 调用瀑布流初始化函数（在waterfall.js中定义）
    if (typeof initWaterfallLayout === 'function') {
        initWaterfallLayout();
    }
    
    // 监听窗口大小变化，重新布局瀑布流
    window.addEventListener('resize', function() {
        if (typeof layoutWaterfall === 'function') {
            layoutWaterfall();
        }
    });
}

// 图片删除功能
function deleteImage(imageId) {
    if (confirm('确定要删除这张图片吗？')) {
        // 创建FormData对象
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('image_id', imageId);
        
        // 使用AJAX请求删除图片
        fetch('/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 刷新页面
                window.location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            alert('删除图片失败');
            console.error('Error:', error);
        });
    }
}

// 绑定图片删除事件
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-btn')) {
        const imageId = e.target.dataset.imageId;
        deleteImage(imageId);
    }
});
