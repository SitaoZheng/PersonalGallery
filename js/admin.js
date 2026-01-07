// 后台管理脚本

// DOM加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 初始化统计数据
    initStats();
    
    // 初始化表单验证
    initFormValidation();
    
    // 初始化删除确认
    initDeleteConfirm();
    
    // 初始化侧边导航
    initSidebarNav();
});

// 初始化统计数据
function initStats() {
    // 检查是否在仪表盘页面
    const statsContainer = document.querySelector('.dashboard-stats');
    if (statsContainer) {
        // 加载初始数据
        loadStats();
        
        // 每5秒刷新一次数据
        setInterval(loadStats, 5000);
    }
}

// 从API加载统计数据
function loadStats() {
    const statsContainer = document.querySelector('.dashboard-stats');
    
    // 使用fetch API获取数据
    fetch('/api/stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 渲染统计卡片
                renderStats(statsContainer, data.data);
            } else {
                console.error('获取统计数据失败:', data.error);
            }
        })
        .catch(error => {
            console.error('获取统计数据失败:', error);
        });
}

// 渲染统计卡片
function renderStats(container, stats) {
    const html = stats.map(stat => `
        <div class="stat-card">
            <div class="stat-value">${stat.value}</div>
            <div class="stat-label">${stat.label}</div>
        </div>
    `).join('');
    
    container.innerHTML = html;
}

// 初始化表单验证
function initFormValidation() {
    const forms = document.querySelectorAll('.admin-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // 简单的表单验证
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    field.focus();
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('请填写所有必填字段');
            }
        });
    });
}

// 初始化删除确认
function initDeleteConfirm() {
    const deleteBtns = document.querySelectorAll('.btn-danger[data-action="delete"]');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const id = this.dataset.id;
            const name = this.dataset.name;
            const confirmMsg = name ? `确定要删除 "${name}" 吗？` : '确定要删除吗？';
            
            if (confirm(confirmMsg)) {
                // 这里应该是AJAX请求，删除数据
                // 为了演示，我们直接刷新页面
                window.location.href = this.href;
            }
        });
    });
}

// 初始化侧边导航
function initSidebarNav() {
    const navItems = document.querySelectorAll('.nav-item');
    const currentPath = window.location.pathname;
    
    navItems.forEach(item => {
        const link = item.querySelector('a');
        const linkPath = new URL(link.href).pathname;
        
        if (currentPath === linkPath) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
}

// 显示弹窗消息
function showModal(title, message, type = 'info') {
    // 创建弹窗覆盖层
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    
    // 创建弹窗内容
    const modal = document.createElement('div');
    modal.className = 'modal';
    
    // 创建标题
    const h3 = document.createElement('h3');
    h3.textContent = title;
    
    // 创建消息内容
    const p = document.createElement('p');
    p.textContent = message;
    
    // 创建操作按钮区域
    const actions = document.createElement('div');
    actions.className = 'modal-actions';
    
    // 创建确认按钮
    const confirmBtn = document.createElement('button');
    confirmBtn.className = 'btn btn-primary';
    confirmBtn.textContent = '确认';
    
    // 点击确认按钮关闭弹窗
    confirmBtn.addEventListener('click', function() {
        overlay.remove();
    });
    
    // 添加按钮到操作区域
    actions.appendChild(confirmBtn);
    
    // 组装弹窗
    modal.appendChild(h3);
    modal.appendChild(p);
    modal.appendChild(actions);
    overlay.appendChild(modal);
    
    // 添加到文档中
    document.body.appendChild(overlay);
    
    // 点击覆盖层关闭弹窗
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.remove();
        }
    });
}

// 显示成功消息
function showSuccessMessage(message) {
    showModal('操作成功', message, 'success');
}

// 显示错误消息
function showErrorMessage(message) {
    showModal('操作失败', message, 'error');
}

// 动画效果
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
`;
document.head.appendChild(style);
