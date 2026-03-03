<?php
// 1. KẾT NỐI CƠ SỞ DỮ LIỆU
$conn = new mysqli('localhost', 'root', '', 'webnoithat');
$conn->set_charset("utf8");

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// 2. LẤY SẢN PHẨM TỪ CSDL
// Lấy 8 sản phẩm mới nhất để hiển thị (4 cho mục Độc Quyền, 4 cho mục Đặc Biệt)
$sql = "SELECT * FROM sanpham ORDER BY masp DESC LIMIT 8";
$result = $conn->query($sql);

$products = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Chia mảng sản phẩm thành 2 phần
// Phần 1: 4 sản phẩm đầu tiên
$section1_products = array_slice($products, 0, 4);
// Phần 2: 4 sản phẩm tiếp theo
$section2_products = array_slice($products, 4, 4);
?>

<?php include "head.php" ?>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
  <script src="https://unpkg.com/unlazy@0.11.3/dist/unlazy.with-hashing.iife.js" defer init></script>
  <script type="text/javascript">
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
            secondary: { DEFAULT: 'hsl(var(--secondary))', foreground: 'hsl(var(--secondary-foreground))' },
            destructive: { DEFAULT: 'hsl(var(--destructive))', foreground: 'hsl(var(--destructive-foreground))' },
            muted: { DEFAULT: 'hsl(var(--muted))', foreground: 'hsl(var(--muted-foreground))' },
            accent: { DEFAULT: 'hsl(var(--accent))', foreground: 'hsl(var(--accent-foreground))' },
            popover: { DEFAULT: 'hsl(var(--popover))', foreground: 'hsl(var(--popover-foreground))' },
            card: { DEFAULT: 'hsl(var(--card))', foreground: 'hsl(var(--card-foreground))' },
          },
        }
      }
    }
  </script>
  <style type="text/tailwindcss">
    @layer base {
        :root {
          --background: 0 0% 100%; --foreground: 240 10% 3.9%; --card: 0 0% 100%; --card-foreground: 240 10% 3.9%;
          --popover: 0 0% 100%; --popover-foreground: 240 10% 3.9%; --primary: 240 5.9% 10%; --primary-foreground: 0 0% 98%;
          --secondary: 240 4.8% 95.9%; --secondary-foreground: 240 5.9% 10%; --muted: 240 4.8% 95.9%; --muted-foreground: 240 3.8% 46.1%;
          --accent: 240 4.8% 95.9%; --accent-foreground: 240 5.9% 10%; --destructive: 0 84.2% 60.2%; --destructive-foreground: 0 0% 98%;
          --border: 240 5.9% 90%; --input: 240 5.9% 90%; --ring: 240 5.9% 10%; --radius: 0.5rem;
        }
        .dark {
          --background: 240 10% 3.9%; --foreground: 0 0% 98%; --card: 240 10% 3.9%; --card-foreground: 0 0% 98%;
          --popover: 240 10% 3.9%; --popover-foreground: 0 0% 98%; --primary: 0 0% 98%; --primary-foreground: 240 5.9% 10%;
          --secondary: 240 3.7% 15.9%; --secondary-foreground: 0 0% 98%; --muted: 240 3.7% 15.9%; --muted-foreground: 240 5% 64.9%;
          --accent: 240 3.7% 15.9%; --accent-foreground: 0 0% 98%; --destructive: 0 62.8% 30.6%; --destructive-foreground: 0 0% 98%;
          --border: 240 3.7% 15.9%; --input: 240 3.7% 15.9%; --ring: 240 4.9% 83.9%;
        }
      }
      .small-img { width: 30%; max-width: 250px; height: auto; }
  </style>
</head>

