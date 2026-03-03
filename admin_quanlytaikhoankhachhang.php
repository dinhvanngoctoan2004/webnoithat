<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// --- KIỂM TRA QUYỀN ADMIN ---
if (!isset($_SESSION['username']) || strtolower($_SESSION['username']) !== 'admin') {
    header("Location: dangnhap.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'webnoithat');
$conn->set_charset("utf8");
if ($conn->connect_error) die("Kết nối thất bại: " . $conn->connect_error);

// --- XỬ LÝ XÓA TÀI KHOẢN ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM KhachHang WHERE id = $id")) {
        $_SESSION['toast'] = ['message' => 'Xóa tài khoản thành công!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['message' => 'Xóa tài khoản thất bại!', 'type' => 'error'];
    }
    header("Location: admin_quanlytaikhoankhachhang.php");
    exit;
}

// --- LẤY DANH SÁCH TÀI KHOẢN ---
$result = $conn->query("SELECT * FROM KhachHang ORDER BY id DESC");

// --- LẤY THÔNG BÁO TOAST TỪ SESSION ---
$toastMessage = $_SESSION['toast']['message'] ?? '';
$toastType = $_SESSION['toast']['type'] ?? '';
unset($_SESSION['toast']);
?>

<?php include "head.php"; ?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Quản lý tài khoản khách hàng</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fefaf0;
            margin: 0;
            padding: 0;
        }

        h2 {
            color: #7a5a00;
            text-align: center;
            margin: 20px 0;
            font-size: 2rem;
        }

        table {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            border-collapse: collapse;
            background: #fffdf5;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #e0d6c3;
            text-align: center;
        }

        th {
            background: #e5c07b;
            color: #4b3c00;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background: #f9f5e8;
        }

        tr:hover {
            background: #f1ecdc;
        }

        a.btn {
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: bold;
        }

        a.xoa {
            background: #f44336;
            color: white;
        }

        /* Toast nhỏ và thấp */
        #toast {
            position: fixed;
            top: 80px;
            right: 20px;
            min-width: 200px;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: bold;
            color: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            opacity: 0;
            pointer-events: none;
            transform: translateX(100%);
            transition: transform 0.5s ease, opacity 0.5s ease;
            z-index: 9999;
        }

        #toast.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(0);
        }

        #toast.success {
            background-color: #4CAF50;
        }

        #toast.error {
            background-color: #f44336;
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
            
            font-family: 'Segoe UI', Arial, sans-serif;
            
            overflow: hidden;
            animation: marquee 8s linear infinite;
        }


    </style>
</head>

<body>
    <h2 style="font-size: 30px; color: black; text-align: center; overflow: hidden; margin-top: 20px;">
  <span class="marquee">
    <b>Quản Lí Tài Khoản Khách Hàng</b>
  </span>
</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Tên tài khoản</th>
            <th>Mật khẩu</th>
            <th>Địa chỉ</th>
            <th>Số điện thoại</th>
            <th>Hành động</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['taikhoan']) ?></td>
                <td><?= htmlspecialchars($row['matkhau']) ?></td>
                <td><?= htmlspecialchars($row['diachi']) ?></td>
                <td><?= htmlspecialchars($row['sdt']) ?></td>
                <td>
                    <a class="btn xoa" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa tài khoản này?')">Xóa</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <!-- Toast -->
    <div id="toast" class="<?= $toastType ?>"><?= $toastMessage ?></div>
    <script>
        const toast = document.getElementById('toast');
        if (toast.textContent.trim() !== '') {
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>

</html>