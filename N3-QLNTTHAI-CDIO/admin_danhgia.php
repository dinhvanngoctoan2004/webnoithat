<?php
// --- KHỞI ĐỘNG PHIÊN LÀM VIỆC ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- KIỂM TRA QUYỀN ADMIN ---
if (!isset($_SESSION['username']) || strtolower($_SESSION['username']) !== 'admin') {
    header("Location: dangnhap.php");
    exit;
}

// --- KẾT NỐI CSDL ---
$conn = new mysqli('localhost', 'root', '', 'webnoithat');
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// --- CẬP NHẬT TRẠNG THÁI ---
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE danhgia SET trangthai = IF(trangthai='Hiển thị','Ẩn','Hiển thị') WHERE id = $id");
    header("Location: admin_danhgia.php");
    exit;
}

// --- XÓA ĐÁNH GIÁ ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM danhgia WHERE id = $id")) {
        echo "<script>alert('Xóa đánh giá thành công!'); window.location='admin_danhgia.php';</script>";
    } else {
        echo "<script>alert('Lỗi khi xóa: " . $conn->error . "');</script>";
    }
    exit;
}

// --- LẤY DANH SÁCH ĐÁNH GIÁ ---
$result = $conn->query("SELECT * FROM danhgia ORDER BY ngaydang DESC");
?>

<?php include "head.php"; ?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Quản lý đánh giá sản phẩm</title>
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
            vertical-align: middle;
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
            transition: 0.3s;
        }

        a.btn {
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: bold;
        }

        a.toggle {
            background: #4CAF50;
            color: white;
        }

        a.delete {
            background: #f44336;
            color: white;
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
        /* .marquee {
            display: inline-block;
            white-space: nowrap;
            overflow: hidden;
            
        } */
        .marquee {
      display: inline-block;
      white-space: nowrap;
      overflow: hidden;
      animation: marquee 8s linear infinite;
    }
    </style>
</head>

<body>
    <h2 style="font-size: 30px; color: black; text-align: center; overflow: hidden; margin-top: 20px;" >
        <span class="marquee">
            <b  >Quản Lí Đánh Giá Bình Luận</b>
        </span>
    </h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Mã SP</th>
            <th>Tài khoản</th>
            <th>Số sao</th>
            <th>Nội dung</th>
            <th>Ngày đăng</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['masp']) ?></td>
                <td><?= htmlspecialchars($row['tentaikhoan']) ?></td>
                <td style="color:#E2B007;font-weight:bold;">⭐ <?= $row['sosao'] ?></td>
                <td style="text-align:left;"><?= htmlspecialchars($row['noidung']) ?></td>
                <td><?= $row['ngaydang'] ?></td>
                <td>
                    <?php if ($row['trangthai'] == 'Hiển thị'): ?>
                        <span style="color:green;font-weight:bold;">Hiển thị</span>
                    <?php else: ?>
                        <span style="color:red;font-weight:bold;">Ẩn</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a class="btn toggle" href="?toggle=<?= $row['id'] ?>">Đổi trạng thái</a>
                    <a class="btn delete" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa đánh giá này?')">Xóa</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>

<?php
$conn->close();
?>