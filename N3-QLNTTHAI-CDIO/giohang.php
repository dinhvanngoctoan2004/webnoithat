<?php
ob_start(); // Bắt buộc để chuyển trang mượt
session_start();

// Database connection
$server = 'localhost';
$user = 'root';
$pass = '';
$database = 'webnoithat';

$conn = new mysqli($server, $user, $pass, $database);
$conn->set_charset("utf8");

// Check login status
$tentaikhoan = isset($_SESSION['username']) ? $_SESSION['username'] : '';
if (empty($tentaikhoan)) {
    echo "<script>alert('Vui lòng đăng nhập để xem giỏ hàng!'); window.location='dangnhap.php';</script>";
    exit;
}

// =======================================================================
// 1. XỬ LÝ AJAX CẬP NHẬT SỐ LƯỢNG
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update_qty'])) {
    $masp_update = $_POST['masp'];
    $qty_update = intval($_POST['soluong']);
    
    if ($qty_update > 0) {
        $stmt = $conn->prepare("UPDATE giohang SET soluong = ? WHERE tentaikhoan = ? AND masp = ?");
        $stmt->bind_param("iss", $qty_update, $tentaikhoan, $masp_update);
        $stmt->execute();
        $stmt->close();
        echo "updated"; 
    }
    exit;
}

// =======================================================================
// 2. XỬ LÝ XÓA SẢN PHẨM (DÙNG SESSION THÔNG BÁO)
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_masp'])) {
    $masp = $_POST['delete_masp'];
    $query = "DELETE FROM giohang WHERE masp = ? AND tentaikhoan = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $masp, $tentaikhoan);
    
    if ($stmt->execute()) {
        $_SESSION['thongbao'] = "Đã xóa sản phẩm thành công!";
        $_SESSION['thongbao_type'] = "success"; // Màu xanh
    } else {
        $_SESSION['thongbao'] = "Lỗi khi xóa: " . $conn->error;
        $_SESSION['thongbao_type'] = "error"; // Màu đỏ (nếu cần)
    }
    $stmt->close();
    header("Location: giohang.php");
    exit;
}

// =======================================================================
// 3. XỬ LÝ THANH TOÁN (ĐÃ SỬA LỖI GIÁ + THÔNG BÁO XANH)
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xac_nhan_thanh_toan'])) {
    $hoten = $_POST['hoten'] ?? '';
    $diachi = $_POST['diachi'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $pttt = $_POST['pttt'] ?? '';
    
    $list_masp_str = $_POST['list_masp_mua'] ?? '';

    if (empty($list_masp_str)) {
        $_SESSION['thongbao'] = "Bạn chưa chọn sản phẩm nào!";
        $_SESSION['thongbao_type'] = "error";
        header("Location: giohang.php");
        exit;
    }

    $selected_masp_array = explode(',', $list_masp_str);
    $tongtien_hoadon = 0;
    $count_success = 0;

    foreach ($selected_masp_array as $masp_mua) {
        $masp_mua = trim($masp_mua);
        
        $query = "SELECT masp, tensp, gia, soluong FROM giohang WHERE tentaikhoan = ? AND masp = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $tentaikhoan, $masp_mua);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();

        if ($item) {
            // FIX GIÁ: Xóa dấu chấm
            $gia_clean = floatval(preg_replace('/[^0-9]/', '', $item['gia']));
            $soluong = intval($item['soluong']);
            $thanhTien = $gia_clean * $soluong;
            $tongtien_hoadon += $thanhTien;

            // INSERT HÓA ĐƠN
            $insert = $conn->prepare("INSERT INTO hoadon (tentaikhoan, masp, tensp, gia, soluong, tongtien, hoten, diachi, sdt, pttt, ngaylap, trangthai) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Chờ xử lý')");
            
            $insert->bind_param("sssdidssss", 
                $tentaikhoan, $item['masp'], $item['tensp'], $gia_clean, 
                $soluong, $thanhTien, $hoten, $diachi, $sdt, $pttt
            );
            
            if ($insert->execute()) {
                $count_success++;
                // Xóa khỏi giỏ
                $del = $conn->prepare("DELETE FROM giohang WHERE tentaikhoan = ? AND masp = ?");
                $del->bind_param("ss", $tentaikhoan, $masp_mua);
                $del->execute();
                $del->close();
            }
            $insert->close();
        }
    }

    if ($count_success > 0) {
        $_SESSION['thongbao'] = "Thanh toán thành công $count_success sản phẩm!";
        $_SESSION['thongbao_type'] = "success";
    } else {
        $_SESSION['thongbao'] = "Có lỗi xảy ra, vui lòng thử lại.";
        $_SESSION['thongbao_type'] = "error";
    }
    
    header("Location: giohang.php");
    exit;
}

// Load cart items
$query = "SELECT masp, tensp, gia, soluong, anh FROM giohang WHERE tentaikhoan = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tentaikhoan);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = [];
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
}
$stmt->close();
$conn->close();
?>

