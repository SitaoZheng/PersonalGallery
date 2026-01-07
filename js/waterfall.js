// 瀑布流布局脚本

// 初始化瀑布流布局
function initWaterfallLayout() {
    // 获取瀑布流容器和所有图片项
    const grid = document.getElementById('waterfall-grid');
    if (!grid) return;
    
    const items = grid.querySelectorAll('.waterfall-item');
    if (items.length === 0) return;
    
    // 获取网格列数
    const columnCount = getColumnCount();
    
    // 如果列数为0，返回
    if (columnCount === 0) return;
    
    // 计算每列的宽度
    const columnWidth = calculateColumnWidth(grid, columnCount);
    
    // 设置每列的初始高度
    const columnHeights = new Array(columnCount).fill(0);
    
    // 布局每个图片项
    items.forEach(item => {
        // 找到高度最小的列
        const minHeight = Math.min(...columnHeights);
        const minIndex = columnHeights.indexOf(minHeight);
        
        // 设置图片项的位置（包含10px间距）
        item.style.position = 'absolute';
        item.style.width = `${columnWidth}px`;
        item.style.left = `${minIndex * (columnWidth + 10)}px`;
        item.style.top = `${minHeight}px`;
        
        // 更新列高度（添加10px间距）
        columnHeights[minIndex] += item.offsetHeight + 10;
    });
    
    // 设置网格容器的高度
    grid.style.position = 'relative';
    grid.style.height = `${Math.max(...columnHeights)}px`;
}

// 重新布局瀑布流
function layoutWaterfall() {
    // 重置网格容器
    const grid = document.getElementById('waterfall-grid');
    if (!grid) return;
    
    const items = grid.querySelectorAll('.waterfall-item');
    items.forEach(item => {
        item.style.position = '';
        item.style.width = '';
        item.style.left = '';
        item.style.top = '';
    });
    grid.style.position = '';
    grid.style.height = '';
    
    // 重新初始化布局
    initWaterfallLayout();
}

// 获取列数
function getColumnCount() {
    const containerWidth = document.querySelector('.container').offsetWidth;
    const minItemWidth = 250; // 最小图片宽度
    
    let columnCount = Math.floor(containerWidth / minItemWidth);
    
    // 确保列数至少为1
    columnCount = Math.max(columnCount, 1);
    
    // 响应式调整列数
    if (containerWidth < 768) {
        columnCount = Math.floor(containerWidth / 200);
    }
    
    if (containerWidth < 480) {
        columnCount = Math.floor(containerWidth / 150);
    }
    
    return Math.max(columnCount, 1);
}

// 计算每列的宽度
function calculateColumnWidth(grid, columnCount) {
    const gridWidth = grid.offsetWidth;
    const gap = 20; // 网格间距
    
    // 计算每列宽度，考虑间距
    const columnWidth = (gridWidth - (gap * (columnCount - 1))) / columnCount;
    
    return columnWidth;
}

// 监听图片加载完成事件，重新布局瀑布流
document.addEventListener('DOMContentLoaded', function() {
    // 监听所有图片加载完成
    const images = document.querySelectorAll('.gallery-image');
    let loadedCount = 0;
    
    images.forEach(img => {
        if (img.complete) {
            loadedCount++;
        } else {
            img.addEventListener('load', function() {
                loadedCount++;
                if (loadedCount === images.length) {
                    // 所有图片加载完成，初始化瀑布流
                    initWaterfallLayout();
                }
            });
        }
    });
    
    // 如果所有图片都已加载完成
    if (loadedCount === images.length) {
        initWaterfallLayout();
    }
});
