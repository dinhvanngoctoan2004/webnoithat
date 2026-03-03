<?php
session_start();
include "head.php";

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['username'])) {
    header("Location: dangnhap.php");
    exit;
}

$tentaikhoan = $_SESSION['username'];
$conn = new mysqli('localhost', 'root', '', 'webnoithat');
$conn->set_charset("utf8");

// 2. LẤY THÔNG TIN NGƯỜI DÙNG TỪ CSDL (Để điền sẵn vào form)
$stmt_user = $conn->prepare("SELECT * FROM KhachHang WHERE taikhoan = ?");
$stmt_user->bind_param("s", $tentaikhoan);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// Các biến mặc định
$list_items = [];
$total_money = 0;
$source = ''; // Để biết nguồn từ 'buy_now' hay 'cart'

// 3. XỬ LÝ DỮ LIỆU ĐẦU VÀO (TỪ CHITIETSP HOẶC GIOHANG)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // TRƯỜNG HỢP A: MUA NGAY TỪ TRANG CHI TIẾT
    if (isset($_POST['buy_now_single'])) {
        $source = 'buy_now';
        $masp = $_POST['masp'];
        $soluong = intval($_POST['soluong']);
        
        // Lấy thông tin sản phẩm
        $stmt_sp = $conn->prepare("SELECT masp, tensp, gia, anh FROM sanpham WHERE masp = ?");
        $stmt_sp->bind_param("s", $masp);
        $stmt_sp->execute();
        $sp = $stmt_sp->get_result()->fetch_assoc();
        
        if ($sp) {
            $gia_clean = floatval(preg_replace('/[^0-9]/', '', $sp['gia']));
            $sp['soluong_mua'] = $soluong;
            $sp['thanh_tien'] = $gia_clean * $soluong;
            $total_money += $sp['thanh_tien'];
            $list_items[] = $sp;
        }
        $stmt_sp->close();
    }

    // TRƯỜNG HỢP B: THANH TOÁN TỪ GIỎ HÀNG
    elseif (isset($_POST['checkout_cart'])) {
        $source = 'cart';
        $list_masp_str = $_POST['list_masp']; // Chuỗi '1,2,5'
        $masp_array = explode(',', $list_masp_str);
        
        foreach ($masp_array as $masp) {
            $stmt_cart = $conn->prepare("SELECT masp, tensp, gia, soluong, anh FROM giohang WHERE tentaikhoan = ? AND masp = ?");
            $stmt_cart->bind_param("ss", $tentaikhoan, $masp);
            $stmt_cart->execute();
            $item = $stmt_cart->get_result()->fetch_assoc();
            
            if ($item) {
                $gia_clean = floatval(preg_replace('/[^0-9]/', '', $item['gia']));
                $item['soluong_mua'] = intval($item['soluong']); // Trong giỏ hàng cột là soluong
                $item['thanh_tien'] = $gia_clean * $item['soluong_mua'];
                $total_money += $item['thanh_tien'];
                $list_items[] = $item;
            }
            $stmt_cart->close();
        }
    }
    
    // TRƯỜNG HỢP C: XỬ LÝ LƯU ĐƠN HÀNG (KHI BẤM NÚT XÁC NHẬN Ở TRANG NÀY)
    elseif (isset($_POST['process_payment'])) {
      
        $items_json = $_POST['items_json']; 
        $list_items_process = json_decode($items_json, true);
        
        $hoten = $_POST['hoten'];
        $diachi = $_POST['diachi'];
        $sdt = $_POST['sdt'];
        $pttt = $_POST['pttt'];
        $minh_chung_path = null;

        // Xử lý ảnh minh chứng
        if ($pttt !== 'Tiền mặt khi nhận hàng' && isset($_FILES['minh_chung']) && $_FILES['minh_chung']['error'] == 0) {
            $target_dir = "uploads/minhchung/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $filename = time() . '_' . basename($_FILES["minh_chung"]["name"]);
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES["minh_chung"]["tmp_name"], $target_file)) {
                $minh_chung_path = $target_file;
            }
        }

        // INSERT VÀO DATABASE
        $sql_insert = "INSERT INTO hoadon (tentaikhoan, masp, tensp, gia, soluong, tongtien, hoten, diachi, sdt, pttt, ngaylap, trangthai, minh_chung) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Chờ xử lý', ?)";
        $stmt_ins = $conn->prepare($sql_insert);

        foreach ($list_items_process as $prod) {
            $gia_goc = floatval(preg_replace('/[^0-9]/', '', $prod['gia']));
            $stmt_ins->bind_param("sssdidsssss", 
                $tentaikhoan, $prod['masp'], $prod['tensp'], $gia_goc, $prod['soluong_mua'], $prod['thanh_tien'],
                $hoten, $diachi, $sdt, $pttt, $minh_chung_path
            );
            $stmt_ins->execute();

            // Nếu mua từ giỏ hàng thì xóa sản phẩm đó khỏi giỏ
            if ($_POST['origin_source'] === 'cart') {
                $conn->query("DELETE FROM giohang WHERE tentaikhoan='$tentaikhoan' AND masp='{$prod['masp']}'");
            }
        }
        $stmt_ins->close();

        // CHUYỂN HƯỚNG SANG TRANG ĐẶT HÀNG (LỊCH SỬ)
        echo "<script>alert('Đặt hàng thành công!'); window.location='dathang.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác nhận thanh toán</title>
    <style>
        body { background-color: #fefaf0; font-family: Arial, sans-serif; }
        .container { max-width: 1100px; margin: 30px auto; display: flex; gap: 30px; }
        .box { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .left-col { flex: 1.5; }
        .right-col { flex: 1; height: fit-content; }
        h2 { color: #7a5a00; border-bottom: 2px solid #e5c07b; padding-bottom: 10px; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        
        .order-item { display: flex; gap: 10px; border-bottom: 1px solid #eee; padding: 10px 0; }
        .order-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; }
        .order-info { flex: 1; }
        .total-row { font-size: 18px; font-weight: bold; margin-top: 20px; display: flex; justify-content: space-between; color: #d32f2f; }
        
        .btn-confirm { width: 100%; padding: 15px; background: #7B4B37; color: white; border: none; font-size: 16px; font-weight: bold; cursor: pointer; border-radius: 5px; margin-top: 20px; }
        .btn-confirm:hover { background: #5e392a; }

        /* QR Code Section */
        #paymentInfo { background:#f8f9fa; padding:15px; border-radius:8px; border:1px dashed #7B4B37; margin-top:15px; text-align:center; display:none; }
        #qrImage { max-width: 200px; border-radius:8px; border:1px solid #ddd; margin: 10px 0; }
    </style>
</head>
<body>

<div class="container">
    <div class="left-col box">
        <h2>Thông tin giao hàng</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="process_payment" value="1">
            <input type="hidden" name="items_json" value='<?= json_encode($list_items) ?>'>
            <input type="hidden" name="origin_source" value='<?= $source ?>'>

            <div class="form-group">
                <label>Họ và tên người nhận</label>
                <input type="text" name="hoten" required value="<?= htmlspecialchars($tentaikhoan) ?>"> 
            </div>
            
            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="text" name="sdt" required pattern="[0-9]{10,11}" value="<?= htmlspecialchars($user_info['sdt'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Địa chỉ giao hàng</label>
                <input type="text" name="diachi" required value="<?= htmlspecialchars($user_info['diachi'] ?? '') ?>">
            </div>

            <h2 style="margin-top: 30px;">Phương thức thanh toán</h2>
            <div class="form-group">
                <select name="pttt" id="paymentMethod" required>
                    <option value="Tiền mặt khi nhận hàng">Thanh toán khi nhận hàng (COD)</option>
                    <option value="Chuyển khoản ngân hàng">Chuyển khoản ngân hàng</option>
                    <option value="Ví điện tử">Ví điện tử (Momo/ZaloPay)</option>
                </select>
            </div>

            <div id="paymentInfo">
                <p style="font-weight:bold; color:#7B4B37; margin:0;">Quét mã để thanh toán</p>
                <img id="qrImage" src="" alt="QR Code">
                <p style="font-size:14px;">Số tiền cần thanh toán: <b style="color:red"><?= number_format($total_money, 0, ',', '.') ?> VNĐ</b></p>
                <p id="bankNote" style="font-size:12px; margin-bottom:10px;">Nội dung CK: <b>THANH TOAN DON HANG</b></p>
                
                <label style="font-weight:bold; display:block; text-align:left; margin-bottom:5px;">Tải lên ảnh bằng chứng:</label>
                <input type="file" name="minh_chung" id="proofFile" accept="image/*" style="width:100%;">
            </div>

            <button type="submit" class="btn-confirm">XÁC NHẬN THANH TOÁN</button>
        </form>
    </div>

    <div class="right-col box">
        <h2>Đơn hàng của bạn</h2>
        <?php if (!empty($list_items)): ?>
            <?php foreach ($list_items as $item): ?>
                <div class="order-item">
                    <?php 
                        $anh = $item['anh'] ?? '';
                        $imgSrc = (empty($anh) || preg_match('/^https?:\\/\\//i', $anh)) ? ($anh ?: 'images/fallback.jpg') : 'anh/' . $anh;
                    ?>
                    <img src="<?= $imgSrc ?>" alt="SP">
                    <div class="order-info">
                        <strong><?= htmlspecialchars($item['tensp']) ?></strong><br>
                        <small>Số lượng: <?= $item['soluong_mua'] ?></small><br>
                        <small>Giá: <?= htmlspecialchars($item['gia']) ?></small>
                    </div>
                    <div><?= number_format($item['thanh_tien'], 0, ',', '.') ?>đ</div>
                </div>
            <?php endforeach; ?>
            
            <div class="total-row">
                <span>Tổng cộng:</span>
                <span><?= number_format($total_money, 0, ',', '.') ?> VNĐ</span>
            </div>
        <?php else: ?>
            <p>Không có sản phẩm nào để thanh toán.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // Cấu hình ngân hàng
    const MY_BANK_ID = 'MB'; // Mã ngân hàng (VD: MB, VCB, ACB...)
    const MY_ACCOUNT_NO = '0000000000'; // Số tài khoản của bạn
    const TOTAL_AMOUNT = <?= $total_money ?>; // Lấy tổng tiền từ PHP

    const paymentSelect = document.getElementById('paymentMethod');
    const paymentInfo = document.getElementById('paymentInfo');
    const qrImage = document.getElementById('qrImage');
    const proofFile = document.getElementById('proofFile');

    paymentSelect.addEventListener('change', function() {
        if (this.value === 'Tiền mặt khi nhận hàng') {
            paymentInfo.style.display = 'none';
            proofFile.required = false;
        } else {
            paymentInfo.style.display = 'block';
            proofFile.required = true; // Bắt buộc up ảnh

            // Tạo link VietQR
            let qrSource = `https://img.vietqr.io/image/${MY_BANK_ID}-${MY_ACCOUNT_NO}-compact.jpg?amount=${TOTAL_AMOUNT}&addInfo=THANH TOAN DON HANG`;
            qrImage.src = qrSource;
        }
    });
</script>

</body>
</html>