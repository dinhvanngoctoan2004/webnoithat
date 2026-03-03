<?php include "head.php"; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra nếu người dùng chưa đăng nhập
if (!isset($_SESSION['username'])) {
    header("Location: dangnhap.php");
    exit;
}

$message = ""; // Biến lưu thông báo
$conn = new mysqli('localhost', 'root', '', 'webnoithat');
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// --- XỬ LÝ CẬP NHẬT THÔNG TIN (KHI BẤM NÚT LƯU) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    // Lấy dữ liệu từ form
    $newPass = $_POST['matkhau'];
    $newAddress = $_POST['diachi'];
    $newPhone = $_POST['sdt'];
    $currentUser = $_SESSION['username'];

    // Câu lệnh Update
    $stmt_update = $conn->prepare("UPDATE KhachHang SET matkhau = ?, diachi = ?, sdt = ? WHERE taikhoan = ?");
    $stmt_update->bind_param("ssss", $newPass, $newAddress, $newPhone, $currentUser);

    if ($stmt_update->execute()) {
        $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4'>Cập nhật thông tin thành công!</div>";
    } else {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>Lỗi: " . $conn->error . "</div>";
    }
    $stmt_update->close();
}

// --- LẤY THÔNG TIN ĐỂ HIỂN THỊ (Sau khi update xong thì lấy lại info mới) ---
$userInfo = null;
$stmt = $conn->prepare("SELECT * FROM KhachHang WHERE taikhoan = ?");
if ($stmt) {
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $userInfo = $result->fetch_assoc();
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <title>Thông Tin Tài Khoản</title>
    <style type="text/tailwindcss">
        @layer base {
            :root {
                --background: 0 0% 100%;
                --foreground: 240 10% 3.9%;
                --muted: 240 4.8% 95.9%;
                --muted-foreground: 240 3.8% 46.1%;
                --input: 240 5.9% 90%;
            }
        }
    </style>
</head>

<body>
    <div class="flex flex-col items-center justify-center min-h-[80vh] py-8" style="background-color: #e2ddcf;">
        <div class="p-8 rounded-lg shadow-lg w-full max-w-md mx-auto" style="background-color: #fcf6e7;">
            <h2 class="text-2xl font-bold text-foreground mb-6 text-center">THÔNG TIN TÀI KHOẢN</h2>
            
            <?= $message ?>

            <form method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">TÊN TÀI KHOẢN</label>
                        <input type="text" value="<?= htmlspecialchars($userInfo['taikhoan'] ?? '') ?>" 
                               class="w-full border border-muted rounded-lg p-3 bg-gray-200 text-gray-500 cursor-not-allowed" 
                               readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">MẬT KHẨU</label>
                        <input type="text" name="matkhau" value="<?= htmlspecialchars($userInfo['matkhau'] ?? '') ?>" 
                               class="w-full border border-muted rounded-lg p-3 bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500" 
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">ĐỊA CHỈ</label>
                        <input type="text" name="diachi" value="<?= htmlspecialchars($userInfo['diachi'] ?? '') ?>" 
                               class="w-full border border-muted rounded-lg p-3 bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">SỐ ĐIỆN THOẠI</label>
                        <input type="text" name="sdt" value="<?= htmlspecialchars($userInfo['sdt'] ?? '') ?>" 
                               class="w-full border border-muted rounded-lg p-3 bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500">
                    </div>
                </div>

                <div class="flex flex-col gap-3 mt-8 text-center">
                    <button type="submit" name="update_info" 
                            class="w-full bg-[#e5c07b] hover:bg-[#d1ae66] text-[#4b3c00] font-bold py-2.5 px-4 rounded-lg transition duration-200">
                        Lưu thay đổi
                    </button>
                    
                    <a href="trangchu.php" 
                       class="w-full inline-block bg-[#e5c07b] text-[#4b3c00] py-2.5 px-4 rounded-lg font-bold transition duration-200 hover:bg-[#d1ae66]">
                        Quay lại Trang chủ
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>