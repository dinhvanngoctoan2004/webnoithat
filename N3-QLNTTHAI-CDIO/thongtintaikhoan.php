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

// Lấy thông tin tài khoản để hiển thị
$userInfo = null;
$conn = new mysqli('localhost', 'root', '', 'webnoithat');
$conn->set_charset("utf8");

if (!$conn->connect_error) {
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
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script src="https://unpkg.com/unlazy@0.11.3/dist/unlazy.with-hashing.iife.js" defer init></script>
    <title>Thông Tin Tài Khoản</title>
    <style type="text/tailwindcss">
        @layer base {
            :root {
                --background: 0 0% 100%;
                --foreground: 240 10% 3.9%;
                --card: 0 0% 100%;
                --card-foreground: 240 10% 3.9%;
                --popover: 0 0% 100%;
                --popover-foreground: 240 10% 3.9%;
                --primary: 240 5.9% 10%;
                --primary-foreground: 0 0% 98%;
                --secondary: 240 4.8% 95.9%;
                --secondary-foreground: 240 5.9% 10%;
                --muted: 240 4.8% 95.9%;
                --muted-foreground: 240 3.8% 46.1%;
                --accent: 240 4.8% 95.9%;
                --accent-foreground: 240 5.9% 10%;
                --destructive: 0 84.2% 60.2%;
                --destructive-foreground: 0 0% 98%;
                --border: 240 5.9% 90%;
                --input: 240 5.9% 90%;
                --ring: 240 5.9% 10%;
                --radius: 0.5rem;
            }
            .dark {
                --background: 240 10% 3.9%;
                --foreground: 0 0% 98%;
                --card: 240 10% 3.9%;
                --card-foreground: 0 0% 98%;
                --popover: 240 10% 3.9%;
                --popover-foreground: 0 0% 98%;
                --primary: 0 0% 98%;
                --primary-foreground: 240 5.9% 10%;
                --secondary: 240 3.7% 15.9%;
                --secondary-foreground: 0 0% 98%;
                --muted: 240 3.7% 15.9%;
                --muted-foreground: 240 5% 64.9%;
                --accent: 240 3.7% 15.9%;
                --accent-foreground: 0 0% 98%;
                --destructive: 0 62.8% 30.6%;
                --destructive-foreground: 0 0% 98%;
                --border: 240 3.7% 15.9%;
                --input: 240 3.7% 15.9%;
                --ring: 240 4.9% 83.9%;
            }
        }
    </style>
</head>

<body>
    <div class="flex flex-col items-center justify-center min-h-[80vh] py-8" style="background-color: #e2ddcf;">
        <div class="p-8 rounded-lg shadow-lg w-full max-w-md mx-auto" style="background-color: #fcf6e7;">
            <h2 class="text-2xl font-bold text-foreground mb-6 text-center">THÔNG TIN TÀI KHOẢN</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-1">TÊN TÀI KHOẢN</label>
                    <div class="border border-muted rounded-lg p-3 bg-white">
                        <?= htmlspecialchars($userInfo['taikhoan'] ?? '') ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-1">MẬT KHẨU</label>
                    <div class="border border-muted rounded-lg p-3 bg-white">
                        <?= htmlspecialchars($userInfo['matkhau'] ?? '') ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-1">ĐỊA CHỈ</label>
                    <div class="border border-muted rounded-lg p-3 bg-white">
                        <?= htmlspecialchars($userInfo['diachi'] ?? '') ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-1">SỐ ĐIỆN THOẠI</label>
                    <div class="border border-muted rounded-lg p-3 bg-white">
                        <?= htmlspecialchars($userInfo['sdt'] ?? '') ?>
                    </div>
                </div>
            </div>
            <div style="text-align:center; margin-top: 20px;">
                <a href="trangchu.php" style="display:inline-block; background:#e5c07b; color:#4b3c00; padding:10px 24px; border-radius:6px; text-decoration:none; font-weight:bold; transition:background 0.2s;"
                    onmouseover="this.style.background='#d1ae66'" onmouseout="this.style.background='#e5c07b'">
                    Quay lại Trang chủ
                </a>
            </div>
        </div>
    </div>
</body>

</html>