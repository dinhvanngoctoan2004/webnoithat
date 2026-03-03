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
// 2. XỬ LÝ XÓA SẢN PHẨM
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_masp'])) {
    $masp = $_POST['delete_masp'];
    $query = "DELETE FROM giohang WHERE masp = ? AND tentaikhoan = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $masp, $tentaikhoan);

    if ($stmt->execute()) {
        $_SESSION['thongbao'] = "Đã xóa sản phẩm thành công!";
        $_SESSION['thongbao_type'] = "success";
    } else {
        $_SESSION['thongbao'] = "Lỗi khi xóa: " . $conn->error;
        $_SESSION['thongbao_type'] = "error";
    }
    $stmt->close();
    header("Location: giohang.php");
    exit;
}

// =======================================================================
// 3. XỬ LÝ THANH TOÁN (CÓ UPLOAD ẢNH)
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xac_nhan_thanh_toan'])) {
    // $hoten = $_POST['hoten'] ?? '';
    // $diachi = $_POST['diachi'] ?? '';
    // $sdt = $_POST['sdt'] ?? '';
    // $pttt = $_POST['pttt'] ?? ''; // Tiền mặt hoặc Chuyển khoản
    // $list_masp_str = $_POST['list_masp_mua'] ?? '';
    
    // // --- XỬ LÝ UPLOAD ẢNH MINH CHỨNG ---
    // $minh_chung_path = null;
    // if ($pttt === 'Chuyển khoản') {
    //     if (isset($_FILES['minh_chung']) && $_FILES['minh_chung']['error'] == 0) {
    //         $target_dir = "uploads/minhchung/";
    //         if (!file_exists($target_dir)) {
    //             mkdir($target_dir, 0777, true);
    //         }
    //         // Đặt tên file unique
    //         $filename = time() . '_' . basename($_FILES["minh_chung"]["name"]);
    //         $target_file = $target_dir . $filename;
    //         $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    //         $allow_types = array('jpg', 'png', 'jpeg', 'gif');

    //         if (in_array($imageFileType, $allow_types)) {
    //             if (move_uploaded_file($_FILES["minh_chung"]["tmp_name"], $target_file)) {
    //                 $minh_chung_path = $target_file;
    //             } else {
    //                 $_SESSION['thongbao'] = "Lỗi khi tải ảnh lên server.";
    //                 $_SESSION['thongbao_type'] = "error";
    //                 header("Location: giohang.php"); exit;
    //             }
    //         } else {
    //             $_SESSION['thongbao'] = "Chỉ chấp nhận file ảnh (JPG, PNG).";
    //             $_SESSION['thongbao_type'] = "error";
    //             header("Location: giohang.php"); exit;
    //         }
    //     } else {
    //         // Nếu chọn chuyển khoản mà không up ảnh
    //         $_SESSION['thongbao'] = "Vui lòng tải lên ảnh minh chứng!";
    //         $_SESSION['thongbao_type'] = "error";
    //         header("Location: giohang.php"); exit;
    //     }
    // }

    // if (empty($list_masp_str)) {
    //     $_SESSION['thongbao'] = "Bạn chưa chọn sản phẩm nào!";
    //     $_SESSION['thongbao_type'] = "error";
    //     header("Location: giohang.php");
    //     exit;
    // }

    $selected_masp_array = explode(',', $list_masp_str);
    $tongtien_hoadon = 0;
    $count_success = 0;

    // Chuẩn bị câu SQL Insert (Thêm cột minh_chung)
    // Lưu ý: Đảm bảo bảng hoadon đã có cột minh_chung
    $sql_insert = "INSERT INTO hoadon (tentaikhoan, masp, tensp, gia, soluong, tongtien, hoten, diachi, sdt, pttt, ngaylap, trangthai, minh_chung) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Chờ xử lý', ?)";
    $insert = $conn->prepare($sql_insert);

    // foreach ($selected_masp_array as $masp_mua) {
    //     $masp_mua = trim($masp_mua);

    //     $query = "SELECT masp, tensp, gia, soluong FROM giohang WHERE tentaikhoan = ? AND masp = ?";
    //     $stmt = $conn->prepare($query);
    //     $stmt->bind_param("ss", $tentaikhoan, $masp_mua);
    //     $stmt->execute();
    //     $result = $stmt->get_result();
    //     $item = $result->fetch_assoc();
    //     $stmt->close();

    //     if ($item) {
    //         $gia_clean = floatval(preg_replace('/[^0-9]/', '', $item['gia']));
    //         $soluong = intval($item['soluong']);
    //         $thanhTien = $gia_clean * $soluong;
    //         $tongtien_hoadon += $thanhTien;

    //         // Bind param và thực thi Insert
    //         $insert->bind_param(
    //             "sssdidsssss", // Thêm 1 chữ 's' ở cuối cho minh_chung
    //             $tentaikhoan,
    //             $item['masp'],
    //             $item['tensp'],
    //             $gia_clean,
    //             $soluong,
    //             $thanhTien,
    //             $hoten,
    //             $diachi,
    //             $sdt,
    //             $pttt,
    //             $minh_chung_path // Lưu đường dẫn ảnh (hoặc null)
    //         );

    //         if ($insert->execute()) {
    //             $count_success++;
    //             // Xóa khỏi giỏ
    //             $del = $conn->prepare("DELETE FROM giohang WHERE tentaikhoan = ? AND masp = ?");
    //             $del->bind_param("ss", $tentaikhoan, $masp_mua);
    //             $del->execute();
    //             $del->close();
    //         }
    //     }
    // }
    // $insert->close();

    // if ($count_success > 0) {
    //     $_SESSION['thongbao'] = "Thanh toán thành công $count_success sản phẩm!";
    //     $_SESSION['thongbao_type'] = "success";
    // } else {
    //     $_SESSION['thongbao'] = "Có lỗi xảy ra, vui lòng thử lại.";
    //     $_SESSION['thongbao_type'] = "error";
    // }

    // header("Location: giohang.php");
    // exit;
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
        
        @media (min-width: 640px) {
            .total-section { flex-direction: row; justify-content: space-between; align-items: center; }
            .total-section .checkout-btn { width: auto; }
        }

        /* Modal CSS */
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.4); justify-content: center; align-items: center; }
        .modal-content { background-color: white; padding: 24px; border-radius: 8px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
        .modal-content input[type="text"], .modal-content select { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px; }
        
        .modal-actions { display: flex; justify-content: space-between; margin-top: 16px; }
        .modal-actions button { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .modal-actions button[type="submit"] { background-color: #cd853f; color: white; }
        .modal-actions button#closeModal { background-color: #ccc; }

        /* CSS Thông báo */
        #toast { visibility: hidden; min-width: 250px; margin-left: -125px; background-color: #28a745; color: #fff; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 9999; right: 30px; top: 30px; font-size: 16px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); opacity: 0; transition: opacity 0.5s, top 0.5s; display: flex; align-items: center; gap: 10px; }
        #toast.show { visibility: visible; opacity: 1; top: 50px; }
        #toast i { font-size: 20px; }

        /* CSS Checkbox & Center */
        .text-center { display: flex; justify-content: center; align-items: center; }
        .cart-item .form-checkbox { width: 24px; height: 24px; cursor: pointer; accent-color: #cd853f; margin: 0 auto; -webkit-appearance: checkbox; appearance: auto; display: inline-block; }

        /* CSS CHO PHẦN QR & UPLOAD TRONG MODAL */
        #paymentInfo { background:#f8f9fa; padding:15px; border-radius:8px; border:1px dashed #7B4B37; margin-top:15px; text-align:center; display: none; }
        #qrImage { max-width: 200px; border-radius:8px; border:1px solid #ddd; margin: 10px 0; }
        #proofFile { margin-top: 10px; width: 100%; }
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
                
                <form action="xacnhanthanhtoan.php" method="POST" id="formCheckoutCart">
                    <input type="hidden" name="checkout_cart" value="1">
                    <input type="hidden" name="list_masp" id="inputListMasp">
                    <button type="button" class="checkout-btn" onclick="submitCheckout()">THANH TOÁN</button>
                </form>
            </div>
        </div>
    </div>

    
    <!-- <div id="checkoutModal" class="modal">
        <div class="modal-content">
            <h2 style="text-align:center; color:#7B4B37;">Thông tin thanh toán</h2>
            <form method="post" id="checkoutForm" enctype="multipart/form-data">
                <input type="hidden" name="list_masp_mua" id="hiddenListMaspMua">
                
                <label>Họ tên:</label> <input type="text" name="hoten" required>
                <label>Địa chỉ:</label> <input type="text" name="diachi" required>
                <label>SĐT:</label> <input type="text" name="sdt" required pattern="[0-9]{10,11}">
                
                <label>Phương thức thanh toán:</label>
                <select name="pttt" id="paymentMethod" required>
                    <option value="Tiền mặt">Tiền mặt khi nhận hàng</option>
                    <option value="Chuyển khoản">Chuyển khoản ngân hàng</option>
                </select>

                <div id="paymentInfo">
                    <p style="font-weight:bold; color:#7B4B37; margin:0;">Quét mã QR để thanh toán</p>
                    <img id="qrImage" src="" alt="QR Code">
                    
                    <p id="bankNote" style="font-size:12px; margin-bottom:10px;">
                        Số tiền: <b id="displayAmount" style="color:red; font-size:16px;">0 đ</b><br>
                        Nội dung: <b>THANH TOAN DON HANG</b>
                    </p>
                    
                    <label style="font-weight:bold; display:block; text-align:left; margin-bottom:5px;">Tải lên ảnh bằng chứng:</label>
                    <input type="file" name="minh_chung" id="proofFile" accept="image/*">
                </div>

                <div class="modal-actions">
                    <button type="submit" name="xac_nhan_thanh_toan">Xác nhận</button>
                    <button type="button" id="closeModal">Hủy</button>
                </div>
            </form>
        </div>
    </div> -->

    <script>
        // --- CẤU HÌNH THÔNG TIN NGÂN HÀNG CỦA BẠN TẠI ĐÂY ---
        const MY_BANK_ID = 'MB'; // Ví dụ: MB, VCB, ACB... (Tra mã ngân hàng VietQR)
        const MY_ACCOUNT_NO = '0000000000'; // Số tài khoản của bạn

        function launchToast(message, type = 'success') {
            var x = document.getElementById("toast");
            var msg = document.getElementById("toast-message");
            var icon = x.querySelector('i');
            msg.innerText = message;
            if (type === 'error') {
                x.style.backgroundColor = "#dc3545"; 
                icon.className = "fa-solid fa-circle-exclamation";
            } else {
                x.style.backgroundColor = "#28a745";
                icon.className = "fa-solid fa-circle-check";
            }
            x.className = "show";
            setTimeout(function() { x.className = x.className.replace("show", ""); }, 3000);
        }

        function updateQtyDatabase(input) {
            let masp = input.getAttribute('data-masp');
            let newQty = parseInt(input.value);
            if (newQty < 1) { input.value = 1; newQty = 1; }
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

        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['thongbao'])): ?>
                launchToast("<?= $_SESSION['thongbao'] ?>", "<?= isset($_SESSION['thongbao_type']) ? $_SESSION['thongbao_type'] : 'success' ?>");
                <?php unset($_SESSION['thongbao']); unset($_SESSION['thongbao_type']); ?>
            <?php endif; ?>

            const checkoutBtn = document.querySelector('.checkout-btn');
            const modal = document.getElementById('checkoutModal');
            const closeBtn = document.getElementById('closeModal');
            const hiddenInput = document.getElementById('hiddenListMaspMua');
            
            // Logic cho QR Code
            const paymentSelect = document.getElementById('paymentMethod');
            const paymentInfo = document.getElementById('paymentInfo');
            const qrImage = document.getElementById('qrImage');
            const proofFile = document.getElementById('proofFile');
            const displayAmount = document.getElementById('displayAmount');
            const rawTotalMoney = document.getElementById('rawTotalMoney');

            checkoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const checkedBoxes = document.querySelectorAll('.form-checkbox:checked');
                if (checkedBoxes.length === 0) {
                    launchToast('Vui lòng chọn sản phẩm!', 'error');
                    return;
                }
                let selectedIds = [];
                checkedBoxes.forEach(cb => selectedIds.push(cb.value));
                hiddenInput.value = selectedIds.join(',');
                
                // Reset form khi mở
                paymentSelect.value = "Tiền mặt";
                paymentInfo.style.display = 'none';
                proofFile.required = false;

                modal.style.display = 'flex';
            });

            // SỰ KIỆN THAY ĐỔI PTTT
            paymentSelect.addEventListener('change', function() {
                if (this.value === 'Chuyển khoản') {
                    paymentInfo.style.display = 'block';
                    proofFile.required = true; // Bắt buộc up ảnh

                    // Lấy tổng tiền số nguyên từ input ẩn
                    let amount = rawTotalMoney.value; 
                    
                    // Tạo link QR VietQR động theo số tiền
                    // Format: https://img.vietqr.io/image/[BANK]-[ACC]-compact.jpg?amount=[TIEN]&addInfo=[NOIDUNG]
                    let qrSource = `https://img.vietqr.io/image/${MY_BANK_ID}-${MY_ACCOUNT_NO}-compact.jpg?amount=${amount}&addInfo=THANH TOAN DON HANG`;
                    
                    qrImage.src = qrSource;
                    displayAmount.innerText = parseInt(amount).toLocaleString('vi-VN') + " đ";

                } else {
                    paymentInfo.style.display = 'none';
                    proofFile.required = false;
                }
            });

            closeBtn.addEventListener('click', () => modal.style.display = 'none');
            window.addEventListener('click', (e) => {
                if (e.target === modal) modal.style.display = 'none';
            });
            updateTongTien();
        });

        function updateTongTien() {
            var tongTien = 0;
            var checkedBoxes = document.querySelectorAll('.form-checkbox:checked');
            checkedBoxes.forEach(function(checkbox) {
                var row = checkbox.closest('.cart-item');
                var thanhTienStr = row.querySelector('.thanh-tien').textContent.replace(/[^\d]/g, "");
                var thanhTien = parseFloat(thanhTienStr) || 0;
                tongTien += thanhTien;
            });
            // Cập nhật Text hiển thị
            document.getElementById('lblTongTien').textContent = tongTien.toLocaleString('vi-VN') + ' VNĐ';
            // Cập nhật giá trị ẩn để dùng cho QR Code
            document.getElementById('rawTotalMoney').value = tongTien;
        }


        function submitCheckout() {
    const checkedBoxes = document.querySelectorAll('.form-checkbox:checked');
    if (checkedBoxes.length === 0) {
        // Dùng hàm toast cũ của bạn nếu còn giữ, hoặc alert
        alert('Vui lòng chọn sản phẩm để thanh toán!');
        return;
    }

    let selectedIds = [];
    checkedBoxes.forEach(cb => selectedIds.push(cb.value));
    
    // Gán danh sách ID vào input ẩn
    document.getElementById('inputListMasp').value = selectedIds.join(',');
    
    // Submit form chuyển sang trang xác nhận
    document.getElementById('formCheckoutCart').submit();
}
    </script>
</body>
</html>