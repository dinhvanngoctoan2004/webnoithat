<?php
// File: api_timkiem.php
header('Content-Type: application/json; charset=utf-8');

// 1. KẾT NỐI DATABASE (Dùng đúng tên webnoithat của bạn)
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "webnoithat"; 

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode([]); // Trả về rỗng nếu lỗi kết nối
    exit();
}

// 2. NHẬN TỪ KHÓA
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 1) {
    echo json_encode([]);
    exit();
}

// 3. TÌM KIẾM TRONG BẢNG SANPHAM
// Lưu ý: Kiểm tra kỹ tên bảng (sanpham) và tên cột (tensp, masp) trong database của bạn
$sql = "SELECT masp, tensp FROM sanpham WHERE tensp LIKE ? LIMIT 10";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $searchTerm = "%" . $q . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'name' => $row['tensp'],
            'url'  => "chitietsp.php?masp=" . $row['masp'] // Đường dẫn khi click vào
        ];
    }
    echo json_encode($data);
    $stmt->close();
} else {
    echo json_encode([]);
}

$conn->close();
?>