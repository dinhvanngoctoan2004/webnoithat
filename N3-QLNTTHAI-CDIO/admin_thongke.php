<?php
session_start();

// --- KIỂM TRA QUYỀN ADMIN ---
if (!isset($_SESSION['username']) || strtolower($_SESSION['username']) !== 'admin') {
    header("Location: dangnhap.php");
    exit;
}

// --- KẾT NỐI CSDL ---
$conn = new mysqli("localhost", "root", "", "webnoithat");
$conn->set_charset("utf8");
if ($conn->connect_error) die("Kết nối thất bại: " . $conn->connect_error);


// Tổng doanh thu toàn thời gian (Chỉ tính đơn 'Hoàn tất')
$sql_total = "SELECT SUM(tongtien) as total FROM hoadon WHERE trangthai = 'Hoàn tất'";
$total_revenue = $conn->query($sql_total)->fetch_assoc()['total'] ?? 0;

// Doanh thu HÔM NAY
$date_today = date('Y-m-d');
$sql_today = "SELECT SUM(tongtien) as total FROM hoadon 
              WHERE trangthai = 'Hoàn tất' AND DATE(ngaylap) = '$date_today'";
$today_revenue = $conn->query($sql_today)->fetch_assoc()['total'] ?? 0;

// Doanh thu THÁNG NÀY
$month_curr = date('m');
$sql_month = "SELECT SUM(tongtien) as total FROM hoadon 
              WHERE trangthai = 'Hoàn tất' AND MONTH(ngaylap) = '$month_curr'";
$month_revenue = $conn->query($sql_month)->fetch_assoc()['total'] ?? 0;


// 2. DỮ LIỆU BIỂU ĐỒ: DOANH THU THEO NGÀY (7 NGÀY GẦN NHẤT)

$sql_chart = "SELECT DATE_FORMAT(ngaylap, '%d/%m/%Y') as ngay_hien_thi, SUM(tongtien) as tong_tien 
              FROM hoadon 
              WHERE trangthai = 'Hoàn tất' 
              GROUP BY DATE(ngaylap) 
              ORDER BY ngaylap DESC LIMIT 7"; // Lấy 7 ngày có đơn gần nhất

$result_chart = $conn->query($sql_chart);

$chart_labels = [];
$chart_data = [];

if ($result_chart) {
    while ($row = $result_chart->fetch_assoc()) {
        $chart_labels[] = $row['ngay_hien_thi'];
        $chart_data[] = $row['tong_tien'];
    }
}
// Đảo ngược mảng để ngày cũ bên trái, ngày mới bên phải
$chart_labels = array_reverse($chart_labels);
$chart_data = array_reverse($chart_data);


// 3. TOP 5 ĐƠN HÀNG CÓ GIÁ TRỊ CAO NHẤT

$sql_top = "SELECT * FROM hoadon 
            WHERE trangthai = 'Hoàn tất' 
            ORDER BY tongtien DESC LIMIT 5";
$result_top = $conn->query($sql_top);

?>

<?php include "head.php"; ?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thống Kê Tài Chính</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { background: #fefaf0; font-family: Arial, sans-serif; }
        
        .stat-box {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-align: center;
            border-bottom: 4px solid #e5c07b;
            transition: 0.3s;
        }
        .stat-box:hover { transform: translateY(-5px); }
        
        .stat-title { font-size: 14px; color: #666; text-transform: uppercase; font-weight: bold; }
        .stat-money { font-size: 24px; color: #bfa52f; font-weight: bold; margin-top: 10px; }
        
        .chart-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-top: 30px;
        }

        table.top-orders {
            width: 100%;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        table.top-orders th { background: #e5c07b; color: #4b3c00; padding: 12px; text-align: center; }
        table.top-orders td { padding: 12px; border-bottom: 1px solid #eee; text-align: center; }
        table.top-orders tr:last-child td { border-bottom: none; }
        
        .badge-money {
            background: #e8f5e9; color: #2e7d32; 
            padding: 5px 10px; border-radius: 4px; font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <h2 class="text-center mb-4" style="color: #7a5a00; font-weight: bold;">
        <i class="fas fa-chart-pie"></i> BÁO CÁO DOANH THU
    </h2>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="stat-box">
                <div class="stat-title">Doanh thu Hôm nay</div>
                <div class="stat-money">
                    <?= number_format($today_revenue, 0, ',', '.') ?> <small>VNĐ</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-box" style="border-bottom-color: #28a745;">
                <div class="stat-title">Doanh thu Tháng <?= date('m') ?></div>
                <div class="stat-money" style="color: #28a745;">
                    <?= number_format($month_revenue, 0, ',', '.') ?> <small>VNĐ</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-box" style="border-bottom-color: #dc3545;">
                <div class="stat-title">Tổng Doanh Thu (Tích lũy)</div>
                <div class="stat-money" style="color: #dc3545;">
                    <?= number_format($total_revenue, 0, ',', '.') ?> <small>VNĐ</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="chart-container">
                <h5 style="color: #7a5a00; margin-bottom: 20px;">
                    <i class="fas fa-chart-bar"></i> Biểu đồ doanh thu 7 ngày gần nhất
                </h5>
                <canvas id="moneyChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h5 style="color: #7a5a00; margin-bottom: 15px; font-weight: bold;">Top 5 Đơn Hàng Giá Trị Cao Nhất</h5>
            <table class="top-orders">
                <thead>
                    <tr>
                        <th>Ngày mua</th>
                        <th>Khách hàng</th>
                        <th>Sản phẩm</th>
                        <th>Số tiền thanh toán</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_top->num_rows > 0): ?>
                        <?php while ($row = $result_top->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($row['ngaylap'])) ?></td>
                                <td>
                                    <b><?= htmlspecialchars($row['hoten']) ?></b><br>
                                    <small class="text-muted"><?= htmlspecialchars($row['tentaikhoan']) ?></small>
                                </td>
                                <td class="text-start"><?= htmlspecialchars($row['tensp']) ?> (x<?= $row['soluong'] ?>)</td>
                                <td><span class="badge-money"><?= number_format($row['tongtien'], 0, ',', '.') ?> VNĐ</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4">Chưa có đơn hàng nào hoàn tất.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="admin_hoadon.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại quản lý đơn hàng
        </a>
    </div>
</div>

<script>
    // Lấy dữ liệu từ PHP
    const labels = <?php echo json_encode($chart_labels); ?>; 
    const data = <?php echo json_encode($chart_data); ?>;     

    const ctx = document.getElementById('moneyChart').getContext('2d');
    const moneyChart = new Chart(ctx, {
        type: 'bar', 
        data: {
            labels: labels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: data,
                backgroundColor: 'rgba(184, 134, 11, 0.6)', 
                borderColor: 'rgba(184, 134, 11, 1)',
                borderWidth: 1,
                barThickness: 40 
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            // Format số tiền kiểu Việt Nam trong tooltip
                            let value = context.raw;
                            return value.toLocaleString('vi-VN', {style: 'currency', currency: 'VND'});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        // Format trục Y thành tiền tệ
                        callback: function(value) {
                            return value.toLocaleString('vi-VN') + ' ₫';
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>