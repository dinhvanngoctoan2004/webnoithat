<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$username = isset($_SESSION['username']) ? strtolower($_SESSION['username']) : null;

// Xác định các link và text cho menu dựa trên trạng thái đăng nhập
if (!$username) {
    $giohang_href = "dangnhap.php";
    $giohang_onclick = "alert('Bạn cần đăng nhập để sử dụng chức năng này!'); return false;";
    $giohang_text = "Giỏ Hàng";

    $thongtin_href = "dangnhap.php";
    $thongtin_onclick = "alert('Bạn cần đăng nhập để sử dụng chức năng này!'); return false;";
    $thongtin_text = "Thông Tin Tài Khoản";

    $dathang_href = "dathang.php";
    $dathang_text = "Đặt Hàng";

    $cuoi_href = "dangnhap.php";
    $cuoi_text = "Đăng Nhập";
    $cuoi_onclick = "";
} else if ($username == "admin") {
    $giohang_href = "";
    $giohang_onclick = "";
    $giohang_text = "";

    $danhgia_href = "admin_danhgia.php";
    $danhgia_onclick = "";
    $danhgia_text = "Đánh Giá";

    $quanlydonhang_href = "admin_donhangthanhtoan.php";
    $quanlydonhang_onclick = "";
    $quanlydonhang_text = "Đơn Hàng";

    $quanlykhachhang_href = "admin_quanlytaikhoankhachhang.php";
    $quanlykhachhang_onclick = "";
    $quanlykhachhang_text = "Khách Hàng";

    $thongtin_href = "";
    $thongtin_onclick = "";
    $thongtin_text = "";
    
    $quanlysanpham_href = "quanlysanpham.php";
    $quanlysanpham_onclick = "";
    $quanlysanpham_text = "Ql.Sản Phẩm";
    
    $thongke_href = "admin_thongke.php";
    $thongke_onclick = "";
    $thongke_text = "Thống Kê";

    $cuoi_href = "dangxuat.php";
    $cuoi_text = "Đăng Xuất (" . htmlspecialchars($_SESSION['username']) . ")";
    $cuoi_onclick = "";
} else {
    $giohang_href = "giohang.php";
    $giohang_onclick = "";
    $giohang_text = "Giỏ Hàng";

    $dathang_href = "dathang.php";
    $dathang_text = "Đơn Hàng Của Bạn";

    $thongtin_href = "thongtintaikhoan.php";
    $thongtin_onclick = "";
    $thongtin_text = "Thông Tin Tài Khoản";

    $sanpham_href = "sanpham.php";
    $sanpham_onclick = "";
    $sanpham_text = "Sản Phẩm";

    $quanlysanpham_href = "quanlysanpham.php";
    $quanlysanpham_onclick = "";
    $quanlysanpham_text = "Quản Lý Sản Phẩm";

    $cuoi_href = "dangxuat.php";
    $cuoi_text = "Đăng Xuất (" . htmlspecialchars($_SESSION['username']) . ")";
    $cuoi_onclick = "";
}
?>

<script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
<script src="https://unpkg.com/unlazy@0.11.3/dist/unlazy.with-hashing.iife.js" defer init></script>

<script type="text/javascript">
    window.tailwind = window.tailwind || {};
    window.tailwind.config = {
        darkMode: ['class'],
        corePlugins: {
            preflight: false, 
        },
        theme: {
            extend: {
                colors: {
                    border: 'hsl(var(--border))',
                    input: 'hsl(var(--input))',
                    ring: 'hsl(var(--ring))',
                    background: 'hsl(var(--background))',
                    foreground: 'hsl(var(--foreground))',
                    primary: { DEFAULT: '#22223b', foreground: '#fff' },
                    secondary: { DEFAULT: '#CD853F', foreground: '#fff' },
                    muted: { DEFAULT: 'hsl(var(--muted))', foreground: 'hsl(var(--muted-foreground))' },
                    accent: { DEFAULT: 'hsl(var(--accent))', foreground: 'hsl(var(--accent-foreground))' },
                    card: { DEFAULT: 'hsl(var(--card))', foreground: 'hsl(var(--card-foreground))' },
                },
            }
        }
    }
</script>

<style>
    :root {
        --background: #fff;
        --foreground: #222;
        --muted: #f3f4f6;
        --muted-foreground: #6b7280;
    }

    /* Style menu */
    .custom-header {
        font-family: 'Segoe UI', Arial, sans-serif;
        background-color: #FDF5E6; 
        border-bottom: 1px solid #e0d6c3;
    }
    
    .custom-nav a {
        color: #222;
        text-decoration: none;
        margin: 0 10px;
        font-weight: 500;
        transition: color 0.25s cubic-bezier(.4,0,.2,1);
    }
    
    .custom-nav a:hover, .custom-nav a:focus {
        color: #CD853F !important;
    }

    /* Autocomplete Box */
    .autocomplete-suggestions {
        position: absolute;
        top: 100%;
        right: 0;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 0 0 8px 8px;
        width: 100%;
        min-width: 220px;
        max-height: 220px;
        overflow-y: auto;
        z-index: 9999;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        text-align: left;
    }
    .autocomplete-suggestion {
        padding: 10px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f3f3f3;
        font-size: 14px;
        color: #333;
    }
    .autocomplete-suggestion:last-child {
        border-bottom: none;
    }
    .autocomplete-suggestion:hover, .autocomplete-suggestion.active {
        background: #ffe4c4;
        color: #CD853F;
    }
    
    .search-wrapper {
        position: relative;
        display: inline-block;
    }
