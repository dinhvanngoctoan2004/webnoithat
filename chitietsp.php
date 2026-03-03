<?php
ob_start(); // Bật đệm đầu ra
if (session_status() === PHP_SESSION_NONE) session_start();

$conn = new mysqli('localhost', 'root', '', 'webnoithat');
$conn->set_charset("utf8");

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$msp = $_GET['masp'] ?? '';
$tentaikhoan = $_SESSION['username'] ?? null;

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("SELECT tensp, gia, chatlieu, mau, hinhthuc, mota, anh FROM sanpham WHERE masp = ?");
$stmt->bind_param("s", $msp);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

// -------------------- XỬ LÝ THÊM GIỎ HÀNG --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$tentaikhoan) {
        $_SESSION['thongbao'] = "Bạn cần đăng nhập để thêm vào giỏ!";
        $_SESSION['thongbao_type'] = "error";
        header("Location: dangnhap.php");
        exit;
    }

    $soluong = $_POST['soluong'] ?? 1;

    $check = $conn->prepare("SELECT soluong FROM giohang WHERE tentaikhoan = ? AND masp = ?");
    $check->bind_param("ss", $tentaikhoan, $msp);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $newQty = $row['soluong'] + $soluong;
        $update = $conn->prepare("UPDATE giohang SET soluong = ? WHERE tentaikhoan = ? AND masp = ?");
        $update->bind_param("iss", $newQty, $tentaikhoan, $msp);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO giohang (tentaikhoan, masp, tensp, gia, soluong, anh)
                                  VALUES (?, ?, ?, ?, ?, ?)");
        $insert->bind_param("sssdis", $tentaikhoan, $msp, $product['tensp'], $product['gia'], $soluong, $product['anh']);
        $insert->execute();
        $insert->close();
    }
    $check->close();

    $_SESSION['thongbao'] = "Đã thêm vào giỏ hàng thành công!";
    $_SESSION['thongbao_type'] = "success";
    header("Location: chitietsp.php?masp=" . $msp);
    exit;
}

// -------------------- XỬ LÝ GỬI BÌNH LUẬN --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!$tentaikhoan) {
        $_SESSION['thongbao'] = "Bạn cần đăng nhập để đánh giá!";
        $_SESSION['thongbao_type'] = "error";
        header("Location: dangnhap.php");
        exit;
    }

    $sosao = intval($_POST['sosao']);
    $noidung = trim($_POST['noidung']);

    $checkPurchase = $conn->prepare("SELECT 1 FROM hoadon WHERE tentaikhoan = ? AND masp = ? LIMIT 1");
    $checkPurchase->bind_param("ss", $tentaikhoan, $msp);
    $checkPurchase->execute();
    $purchased = $checkPurchase->get_result()->num_rows > 0;
    $checkPurchase->close();

    if (!$purchased) {
        $_SESSION['thongbao'] = "Bạn phải mua hàng mới được đánh giá!";
        $_SESSION['thongbao_type'] = "error";
    } elseif ($sosao >= 1 && $sosao <= 5 && $noidung != '') {
        $stmt = $conn->prepare("INSERT INTO danhgia (masp, tentaikhoan, sosao, noidung) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $msp, $tentaikhoan, $sosao, $noidung);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['thongbao'] = "Cảm ơn bạn đã đánh giá!";
        $_SESSION['thongbao_type'] = "success";
    } else {
        $_SESSION['thongbao'] = "Vui lòng nhập nội dung đánh giá.";
        $_SESSION['thongbao_type'] = "error";
    }
    
    header("Location: chitietsp.php?masp=" . $msp);
    exit;
}
?>

