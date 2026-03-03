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
        table { width: 95%; max-width: 1400px; margin: 20px auto; border-collapse: collapse; background: #fffdf5; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05); font-size: 14px; }
        th, td { padding: 10px; border: 1px solid #e0d6c3; text-align: center; vertical-align: middle; }
        th { background: #e5c07b; color: #4b3c00; font-weight: bold; white-space: nowrap; }
        
        /* Toast thông báo */

        #toast { position: fixed; top: 80px; right: 20px; min-width: 200px; background-color: #4CAF50; color: white; padding: 10px 16px; border-radius: 6px; font-weight: bold; opacity: 0; pointer-events: none; transform: translateX(100%); transition: all 0.5s ease; z-index: 9999; }
        #toast.error { background-color: #f44336; }
        #toast.show { opacity: 1; transform: translateX(0); }
        .marquee { display: inline-block; white-space: nowrap; overflow: hidden; animation: marquee 8s linear infinite; }
        

        #toast {
            position: fixed;
            top: 80px;
            right: 20px;
            min-width: 200px;
            background-color: #4CAF50;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: bold;
            opacity: 0;
            pointer-events: none;
            transform: translateX(100%);
            transition: all 0.5s ease;
            z-index: 9999;
        }

        #toast.error {
            background-color: #f44336;
        }

        #toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        /* Hiệu ứng icon nháy */
        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.4;
            }
        }

        .blink {
            animation: blink 1s infinite;
        }

        /* Hiệu ứng chữ chạy ngang */
        .marquee {
            display: inline-block;
            white-space: nowrap;
            overflow: hidden;
            animation: marquee 8s linear infinite;
        }



    </style>
</head>

<body>

 

    <?php include "head.php"; ?>
    <div class="container mt-5">

        <h2 style="font-size: 30px; color: black; text-align: center; overflow: hidden; margin-top: 20px;">
            <span class="marquee"><b>Quản Lí Đơn Hàng Thanh Toán</b></span>
        </h2>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr class="text-center">
                        <th>Mã Hóa Đơn</th>
                        <th>Tài khoản</th>
                        <th>Mã SP</th> <th>Tên Sản phẩm</th> 
                        <th>Đơn Giá</th>
                        <th>SL</th>
                        <th>Tổng tiền</th>
                        <th>Địa chỉ</th>
                        <th>SĐT</th>
                        <th>PTTT</th>
                        <th>Ngày lập</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td>#<?= $row['madon'] ?></td>
                                <td><?= htmlspecialchars($row['tentaikhoan']) ?></td>
                                
                                <td style="font-weight:bold; color:#555;"><?= htmlspecialchars($row['masp']) ?></td>
                                
                                <td style="text-align:left; max-width: 200px;"><?= htmlspecialchars($row['tensp']) ?></td>
                                
                                <td><?= number_format($row['gia'], 0, ',', '.') ?> VNĐ</td>
                                
                                <td><?= $row['soluong'] ?></td>
                                <td style="color:#d32f2f; font-weight:bold;"><?= number_format($row['tongtien'], 0, ',', '.') ?> VNĐ</td>
                                
                                <td style="font-size:12px; max-width:150px;"><?= htmlspecialchars($row['diachi']) ?></td>
                                <td><?= htmlspecialchars($row['sdt']) ?></td>
                                <td><?= htmlspecialchars($row['pttt']) ?></td>
                                <td><?= date('d/m H:i', strtotime($row['ngaylap'])) ?></td>
                                
                                <td>
                                    <form method="POST" class="d-flex" style="min-width: 160px;">
                                        <input type="hidden" name="madon" value="<?= $row['madon'] ?>">
                                        <select name="trangthai" class="form-select form-select-sm me-1">
                                            <option <?= $row['trangthai'] == 'Chờ xử lý' ? 'selected' : '' ?>>Chờ xử lý</option>
                                            <option <?= $row['trangthai'] == 'Đang giao' ? 'selected' : '' ?>>Đang giao</option>
                                            <option <?= $row['trangthai'] == 'Hoàn tất' ? 'selected' : '' ?>>Hoàn tất</option>
                                            <option <?= $row['trangthai'] == 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
                                        </select>
                                        <button type="submit" name="capnhat" class="btn btn-sm btn-success" title="Lưu">
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
                            <td colspan="14" class="text-center">Chưa có đơn hàng nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align:center; margin:20px 0 50px 0;">
            <a href="trangchu.php" class="btn btn-secondary">Quay lại Trang chủ</a>
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