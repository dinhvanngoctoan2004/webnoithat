<?php
ob_start(); // ✅ Chặn output để tránh lỗi header
include "head.php";

$conn = new mysqli("localhost", "root", "", "webnoithat");
$conn->set_charset("utf8");
if ($conn->connect_error) die("Kết nối thất bại: " . $conn->connect_error);

$msg = "";

// thêm sản phẩm
if (isset($_POST['them'])) {
  $masp = trim($_POST['masp']);
  $maloai = trim($_POST['maloai']);
  $tensp = trim($_POST['tensp']);
  $chatlieu = trim($_POST['chatlieu']);
  $mau = trim($_POST['mau']);
  $hinhthuc = trim($_POST['hinhthuc']);
  $mota = trim($_POST['mota']);
  $gia = trim($_POST['gia']);
  $anh = trim($_POST['anh']);

  if ($masp && $tensp && is_numeric($gia)) {
    $sql = "INSERT INTO sanpham(masp,maloai,tensp,chatlieu,mau,hinhthuc,mota,gia,anh) 
            VALUES('$masp','$maloai','$tensp','$chatlieu','$mau','$hinhthuc','$mota','$gia','$anh')";
    $msg = $conn->query($sql) ? "Thêm thành công!" : "Lỗi: " . $conn->error;
  } else {
    $msg = "Dữ liệu không hợp lệ (giá phải là số và tên không được trống)";
  }
  header("Location: quanlysanpham.php?msg=" . urlencode($msg));
  exit;
}

// cập nhật sản phẩm
if (isset($_POST['capnhat'])) {
  $id = $_POST['masp'];
  $gia = $_POST['gia'];

  if (is_numeric($gia)) {
    $sql = "UPDATE sanpham SET 
              maloai='{$_POST['maloai']}',
              tensp='{$_POST['tensp']}',
              chatlieu='{$_POST['chatlieu']}',
              mau='{$_POST['mau']}',
              hinhthuc='{$_POST['hinhthuc']}',
              mota='{$_POST['mota']}',
              gia='$gia',
              anh='{$_POST['anh']}'
            WHERE masp='$id'";
    $msg = $conn->query($sql) ? "Cập nhật thành công!" : "Lỗi: " . $conn->error;
  } else {
    $msg = "Giá phải là số!";
  }
  header("Location: quanlysanpham.php?msg=" . urlencode($msg));
  exit;
}

// xóa sản phẩm
if (isset($_GET['xoa'])) {
  $id = $_GET['xoa'];
  $conn->query("DELETE FROM sanpham WHERE masp='$id'");
  $msg = "Đã xóa sản phẩm!";
  header("Location: quanlysanpham.php?msg=" . urlencode($msg));
  exit;
}

// lấy thông báo nếu có
$msg = $_GET['msg'] ?? "";
$products = $conn->query("SELECT * FROM sanpham ORDER BY masp DESC");