<body class="bg-background text-foreground">

    <section class="flex flex-col items-center justify-center h-[60vh] bg-cover"
      style="background-image:url('https://i.pinimg.com/originals/26/31/81/263181b4c7d8c0fb70aa6fa3f6c31936.jpg');">
      <h1 class="text-4xl font-bold text-white">Chúng tôi mang đến những sản phẩm gia dụng chất lượng cao nhất!</h1>
      <button onclick="window.location.href='sanpham.php'" class="mt-4 bg-primary text-primary-foreground p-2 rounded">Order Now</button>
    </section>

    <section class="p-8" style="background-color:#FDF5E6">
      <h2 class="text-3xl font-bold text-center">Thiết Kế Nội Thất Độc Quyền Của Chúng Tôi</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
        
        <?php foreach ($section1_products as $item): 
            // Xử lý đường dẫn ảnh
            $anh = $item['anh'] ?? '';
            $imgSrc = (empty($anh) || preg_match('/^https?:\\/\\//i', $anh)) ? ($anh ?: 'images/fallback.jpg') : 'anh/' . $anh;
        ?>
        <div class="border border-muted p-4 rounded" style="background-color:#EEDFCC">
          <a href="chitietsp.php?masp=<?= htmlspecialchars($item['masp']) ?>">
            <img src="<?= htmlspecialchars($imgSrc) ?>" 
                 alt="<?= htmlspecialchars($item['tensp']) ?>" 
                 class="w-full h-40 object-cover rounded" 
                 onerror="this.src='images/fallback.jpg';" />
            
            <h3 class="font-semibold" style="min-height: 48px; margin-top: 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                <?= htmlspecialchars($item['tensp']) ?>
            </h3>
            
            <p style="color:red; font-weight:bold;"><?= number_format($item['gia'], 0, ',', '.') ?> VNĐ</p>
            <br>
            <button class="bg-secondary text-secondary-foreground hover:bg-secondary/80 p-2 rounded" style="background-color:#CD853F; width:100%;">
                Đặt hàng
            </button>
          </a>
        </div>
        <?php endforeach; ?>

      </div>

      <h2 class="text-3xl font-bold text-center mt-8">Thiết Kế Nội Thất Đặc Biệt Của Chúng Tôi</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
        
        <?php foreach ($section2_products as $item): 
            // Xử lý đường dẫn ảnh
            $anh = $item['anh'] ?? '';
            $imgSrc = (empty($anh) || preg_match('/^https?:\\/\\//i', $anh)) ? ($anh ?: 'images/fallback.jpg') : 'anh/' . $anh;
        ?>
        <div class="border border-muted p-4 rounded" style="background-color:#EEDFCC">
          <a href="chitietsp.php?masp=<?= htmlspecialchars($item['masp']) ?>">
            <img src="<?= htmlspecialchars($imgSrc) ?>" 
                 alt="<?= htmlspecialchars($item['tensp']) ?>" 
                 class="w-full h-40 object-cover rounded" 
                 onerror="this.src='images/fallback.jpg';" />

            <h3 class="font-semibold" style="min-height: 48px; margin-top: 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                <?= htmlspecialchars($item['tensp']) ?>
            </h3>

            <p style="color:red; font-weight:bold;"><?= number_format($item['gia'], 0, ',', '.') ?> VNĐ</p>
            <br>
            <button class="bg-secondary text-secondary-foreground hover:bg-secondary/80 p-2 rounded" style="background-color:#CD853F; width:100%;">
                Đặt hàng
            </button>
          </a>
        </div>
        <?php endforeach; ?>

      </div>
    </section>

    <section class="flex flex-col items-center p-8 bg-muted" style="background-color:#FFEFD5">
      <h2 class="text-2xl font-bold">Khám phá những thiết kế nội thất tinh tế nhất của chúng tôi</h2>
      <img src="https://i.pinimg.com/474x/e6/1b/e1/e61be1bc18225d2734d781948f05c1cb.jpg" alt="Noi that" class="small-img mt-4" />
      <button onclick="window.location.href='sanpham.php'"
        class="mt-4 bg-primary text-primary-foreground p-2 rounded"
        style="background-color:#CD853F">
        Khám phá sản phẩm của chúng tôi
      </button>
    </section>

    <section class="p-8" style="background-color:#FDF5E6">
      <h2 class="text-3xl font-bold text-center">Những Khách Hàng Hạnh Phúc Của Chúng Tôi.</h2>
      <div class="space-y-4 mt-4">
        <div class="border border-muted p-4 rounded">
          <p>"Tôi rất ấn tượng với chất lượng bàn ghế tại đây. Dịch vụ giao hàng nhanh và nhân viên rất nhiệt tình!"</p>
          <p class="font-semibold">Nguyễn Văn A ★★★★★</p>
        </div>
        <div class="border border-muted p-4 rounded">
          <p>"Sản phẩm đúng như mô tả, gỗ rất chắc chắn và màu sắc đẹp. Sẽ ủng hộ shop dài dài."</p>
          <p class="font-semibold">Trần Thị B ★★★★★</p>
        </div>
        <div class="border border-muted p-4 rounded">
          <p>"Giá cả hợp lý cho chất lượng tuyệt vời. Tôi đã mua trọn bộ combo phòng ngủ và rất hài lòng."</p>
          <p class="font-semibold">Lê Văn C ★★★★★</p>
        </div>
      </div>
    </section>

    <section class="flex flex-col items-center p-8 bg-muted" style="background-color:#FFEFD5">
      <h2 class="text-2xl font-bold">Tham gia ngay để nhận ưu đãi 15%!</h2>
      <p class="mt-2">Đăng ký nhận bản tin của chúng tôi để nhận mã giảm giá 15%!</p>
      <input type="email" placeholder="Email address" class="border border-muted p-2 rounded mt-4" />
      <button class="mt-2 bg-primary text-primary-foreground p-2 rounded " style="background-color: #CD853F">Đăng ký</button>
    </section>

    <?php include 'boxchat.php'; ?>

    <style>
      #chatbox-toggle { position: fixed; bottom: 24px; right: 24px; z-index: 10000; background: #febf00; color: #fff; border: none; border-radius: 50%; width: 60px; height: 60px; font-size: 32px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2); cursor: pointer; display: flex; align-items: center; justify-content: center; }
      .bg-secondary{ color: #ffff; }
      .chat-container { position: fixed; bottom: 100px; right: 24px; z-index: 9999; display: none; }
      .chat-container.active { display: block; }
      .text { color: #fff; }
    </style>

    <button id="chatbox-toggle" title="Chat với chúng tôi" aria-label="Chat với chúng tôi">
      <img id="chatbox-icon" src="https://i.pinimg.com/236x/2f/08/ab/2f08ab311cb92ed2cfafc691b12a8ce2.jpg" alt="Chat Icon" style="width:56px; height:56px;border-radius: 50%;; pointer-events:none; transition: transform 0.3s cubic-bezier(.68,-0.55,.27,1.55); filter: drop-shadow(0 2px 6px rgba(0,0,0,0.15));" />
    </button>

    <script>
      const chatToggle = document.getElementById('chatbox-toggle');
      const chatBox = document.querySelector('.chat-container');
      const chatIcon = document.getElementById('chatbox-icon');
      chatToggle.onclick = function() {
        chatBox.classList.toggle('active');
        chatIcon.style.transform = 'scale(1.2) rotate(-10deg)';
        setTimeout(() => { chatIcon.style.transform = 'scale(1) rotate(0deg)'; }, 220);
      };
    </script>

</body>
</html>