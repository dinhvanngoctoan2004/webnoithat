<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Biến $input có thể là Tên đăng nhập (Khách) hoặc Email (Admin)
    $input = trim($_POST['txtUsername'] ?? '');
    $password = trim($_POST['txtPassword'] ?? '');

    // Kết nối MySQL
    $conn = new mysqli('localhost', 'root', '', 'webnoithat');
    $conn->set_charset("utf8");

    if ($conn->connect_error) {
        die("Kết nối thất bại: " . $conn->connect_error);
    }

    // =================================================================
    // BƯỚC 1: KIỂM TRA TRONG BẢNG ADMIN (Ưu tiên Admin trước)
    // =================================================================
    $stmt_admin = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt_admin->bind_param("s", $input);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();

    if ($result_admin->num_rows > 0) {
        $row = $result_admin->fetch_assoc();
        
        // Kiểm tra mật khẩu Admin (Hỗ trợ cả mã hóa và không mã hóa)
        $is_admin_valid = false;
        if (password_verify($password, $row['matkhau'])) {
            $is_admin_valid = true;
        } elseif ($password === $row['matkhau']) {
            $is_admin_valid = true;
        }

        if ($is_admin_valid) {
            // Đăng nhập Admin thành công
            $_SESSION['username'] = 'admin'; // Đặt tên session đặc biệt cho admin
            $_SESSION['admin_email'] = $row['email'];
            
            // Chuyển hướng sang trang quản lý của Admin
            // (Bạn thay đổi tên file này nếu file quản lý của bạn tên khác)
            header("Location: admin_quanlytaikhoankhachhang.php"); 
            exit;
        }
    }
    $stmt_admin->close();

    // =================================================================
    // BƯỚC 2: NẾU KHÔNG PHẢI ADMIN -> KIỂM TRA BẢNG KHÁCH HÀNG
    // =================================================================
    $stmt_user = $conn->prepare("SELECT * FROM KhachHang WHERE taikhoan = ?");
    $stmt_user->bind_param("s", $input);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows > 0) {
        $row = $result_user->fetch_assoc();
        
        // Kiểm tra mật khẩu Khách hàng
        $is_user_valid = false;
        if (password_verify($password, $row['matkhau'])) {
            $is_user_valid = true;
        } elseif ($password === $row['matkhau']) {
            $is_user_valid = true;
        }

        if ($is_user_valid) {
            // Đăng nhập Khách hàng thành công
            $_SESSION['username'] = $input;
            $_SESSION['phone'] = $row['sdt'];
            $_SESSION['address'] = $row['diachi'];
            
            // Chuyển hướng sang Trang chủ
            header("Location: trangchu.php");
            exit;
        } else {
            $error = "Mật khẩu không đúng!";
        }
    } else {
        // Nếu không tìm thấy ở cả Admin và Khách hàng (hoặc Admin sai pass thì nó cũng rơi xuống đây nếu không xử lý kỹ, nhưng ở trên ta check admin num_rows riêng rồi)
        // Logic ở đây: Nếu tìm thấy Admin nhưng sai pass -> Code trên không exit -> Chạy xuống đây -> Không tìm thấy User -> Báo lỗi chung.
        if (empty($error)) { // Nếu chưa có lỗi mật khẩu từ bước check User
             $error = "Tài khoản hoặc Email không tồn tại!";
        }
    }
    
    $stmt_user->close();
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
    <title>Đăng Nhập</title>
    <script type="text/javascript">
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            darkMode: ['class'],
            theme: {
                extend: {
                    colors: {
                        border: 'hsl(var(--border))',
                        input: 'hsl(var(--input))',
                        ring: 'hsl(var(--ring))',
                        background: 'hsl(var(--background))',
                        foreground: 'hsl(var(--foreground))',
                        primary: { DEFAULT: 'hsl(var(--primary))', foreground: 'hsl(var(--primary-foreground))' },
                    },
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer base {
            :root {
                --background: 0 0% 100%; --foreground: 240 10% 3.9%;
            }
        }
    </style>
</head>
<body>
<div class="flex flex-col md:flex-row h-screen">
    <div class="relative w-full md:w-1/2 bg-cover bg-center" style="background-image: url('NỘI THẤT.png');">
    </div>
    <div class="flex items-center justify-center w-full md:w-1/2 bg-[#FAF4EE]">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-sm">
            <h2 class="text-black text-lg font-bold uppercase text-center mb-6" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);">ĐĂNG NHẬP</h2>
            
            <?php if ($error): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded text-center text-sm font-semibold">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <div class="mb-4">
                    <label class="block text-zinc-700 text-sm font-bold mb-2" for="txtUsername">TÊN ĐĂNG NHẬP</label>
                    <input type="text" id="txtUsername" name="txtUsername" class="border rounded-lg w-full py-2 px-3 text-zinc-700 focus:border-[#C4A484] transition duration-200" placeholder="Nhập tài khoản hoặc email" required value="<?= htmlspecialchars($_POST['txtUsername'] ?? '') ?>">
                </div>
                <div class="mb-6">
                    <label class="block text-zinc-700 text-sm font-bold mb-2" for="txtPassword">MẬT KHẨU</label>
                    <input type="password" id="txtPassword" name="txtPassword" class="border rounded-lg w-full py-2 px-3 text-zinc-700 focus:border-[#C4A484] transition duration-200" placeholder="Nhập mật khẩu" required>
                </div>
                <button type="submit" class="bg-[#C4A484] text-white font-bold uppercase rounded-lg py-2 w-full hover:bg-[#BFA68A] transition duration-200">ĐĂNG NHẬP</button>
            </form>
            <div class="flex justify-between mt-4 text-zinc-600 text-sm">
                <a href="dangky.php" class="text-center hover:underline w-full">ĐĂNG KÝ TÀI KHOẢN KHÁCH HÀNG</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>