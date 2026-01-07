// 登录页面脚本

// 表单提交处理
const loginForm = document.getElementById('login-form');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        // 客户端验证
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        
        if (!username || !password) {
            e.preventDefault();
            showError('请输入用户名和密码');
            return;
        }
        
        // 禁用提交按钮，防止重复提交
        const loginBtn = this.querySelector('.login-btn');
        loginBtn.disabled = true;
        loginBtn.textContent = '登录中...';
    });
}

// 显示错误信息
function showError(message) {
    // 移除已存在的错误信息
    const existingError = document.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // 创建新的错误信息元素
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    // 插入到表单上方
    const loginBox = document.querySelector('.login-box');
    const form = loginBox.querySelector('form');
    loginBox.insertBefore(errorDiv, form);
    
    // 添加抖动动画
    errorDiv.classList.add('shake-animation');
}

// 输入框焦点效果
const inputs = document.querySelectorAll('input');
inputs.forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.classList.add('focused');
    });
    
    input.addEventListener('blur', function() {
        if (!this.value) {
            this.parentElement.classList.remove('focused');
        }
    });
});