<?php include "head.php"; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Chi tiết sản phẩm</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
    :root { --primary-color: #7B4B37; --primary-color-light: #A67C68; --background-color: #e2ddcf; }
    body { background-color: var(--background-color); }
    .container { max-width: 1200px; margin: 40px auto; margin-bottom: 40px; padding: 20px; background-color: #fffaf1; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
    .product-container { display: flex; gap: 40px; flex-wrap: wrap; }
    .product-image { flex: 1; aspect-ratio: 4 / 3; background: #f5f5f5; display: flex; align-items: center; justify-content: center; border-radius: 16px; overflow: hidden; position: relative; }
    .product-image img { width: 100%; height: 100%; object-fit: cover; object-position: center; display: block; transition: opacity 0.3s ease; background: #fff; }
    .product-info { flex: 1; }
    .product-title { font-size: 24px; margin-bottom: 10px; color: #000; font-weight: bold; }
    .product-price { color: #000; font-size: 32px; font-weight: bold; margin-bottom: 15px; }
    .discount { background-color: var(--primary-color-light); color: white; display: inline-block; padding: 4px 8px; border-radius: 6px; margin-bottom: 15px; font-weight: bold; }
    .specifications { margin-bottom: 15px; }
    .specifications h3 { font-size: 20px; margin-bottom: 10px; color: var(--primary-color); }
    .specifications table { width: 100%; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 0 5px rgba(0, 0, 0, 0.1); }
    .specifications th, .specifications td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    .specifications th { background-color: var(--primary-color-light); color: white; }
    .action-group { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
    .quantity-cart-group { display: flex; gap: 10px; }
    .quantity-input { padding: 8px; border-radius: 8px; border: 1px solid #ccc; flex: 1; }
    .add-to-cart { background: none; border: 2px solid var(--primary-color); color: var(--primary-color); padding: 12px; font-size: 16px; cursor: pointer; flex: 2; text-align: center; border-radius: 8px; }
    .add-to-cart:hover { background-color: var(--primary-color-light); color: white; }
    .buy-now { background-color: #7B4B37; border: 2px solid #7B4B37; color: #fff; padding: 12px; font-size: 16px; font-weight: bold; cursor: pointer; width: 100%; text-align: center; border-radius: 8px; transition: all 0.3s ease; }
    .buy-now:hover { background-color: #A67C68; border-color: #A67C68; transform: scale(1.03); }

    /* CSS thông báo */
    #toast {
        visibility: hidden;
        min-width: 250px;
        margin-left: -125px;
        background-color: #28a745;
        color: #fff;
        text-align: center;
        border-radius: 8px;
        padding: 16px;
        position: fixed;
        z-index: 10000;
        right: 30px;
        top: 30px;
        font-size: 16px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        opacity: 0;
        transition: opacity 0.5s, top 0.5s;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    #toast.show { visibility: visible; opacity: 1; top: 50px; }
    #toast i { font-size: 20px; }
</style>
</head>

<body>
    <div id="toast">
        <i class="fa-solid fa-circle-check"></i>
        <span id="toast-message">Thông báo</span>
    </div>

    <div class="container">
        <div class="product-container">
            <div class="product-image">
                <img src="<?= htmlspecialchars($product['anh'] ?? 'images/fallback.jpg') ?>" alt="Ảnh sản phẩm">
            </div>
            <div class="product-info">
                <h1 class="product-title"><?= htmlspecialchars($product['tensp'] ?? '') ?></h1>
                <div class="product-price"><?= number_format($product['gia'], 0, ',', '.') ?> VNĐ</div>
                <div class="discount">MÃ GIẢM GIÁ: 1K</div>

                <div class="specifications">
                    <h3>THÔNG SỐ KỸ THUẬT</h3>
                    <table>
                        <tr><th>Chất liệu</th><td><?php echo htmlspecialchars($product['chatlieu'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Màu sắc</th><td><?php echo htmlspecialchars($product['mau'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Hình thức</th><td><?php echo htmlspecialchars($product['hinhthuc'] ?? 'N/A'); ?></td></tr>
                    </table>
                </div>

                <div class="action-group">
                    <form method="post" style="display:flex; gap:10px; width: 100%;">
                        <input type="number" class="quantity-input" name="soluong" value="1" min="1">
                        <button type="submit" name="add_to_cart" class="add-to-cart">THÊM VÀO GIỎ HÀNG</button>
                    </form>
                    
                    <form action="xacnhanthanhtoan.php" method="POST" style="margin-top: 10px; width: 100%;">
                        <input type="hidden" name="buy_now_single" value="1">
                        <input type="hidden" name="masp" value="<?= $msp ?>">
                        <input type="hidden" name="soluong" id="hiddenSoluongMuaNgay" value="1">
                        <button type="submit" class="buy-now">MUA NGAY</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <hr style="margin: 40px 0;">

    <div class="container" style="margin-top:40px;">
      <div class="reviews-section">
        <h2 style="color:#7B4B37;margin-bottom:20px;text-align:center;">Đánh giá & Bình luận</h2>

        <?php
        $reviews = $conn->prepare("SELECT tentaikhoan, sosao, noidung, ngaydang FROM danhgia WHERE masp = ? AND trangthai='Hiển thị' ORDER BY ngaydang DESC");
        $reviews->bind_param("s", $msp);
        $reviews->execute();
        $result = $reviews->get_result();
        ?>

        <form method="post" class="review-form" style="margin:20px auto;max-width:700px;background:#fffaf1;border:1px solid #ddd;border-radius:12px;padding:20px;">
          <h3 style="margin-bottom:10px;color:#7B4B37;">Gửi đánh giá của bạn:</h3>
          <label for="sosao" style="font-weight:bold;">Số sao:</label>
          <select name="sosao" id="sosao" required style="margin:5px 0 15px;padding:8px;border-radius:8px;border:1px solid #ccc;width:100%;">
            <option value="5">★★★★★ (5 sao)</option>
            <option value="4">★★★★☆ (4 sao)</option>
            <option value="3">★★★☆☆ (3 sao)</option>
            <option value="2">★★☆☆☆ (2 sao)</option>
            <option value="1">★☆☆☆☆ (1 sao)</option>
          </select>

          <textarea name="noidung" rows="4" placeholder="Nhập nội dung bình luận..." required
                    style="width:100%;border-radius:8px;border:1px solid #ccc;padding:10px;"></textarea><br>
          <button type="submit" name="submit_review"
                  style="margin-top:10px;padding:10px 20px;background-color:#7B4B37;color:white;border:none;border-radius:8px;cursor:pointer;width:100%;">
            Gửi đánh giá
          </button>
        </form>

        <div class="review-list" style="margin-top:30px;">
          <h3 style="color:#7B4B37;margin-bottom:10px;text-align:center;">Nhận xét của khách hàng</h3>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <div class="review-item" style="background:#fff;border:1px solid #ddd;border-radius:10px;padding:15px;margin:10px auto;max-width:700px;">
                <strong style="color:#7B4B37;"><?= htmlspecialchars($row['tentaikhoan']) ?></strong>
                <div style="color:#E2B007;margin:5px 0;">
                  <?= str_repeat('⭐', $row['sosao']) ?>
                </div>
                <p style="margin:5px 0;"><?= htmlspecialchars($row['noidung']) ?></p>
                <small style="color:#777;"><?= date('d/m/Y H:i', strtotime($row['ngaydang'])) ?></small>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p style="text-align:center;color:#555;">Chưa có đánh giá nào cho sản phẩm này.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php $conn->close(); ?>

    <script>
    // Hàm hiển thị thông báo
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
        setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
    }

    // Logic đồng bộ số lượng và hiển thị thông báo PHP
    document.addEventListener('DOMContentLoaded', () => {
        // Hiện thông báo PHP nếu có
        <?php if (isset($_SESSION['thongbao'])): ?>
            launchToast("<?= $_SESSION['thongbao'] ?>", "<?= isset($_SESSION['thongbao_type']) ? $_SESSION['thongbao_type'] : 'success' ?>");
        <?php 
            unset($_SESSION['thongbao']); 
            unset($_SESSION['thongbao_type']);
        ?>
        <?php endif; ?>

        // Cập nhật số lượng cho form Mua Ngay
        const qtyInput = document.querySelector('.quantity-input');
        const hiddenQty = document.getElementById('hiddenSoluongMuaNgay');
        
        if(qtyInput && hiddenQty) {
            qtyInput.addEventListener('change', function() {
                hiddenQty.value = this.value;
            });
            qtyInput.addEventListener('input', function() {
                hiddenQty.value = this.value;
            });
        }
    });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>