<?php include "head.php"; ?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS CŨ GIỮ NGUYÊN */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        .giohang-wrapper { background-color: #e5e0d8; min-height: 100vh; padding: 16px; }
        .cart-container { background-color: #fffefc; max-width: 1280px; margin: 0 auto; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .cart-header { display: grid; grid-template-columns: 1fr 4fr 2fr 2fr 2fr 1fr; text-align: center; font-weight: 600; border-bottom: 1px solid #d1d5db; padding-bottom: 12px; }
        .cart-item { display: grid; grid-template-columns: 1fr 4fr 2fr 2fr 2fr 1fr; align-items: center; padding: 16px 0; border-bottom: 1px solid #d1d5db; gap: 8px; }
        .cart-item img { width: 64px; height: 64px; object-fit: cover; border-radius: 6px; }
        .cart-item .form-checkbox { width: 20px; height: 20px; cursor: pointer; }
        .cart-item .quantity-input { border: 1px solid #d1d5db; border-radius: 6px; padding: 4px 8px; width: 80px; text-align: center; }
        .cart-item .thanh-tien { text-align: right; padding-right: 8px; }
        .cart-item .delete-btn { background-color: #cd853f; color: white; border: none; border-radius: 6px; padding: 4px 12px; cursor: pointer; }
        .total-section { margin-top: 24px; display: flex; flex-direction: column; gap: 16px; }
        .total-section .total-text { font-size: 18px; font-weight: 600; text-align: right; }
        .total-section .total-text span { color: #dc2626; }
        .total-section .checkout-btn { background-color: #cd853f; color: white; font-weight: 600; padding: 8px 24px; border: none; border-radius: 8px; cursor: pointer; width: 100%; text-align: center; }
        @media (min-width: 640px) { .total-section { flex-direction: row; justify-content: space-between; align-items: center; } .total-section .checkout-btn { width: auto; } }
        
        /* Modal CSS */
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); justify-content: center; align-items: center; }
        .modal-content { background-color: white; padding: 24px; border-radius: 8px; width: 90%; max-width: 420px; }
        .modal-content input, .modal-content select { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px; }
        .modal-actions { display: flex; justify-content: space-between; margin-top: 16px; }
        .modal-actions button { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .modal-actions button[type="submit"] { background-color: #cd853f; color: white; }
        .modal-actions button#closeModal { background-color: #ccc; }

        /* ========================================= */
        /* CSS MỚI CHO THÔNG BÁO (TOAST NOTIFICATION) */
        /* ========================================= */
        #toast {
            visibility: hidden; /* Mặc định ẩn */
            min-width: 250px;
            margin-left: -125px;
            background-color: #28a745; /* MÀU XANH LÁ CÂY */
            color: #fff; /* Chữ màu trắng */
            text-align: center;
            border-radius: 8px; /* Bo tròn góc */
            padding: 16px;
            position: fixed; /* Cố định trên màn hình */
            z-index: 9999;
            right: 30px; /* Cách lề phải 30px */
            top: 30px;   /* Cách lề trên 30px */
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2); /* Đổ bóng cho đẹp */
            opacity: 0;
            transition: opacity 0.5s, top 0.5s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Hiệu ứng khi hiện lên */
        #toast.show {
            visibility: visible;
            opacity: 1;
            top: 50px; /* Trượt nhẹ xuống */
        }

        #toast i {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div id="toast">
        <i class="fa-solid fa-circle-check"></i>
        <span id="toast-message">Nội dung thông báo</span>
    </div>

    <div class="giohang-wrapper">
        <div class="cart-container">
            <div class="cart-header">
                <div>CHỌN</div>
                <div>SẢN PHẨM</div>
                <div>GIÁ</div>
                <div>SỐ LƯỢNG</div>
                <div>THÀNH TIỀN</div>
                <div>XÓA</div>
            </div>

            <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="text-center">
                        <input type="checkbox" class="form-checkbox" value="<?= htmlspecialchars($item['masp']) ?>" onchange="updateTongTien()" />
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <?php
                        $anh = $item['anh'] ?? '';
                        $imgSrc = (empty($anh) || preg_match('/^https?:\\/\\//i', $anh)) ? ($anh ?: 'images/fallback.jpg') : 'anh/' . $anh;
                        ?>
                        <img src="<?= htmlspecialchars($imgSrc) ?>" onerror="this.src='images/fallback.jpg';" />
                        <span class="product-name"><?php echo htmlspecialchars($item['tensp'] ?? 'Sản phẩm'); ?></span>
                    </div>
                    <div class="gia" data-gia="<?php echo htmlspecialchars($item['gia'] ?? '0'); ?>">
                        <?php echo htmlspecialchars(number_format(floatval(preg_replace('/[^0-9]/', '', $item['gia'])), 0, ',', '.') . ' VNĐ'); ?>
                    </div>
                    
                    <div class="text-center">
                        <input type="number" class="quantity-input" 
                               value="<?php echo htmlspecialchars($item['soluong'] ?? '1'); ?>" 
                               min="1" 
                               data-masp="<?= htmlspecialchars($item['masp']) ?>"
                               onchange="updateQtyDatabase(this)" 
                               onkeyup="updateQtyDatabase(this)" />
                    </div>
                    
                    <div class="thanh-tien">
                        <?php
                        $gia = floatval(preg_replace("/[^0-9]/", "", $item['gia'] ?? 0));
                        $soluong = intval($item['soluong'] ?? 1);
                        echo number_format($gia * $soluong, 0, ',', '.') . ' VNĐ';
                        ?>
                    </div>
                    <div class="text-center">
                        <form method="post">
                            <input type="hidden" name="delete_masp" value="<?php echo htmlspecialchars($item['masp']); ?>" />
                            <button type="submit" class="delete-btn" onclick="return confirm('Xóa món này?');">XÓA</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="total-section">
                <div class="total-text">
                    TỔNG TIỀN: <span id="lblTongTien">0 VNĐ</span>
                </div>
                <button type="button" class="checkout-btn">THANH TOÁN</button>
            </div>
        </div>
    </div>

    <div id="checkoutModal" class="modal">
        <div class="modal-content">
            <h2>Thông tin thanh toán</h2>
            <form method="post" id="checkoutForm">
                <input type="hidden" name="list_masp_mua" id="hiddenListMaspMua">
                <label>Họ tên:</label> <input type="text" name="hoten" required>
                <label>Địa chỉ:</label> <input type="text" name="diachi" required>
                <label>SĐT:</label> <input type="text" name="sdt" required pattern="[0-9]{10,11}">
                <label>PTTT:</label> 
                <select name="pttt" required>
                    <option value="Tiền mặt">Tiền mặt</option>
                    <option value="Chuyển khoản">Chuyển khoản</option>
                </select>
                <div class="modal-actions">
                    <button type="submit" name="xac_nhan_thanh_toan">Xác nhận</button>
                    <button type="button" id="closeModal">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Hàm hiện thông báo
        function launchToast(message, type = 'success') {
            var x = document.getElementById("toast");
            var msg = document.getElementById("toast-message");
            msg.innerText = message;
            
            // Đổi màu nếu là lỗi
            if (type === 'error') {
                x.style.backgroundColor = "#dc3545"; // Màu đỏ
            } else {
                x.style.backgroundColor = "#28a745"; // Màu xanh
            }

            x.className = "show";
            // Tự động ẩn sau 3 giây
            setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
        }

        // Logic cập nhật số lượng
        function updateQtyDatabase(input) {
            let masp = input.getAttribute('data-masp');
            let newQty = parseInt(input.value);
            if(newQty < 1) { input.value = 1; newQty = 1; }
            var row = input.closest('.cart-item');
            var giaStr = row.querySelector('.gia').textContent || "0";
            var gia = parseFloat(giaStr.replace(/[^0-9]/g, "")) || 0;
            var thanhTien = gia * newQty;
            row.querySelector('.thanh-tien').textContent = thanhTien.toLocaleString('vi-VN') + ' VNĐ';
            updateTongTien();
            const formData = new FormData();
            formData.append('ajax_update_qty', '1');
            formData.append('masp', masp);
            formData.append('soluong', newQty);
            fetch('giohang.php', { method: 'POST', body: formData });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Kiểm tra xem PHP có gửi thông báo xuống không
            <?php if (isset($_SESSION['thongbao'])): ?>
                launchToast("<?= $_SESSION['thongbao'] ?>", "<?= isset($_SESSION['thongbao_type']) ? $_SESSION['thongbao_type'] : 'success' ?>");
            <?php 
                unset($_SESSION['thongbao']); 
                unset($_SESSION['thongbao_type']);
            ?>
            <?php endif; ?>

            const checkoutBtn = document.querySelector('.checkout-btn');
            const modal = document.getElementById('checkoutModal');
            const closeBtn = document.getElementById('closeModal');
            const hiddenInput = document.getElementById('hiddenListMaspMua');

            checkoutBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const checkedBoxes = document.querySelectorAll('.form-checkbox:checked');
                if (checkedBoxes.length === 0) {
                    launchToast('Vui lòng chọn sản phẩm!', 'error');
                    return;
                }
                let selectedIds = [];
                checkedBoxes.forEach(cb => selectedIds.push(cb.value));
                hiddenInput.value = selectedIds.join(',');
                modal.style.display = 'flex';
            });

            closeBtn.addEventListener('click', () => modal.style.display = 'none');
            window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
            updateTongTien();
        });

        function updateTongTien() {
            var tongTien = 0;
            var checkedBoxes = document.querySelectorAll('.form-checkbox:checked');
            checkedBoxes.forEach(function (checkbox) {
                var row = checkbox.closest('.cart-item');
                var thanhTienStr = row.querySelector('.thanh-tien').textContent.replace(/[^\d]/g, "");
                var thanhTien = parseFloat(thanhTienStr) || 0;
                tongTien += thanhTien;
            });
            document.getElementById('lblTongTien').textContent = tongTien.toLocaleString('vi-VN') + ' VNĐ';
        }
    </script>
</body>
</html>