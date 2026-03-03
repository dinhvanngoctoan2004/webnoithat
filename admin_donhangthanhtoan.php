<?php
// Kết nối CSDL
$conn = new mysqli("localhost", "root", "", "webnoithat");
$conn->set_charset("utf8");

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Cập nhật trạng thái
if (isset($_POST['capnhat'])) {
    $madon = $_POST['madon'];
    $trangthai = $_POST['trangthai'];
    if ($conn->query("UPDATE hoadon SET trangthai='$trangthai' WHERE madon=$madon")) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=capnhat");
        exit;
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=capnhat");
        exit;
    }
}

// Xóa đơn hàng
if (isset($_POST['xoa'])) {
    $madon = $_POST['madon'];
    if ($conn->query("DELETE FROM hoadon WHERE madon=$madon")) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=xoa");
        exit;
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=xoa");
        exit;
    }
}

// Lấy danh sách đơn hàng
$sql = "SELECT * FROM hoadon ORDER BY ngaylap DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng thanh toán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body { font-family: Arial, sans-serif; background: #fefaf0; margin: 0; padding: 0; }
        h2 { color: #7a5a00; text-align: center; margin: 20px 0; font-size: 2rem; }
        table { width: 95%; max-width: 1500px; margin: 20px auto; border-collapse: collapse; background: #fffdf5; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05); font-size: 14px; }
        th, td { padding: 10px; border: 1px solid #e0d6c3; text-align: center; vertical-align: middle; }
        th { background: #e5c07b; color: #4b3c00; font-weight: bold; white-space: nowrap; }
        
        /* Style ảnh minh chứng */
        .img-minhchung {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ccc;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .img-minhchung:hover {
            transform: scale(1.1);
        }

        /* Toast thông báo */
        #toast { position: fixed; top: 80px; right: 20px; min-width: 200px; background-color: #4CAF50; color: white; padding: 10px 16px; border-radius: 6px; font-weight: bold; opacity: 0; pointer-events: none; transform: translateX(100%); transition: all 0.5s ease; z-index: 9999; }
        #toast.error { background-color: #f44336; }
        #toast.show { opacity: 1; transform: translateX(0); }
        .marquee { display: inline-block; white-space: nowrap; overflow: hidden; animation: marquee 8s linear infinite; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .blink { animation: blink 1s infinite; }
    </style>
</head>

<body>

    <?php include "head.php"; ?>
    <div class="container-fluid mt-5"> <h2 style="font-size: 30px; color: black; text-align: center; overflow: hidden; margin-top: 20px;">
            <span class="marquee"><b>Quản Lí Đơn Hàng Thanh Toán</b></span>
        </h2>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr class="text-center">
                        <th>Mã HĐ</th>
                        <th>Tài khoản</th>
                        <th>Thông tin SP</th> <th>Tổng tiền</th>
                        <th>Khách hàng</th>
                        <th>PTTT</th>
                        <th>Minh chứng</th> <th>Ngày lập</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td>#<?= $row['madon'] ?></td>
                                <td><?= htmlspecialchars($row['tentaikhoan']) ?></td>
                                
                                <td style="text-align:left; max-width: 250px;">
                                    <small>ID: <?= $row['masp'] ?></small><br>
                                    <b><?= htmlspecialchars($row['tensp']) ?></b><br>
                                    <small>SL: <?= $row['soluong'] ?> x <?= number_format($row['gia'], 0, ',', '.') ?></small>
                                </td>
                                
                                <td style="color:#d32f2f; font-weight:bold;"><?= number_format($row['tongtien'], 0, ',', '.') ?> VNĐ</td>
                                
                                <td style="font-size:13px; text-align:left;">
                                    <b><?= htmlspecialchars($row['hoten']) ?></b><br>
                                    <?= htmlspecialchars($row['sdt']) ?><br>
                                    <small><?= htmlspecialchars($row['diachi']) ?></small>
                                </td>

                                <td><?= htmlspecialchars($row['pttt']) ?></td>
                                
                                <td>
                                    <?php 
                                        $minh_chung = $row['minh_chung'] ?? '';
                                        // Kiểm tra nếu có đường dẫn ảnh và file tồn tại
                                        if (!empty($minh_chung) && file_exists($minh_chung)) {
                                            echo '<a href="' . htmlspecialchars($minh_chung) . '" target="_blank" title="Xem ảnh lớn">';
                                            echo '<img src="' . htmlspecialchars($minh_chung) . '" class="img-minhchung" alt="Proof">';
                                            echo '</a>';
                                        } else {
                                            // Nếu PTTT không phải tiền mặt mà không có ảnh
                                            if ($row['pttt'] !== 'Tiền mặt' && $row['pttt'] !== 'Tiền mặt khi nhận hàng') {
                                                echo '<span style="color:red; font-size:12px;">Chưa có ảnh</span>';
                                            } else {
                                                echo '<span style="color:#ccc;">-</span>';
                                            }
                                        }
                                    ?>
                                </td>

                                <td><?= date('d/m H:i', strtotime($row['ngaylap'])) ?></td>
                                
                                <td>
                                    <form method="POST" class="d-flex" style="min-width: 140px;">
                                        <input type="hidden" name="madon" value="<?= $row['madon'] ?>">
                                        <select name="trangthai" class="form-select form-select-sm me-1" style="font-size:12px;">
                                            <option <?= $row['trangthai'] == 'Chờ xử lý' ? 'selected' : '' ?>>Chờ xử lý</option>
                                            <option <?= $row['trangthai'] == 'Đang giao' ? 'selected' : '' ?>>Đang giao</option>
                                            <option <?= $row['trangthai'] == 'Hoàn tất' ? 'selected' : '' ?>>Hoàn tất</option>
                                            <option <?= $row['trangthai'] == 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
                                        </select>
                                        <button type="submit" name="capnhat" class="btn btn-sm btn-success">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                </td>
                                
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="madon" value="<?= $row['madon'] ?>">
                                        <button type="submit" name="xoa" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Bạn có chắc muốn xóa đơn hàng #<?= $row['madon'] ?> không?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="text-center">Chưa có đơn hàng nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="toast"></div>

    <script>
        const params = new URLSearchParams(window.location.search);
        const toast = document.getElementById('toast');

        if (params.get('success') === 'xoa') {
            toast.textContent = 'Xóa đơn hàng thành công!';
            toast.classList.add('success', 'show');
        } else if (params.get('success') === 'capnhat') {
            toast.textContent = 'Cập nhật trạng thái thành công!';
            toast.classList.add('success', 'show');
        } else if (params.get('error')) {
            toast.textContent = 'Thao tác thất bại!';
            toast.classList.add('error', 'show');
        }

        if (toast.classList.contains('show')) {
            setTimeout(() => {
                toast.classList.remove('show');
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 2500);
        }
    </script>
</body>
</html>