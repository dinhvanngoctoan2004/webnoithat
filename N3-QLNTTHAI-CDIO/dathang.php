<?php include "head.php"; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu chưa đăng nhập thì quay lại đăng nhập
if (!isset($_SESSION['username'])) {
    header("Location: dangnhap.php");
    exit;
}

$tentaikhoan = $_SESSION['username'];
$conn = new mysqli('localhost', 'root', '', 'webnoithat');
$conn->set_charset("utf8");

// --- XỬ LÝ MUA HÀNG ---
if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']); // Lấy ID sản phẩm từ trên thanh địa chỉ
    
    // Tìm sản phẩm theo ID
    $result = $conn->query("SELECT * FROM products WHERE id = $product_id");
    $sp = $result->fetch_assoc();

    if ($sp) { // Bỏ check quantity tạm thời để test cho dễ, bạn có thể thêm lại && $sp['quantity'] > 0 sau
        $hoten = "Người dùng " . $tentaikhoan;
        $diachi = "Chưa cập nhật";
        $sdt = "Chưa cập nhật";
        $pttt = "Thanh toán khi nhận hàng";

        // === SỬA LỖI 1: LẤY ĐÚNG TÊN CỘT 'gia' TRONG DATABASE ===
        // Trong ảnh bạn gửi, cột tên là 'gia', không phải 'price'
        $raw_price = isset($sp['gia']) ? $sp['gia'] : 0; 

        // Xử lý làm sạch giá (Xóa dấu chấm, phẩy, chữ)
        // Ví dụ: "18.990.000" -> thành "18990000"
        $dongia = preg_replace('/[^0-9]/', '', $raw_price); 
        
        // Nếu giá rỗng hoặc lỗi thì set mặc định bằng 0 để tránh lỗi SQL
        if ($dongia == '') $dongia = 0;

        $soluong = 1;
        $tongtien = floatval($dongia) * $soluong; 

        // === SỬA LỖI 2: MASP ===
        // Lưu $product_id (số) vào cột masp để tránh lỗi về 0 do lệch kiểu dữ liệu
        
        $sql = "INSERT INTO hoadon (tentaikhoan, masp, tensp, gia, soluong, tongtien, hoten, diachi, sdt, pttt, ngaylap, trangthai) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Chờ xử lý')";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // Bind param: 
            // - $dongia và $tongtien: để kiểu 's' (string) cho an toàn với số lớn
            // - $product_id: để kiểu 'i' (int) vì masp trong hoadon là int
            $stmt->bind_param("sississsss", 
                $tentaikhoan,   // s
                $product_id,    // i (Lưu ID sản phẩm vào cột masp)
                $sp['tensp'],   // s (Sửa thành tensp cho khớp, nếu bảng products là 'name' thì đổi lại)
                $dongia,        // s (Giá dạng chuỗi số sạch)
                $soluong,       // i
                $tongtien,      // s (Tổng tiền dạng chuỗi)
                $hoten,         // s
                $diachi,        // s
                $sdt,           // s
                $pttt           // s
            );
            
            $stmt->execute();
            $stmt->close();

            // Trừ kho (nếu có cột quantity) - kiểm tra lại tên cột trong bảng products
            // $conn->query("UPDATE products SET quantity = quantity - 1 WHERE id = $product_id");
        }
    }

    // Quay lại trang đơn hàng
    header("Location: dathang.php");
    exit;
}

// --- LẤY DANH SÁCH ĐƠN HÀNG ---
$result_list = null;
$sql_list = "SELECT * FROM hoadon WHERE tentaikhoan = ? ORDER BY ngaylap DESC";
$stmt_list = $conn->prepare($sql_list);
if ($stmt_list) {
    $stmt_list->bind_param("s", $tentaikhoan);
    $stmt_list->execute();
    $result_list = $stmt_list->get_result();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Đơn hàng của bạn</title>
    <style>

        body { background-color: #fefaf0; font-family: Arial, sans-serif; min-height: 100vh; }
        h2 { color: #7a5a00; text-align: center; margin-top: 20px; }
        table { width: 95%; margin: 20px auto; border-collapse: collapse; background-color: #fffdf5; box-shadow: 0 4px 8px rgba(0,0,0,0.05); font-size: 14px; }
        th, td { padding: 10px; text-align: center; border: 1px solid #e0d6c3; }
        th { background-color: #e5c07b; color: #4b3c00; white-space: nowrap; }
        tr:nth-child(even) { background-color: #f9f5e8; }
        tr:hover { background-color: #f1ecdc; }

        body {
            background-color: #fefaf0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
        }

        h2 {
            color: #7a5a00;
            text-align: center;
            margin-top: 20px;
        }

        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: #fffdf5;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            border: 1px solid #e0d6c3;
        }

        th {
            background-color: #e5c07b;
            color: #4b3c00;
        }

        tr:nth-child(even) {
            background-color: #f9f5e8;
        }

        tr:hover {
            background-color: #f1ecdc;
        }


        .blink {
            animation: blink 1s infinite;
             font-family: 'Segoe UI', Arial, sans-serif;
        }

    </style>
</head>
<body>

    <h2 style="font-size: 30px; color: black;">
        <i class="fa-solid fa-cart-shopping" style="color:black; margin-right:10px;"></i>

    <h2 style="font-size: 30px; color: black;" class="blink">
        

        <b>Đơn hàng của bạn</b>
    </h2>
    <table>
        <thead>
            <tr>
                <th>Mã Hóa Đơn</th>
                <th>Mã SP (ID)</th> 
                <!-- <th>Tên SP</th> -->
                <th>SL</th>
                <th>Đơn Giá</th>
                <th>Tổng Tiền</th>
                <th>Địa Chỉ</th>
                <th>SĐT</th>
                <th>PTTT</th>
                <th>Ngày Đặt</th>
                <th>Trạng Thái</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result_list && $result_list->num_rows > 0): ?>
            <?php while ($row = $result_list->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $row['madon'] ?></td>
                    
                    <td style="font-weight:bold; color:#555;"><?= $row['masp'] ?></td>
                    
                    <!-- <td style="text-align: left; max-width: 250px;"><?= htmlspecialchars($row['tensp']) ?></td> -->
                    <td><?= $row['soluong'] ?></td>
                    
                    <td><?= number_format($row['gia'], 0, ',', '.') ?> VNĐ</td>
                    
                    <td style="font-weight: bold; color: #d32f2f;">
                        <?= number_format($row['tongtien'], 0, ',', '.') ?> VNĐ
                    </td>
                    
                    <td><?= htmlspecialchars($row['diachi']) ?></td>
                    <td><?= htmlspecialchars($row['sdt']) ?></td>
                    <td><?= htmlspecialchars($row['pttt']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['ngaylap'])) ?></td>
                    <td>
                        <span style="color: green; font-weight: bold;">
                            <?= htmlspecialchars($row['trangthai']) ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="11" style="padding: 20px;">Bạn chưa có đơn hàng nào.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>