</style>

<header class="flex justify-between items-center p-4 custom-header">
    <div class="text-2xl font-bold" style="color: black;">NỘI THẤT HIỆN ĐẠI</div>
    
    <nav class="space-x-4 custom-nav flex flex-wrap justify-center">
        <a href="trangchu.php">Trang Chủ</a>
        <a href="sanpham.php">Sản Phẩm</a>
        <?php if ($username == "admin"): ?>
            <a href="<?= $quanlysanpham_href ?>" <?= $quanlysanpham_onclick ? 'onclick="'.$quanlysanpham_onclick.'"' : '' ?>><?= $quanlysanpham_text ?></a>
            <a href="<?= $quanlykhachhang_href ?>" <?= $quanlykhachhang_onclick ? 'onclick="'.$quanlykhachhang_onclick.'"' : '' ?>><?= $quanlykhachhang_text ?></a>
            <a href="<?= $quanlydonhang_href ?>" <?= $quanlydonhang_onclick ? 'onclick="'.$quanlydonhang_onclick.'"' : '' ?>><?= $quanlydonhang_text ?></a>
            <a href="<?= $danhgia_href ?>" <?= $danhgia_onclick ? 'onclick="'.$danhgia_onclick.'"' : '' ?>><?= $danhgia_text ?></a>
            <a href="<?= $thongke_href ?>" <?= $thongke_onclick ? 'onclick="'.$thongke_onclick.'"' : '' ?>><?= $thongke_text ?></a>
        <?php endif; ?>
        
        <a href="<?= $giohang_href ?>" <?= $giohang_onclick ? 'onclick="'.$giohang_onclick.'"' : '' ?>><?= $giohang_text ?></a>
        <?php if (isset($dathang_href)): ?>
            <a href="<?= $dathang_href ?>"><?= $dathang_text ?></a>
        <?php endif; ?>
        <a href="<?= $thongtin_href ?>" <?= $thongtin_onclick ? 'onclick="'.$thongtin_onclick.'"' : '' ?>><?= $thongtin_text ?></a>
        <a href="<?= $cuoi_href ?>" <?= $cuoi_onclick ? 'onclick="'.$cuoi_onclick.'"' : '' ?>><?= $cuoi_text ?></a>
    </nav>
    
    <div class="search-wrapper">
        <input type="text" id="search-box" placeholder="Search..." class="border border-muted p-2 rounded" autocomplete="off" style="width:220px;" />
        <div id="autocomplete-box" class="autocomplete-suggestions" style="display:none;"></div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-box');
    const suggestionBox = document.getElementById('autocomplete-box');
    let debounceTimer = null; // Biến timer để tạo độ trễ khi gõ

    if (!searchInput || !suggestionBox) return;

    // --- HÀM 1: GỌI API LẤY DỮ LIỆU THẬT ---
    async function fetchProducts(keyword) {
        try {
            // Gọi đến file api_timkiem.php kèm tham số q
            const response = await fetch(`api_timkiem.php?q=${encodeURIComponent(keyword)}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error("Lỗi tìm kiếm:", error);
            return [];
        }
    }

    // --- HÀM 2: HIỂN THỊ KẾT QUẢ ---
    function renderSuggestions(suggestions, keyword) {
        suggestionBox.innerHTML = '';
        
        if (suggestions.length === 0) {
            const div = document.createElement('div');
            div.className = 'autocomplete-suggestion';
            div.textContent = 'Không tìm thấy sản phẩm nào';
            div.style.color = '#999';
            suggestionBox.appendChild(div);
        } else {
            suggestions.forEach(item => {
                const div = document.createElement('div');
                div.className = 'autocomplete-suggestion';
                
                // Highlight từ khóa tìm kiếm
                const regex = new RegExp(`(${keyword})`, 'gi');
                const highlightedName = item.name.replace(regex, '<strong style="color:#CD853F">$1</strong>');
                
                div.innerHTML = highlightedName;
                div.onclick = function() {
                    window.location.href = item.url;
                };
                suggestionBox.appendChild(div);
            });
        }
        suggestionBox.style.display = 'block';
    }

    // --- SỰ KIỆN KHI NGƯỜI DÙNG GÕ PHÍM ---
    searchInput.addEventListener('input', function() {
        const value = this.value.trim();
        
        // Reset timer mỗi khi gõ
        clearTimeout(debounceTimer);
        
        if (!value) {
            suggestionBox.style.display = 'none';
            return;
        }

        // Chờ 300ms sau khi ngừng gõ mới gửi request (kỹ thuật Debounce)
        // Giúp web mượt hơn, server không bị quá tải
        debounceTimer = setTimeout(async () => {
            const results = await fetchProducts(value);
            renderSuggestions(results, value);
        }, 300);
    });

    // Ẩn khi click ra ngoài
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionBox.contains(e.target)) {
            suggestionBox.style.display = 'none';
        }
    });
});
</script>