ob_end_flush(); // ✅ Cho phép xuất HTML sau khi xong xử lý PHP
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Quản lý sản phẩm</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>

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



    .marquee {
      display: inline-block;
      white-space: nowrap;
      overflow: hidden;
      animation: marquee 8s linear infinite;
    }

    .container-flex {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      width: 95%;
      margin: 0 auto;
      gap: 20px;
    }

    .form-section {
      flex: 4;
      background: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      padding: 20px;
    }

    .list-section {
      flex: 8;
      background: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      padding: 20px;
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fffdf5;
    }

    th,
    td {
      padding: 10px;
      border: 1px solid #ddd;
      text-align: center;
    }

    th {
      background: #e5c07b;
      color: #4b3c00;
      font-weight: bold;
    }

    .notify {
      position: fixed;
      top: 70px;
      right: 20px;
      background-color: #fff4e5;
      color: #0ce943ff;
      border-left: 5px solid #ffb347;
      padding: 10px 15px;
      margin: 10px 0 10px auto;
      width: fit-content;
      border-radius: 5px;
      font-weight: bold;
      animation: slideDown 0.5s ease;
      z-index: 9999;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>

  <script>
    function previewImage() {
      let url = document.getElementById("anh").value;
      let img = document.getElementById("imgPreview");
      img.src = url;
      img.style.display = url ? "block" : "none";
    }
  </script>
</head>

<body>
  <h2 style="font-size: 30px; color: black; text-align: center; overflow: hidden; margin-top: 20px;">
    <span class="marquee"><b>Quản Lí Sản Phẩm</b></span>
  </h2>

  <?php if ($msg): ?>
    <div class="notify"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="container-flex">

    <!-- Form thêm sản phẩm -->
    <div class="form-section">
      <h2 class="text-xl font-bold mb-4" style="font-size:25px; color: black;">Thêm sản phẩm</h2>
      <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input name="masp" placeholder="Mã SP" class="border p-2" required>
        <input name="maloai" placeholder="Mã Loại" class="border p-2">
        <input name="tensp" placeholder="Tên SP" class="border p-2 md:col-span-2" required>
        <input name="chatlieu" placeholder="Chất liệu" class="border p-2">
        <input name="mau" placeholder="Màu" class="border p-2">
        <input name="hinhthuc" placeholder="Hình thức" class="border p-2 md:col-span-2">
        <textarea name="mota" placeholder="Mô tả" class="border p-2 md:col-span-2"></textarea>
        <input name="gia" placeholder="Giá" class="border p-2 md:col-span-2" required>
        <input id="anh" name="anh" placeholder="Link ảnh" oninput="previewImage()" class="border p-2 md:col-span-2">
        <img id="imgPreview" class="max-h-40 hidden md:col-span-2">
        <button type="submit" name="them" class="bg-amber-400 text-white px-4 py-2 rounded md:col-span-2 hover:bg-amber-500">Thêm</button>
      </form>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="list-section">
      <h2 class="text-xl font-bold mb-4" style="font-size:25px; color: black;">Danh sách sản phẩm</h2>
      <table class="w-full text-gray-700">
        <thead class="bg-gray-200 uppercase text-gray-600 text-xs font-semibold">
          <tr>
            <th class="p-2 text-left border-b w-1/12">Mã</th>
            <th class="p-2 text-left border-b w-2/12">Tên</th>
            <th class="p-2 text-right border-b w-1/12">Giá</th>
            <th class="p-2 text-center border-b w-4/12">Ảnh</th>
            <th class="p-2 text-center border-b w-4/12">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $products->fetch_assoc()): ?>
            <tr class="hover:bg-gray-50">
              <td class="p-2 border-b"><?= $row['masp'] ?></td>
              <td class="p-2 text-xs truncate max-w-xs border-b"><?= htmlspecialchars($row['tensp']) ?></td>
              <td class="p-2 text-right text-xs border-b"><?= is_numeric($row['gia']) ? number_format($row['gia'], 0, ',', '.') : "0" ?> VNĐ</td>
              <td class="p-2 text-center border-b">
                <img src="<?= htmlspecialchars($row['anh']) ?>" class="h-24 mx-auto rounded">
              </td>
              <td class="p-2 text-center border-b">
                <form method="post" class="flex flex-col gap-1 mb-1">
                  <input type="hidden" name="masp" value="<?= $row['masp'] ?>">
                  <input type="text" name="maloai" value="<?= $row['maloai'] ?>" class="border p-1 text-sm" placeholder="Mã loại">
                  <input type="text" name="tensp" value="<?= htmlspecialchars($row['tensp']) ?>" class="border p-1 text-sm" placeholder="Tên SP">
                  <input type="text" name="chatlieu" value="<?= htmlspecialchars($row['chatlieu']) ?>" class="border p-1 text-sm" placeholder="Chất liệu">
                  <input type="text" name="mau" value="<?= htmlspecialchars($row['mau']) ?>" class="border p-1 text-sm" placeholder="Màu">
                  <input type="text" name="hinhthuc" value="<?= htmlspecialchars($row['hinhthuc']) ?>" class="border p-1 text-sm" placeholder="Hình thức">
                  <textarea name="mota" class="border p-1 text-sm" placeholder="Mô tả"><?= htmlspecialchars($row['mota']) ?></textarea>
                  <input type="text" name="gia" value="<?= htmlspecialchars($row['gia']) ?>" class="border p-1 text-sm" placeholder="Giá">
                  <input type="text" name="anh" value="<?= htmlspecialchars($row['anh']) ?>" class="border p-1 text-sm" placeholder="Link ảnh">
                  <div class="flex gap-2 mt-1 justify-center">
                    <button name="capnhat" class="bg-green-500 text-white px-2 py-1 rounded text-sm hover:bg-green-600">Sửa</button>
                    <a href="?xoa=<?= $row['masp'] ?>" onclick="return confirm('Xóa sản phẩm này?')" class="bg-red-500 text-white px-2 py-1 rounded text-sm hover:bg-red-600">Xóa</a>
                  </div>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>

</html>