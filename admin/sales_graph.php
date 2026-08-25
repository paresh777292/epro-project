<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Admin Authentication Check
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// 2. Database Connection
if (file_exists('../db_connect.php')) {
    include '../db_connect.php';
} elseif (file_exists('../config.php')) {
    include '../config.php';
}

if (!isset($conn) || !$conn) {
    die("<h3 style='color:#f43f5e; text-align:center;'>Database Connection Failed!</h3>");
}

// 3. Real-Time Orders Metrics Fetch
$total_revenue = 0;
$total_orders = 0;

$order_stats = mysqli_query($conn, "SELECT SUM(total_amount) as rev, COUNT(*) as orders_cnt FROM orders");
if ($order_stats && $r = mysqli_fetch_assoc($order_stats)) {
    $total_revenue = (float)($r['rev'] ?? 0);
    $total_orders = (int)($r['orders_cnt'] ?? 0);
}

// 4. Monthly Dynamic Revenue from Orders
$months_map = ['Jan'=>0, 'Feb'=>0, 'Mar'=>0, 'Apr'=>0, 'May'=>0, 'Jun'=>0, 'Jul'=>0, 'Aug'=>0, 'Sep'=>0, 'Oct'=>0, 'Nov'=>0, 'Dec'=>0];

$monthly_res = mysqli_query($conn, "SELECT DATE_FORMAT(order_date, '%b') as mon, SUM(total_amount) as total FROM orders GROUP BY DATE_FORMAT(order_date, '%b')");
if ($monthly_res) {
    while ($m = mysqli_fetch_assoc($monthly_res)) {
        if (isset($months_map[$m['mon']])) {
            $months_map[$m['mon']] = (float)$m['total'];
        }
    }
}

$chart_months = array_keys($months_map);
$chart_sales = array_values($months_map);

// 5. Category Distribution from Products
$categories = [];
$cat_counts = [];
$cat_res = mysqli_query($conn, "SELECT category, COUNT(*) as total_items FROM products GROUP BY category");
if ($cat_res && mysqli_num_rows($cat_res) > 0) {
    while ($row = mysqli_fetch_assoc($cat_res)) {
        $categories[] = !empty($row['category']) ? ucfirst($row['category']) : 'General';
        $cat_counts[] = (int)$row['total_items'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales & Analytics | EPRO Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
        body {
            background-color: #0b0f19;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(56, 189, 248, 0.18) 0%, transparent 40%),
                radial-gradient(circle at 90% 10%, rgba(129, 140, 248, 0.18) 0%, transparent 40%),
                radial-gradient(circle at 50% 90%, rgba(236, 72, 153, 0.14) 0%, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            color: #f8fafc;
            padding: 35px 20px;
        }
        .container { max-width: 1150px; margin: 0 auto; }
        .header-bar {
            background: rgba(30, 41, 59, 0.65);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }
        .title-area h2 {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-dash {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-dash:hover { background: rgba(255, 255, 255, 0.16); color: white; transform: translateY(-2px); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card {
            background: rgba(30, 41, 59, 0.55);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-info h3 { font-size: 24px; font-weight: 700; margin-bottom: 4px; }
        .stat-info p { color: #94a3b8; font-size: 13px; }
        .stat-icon { font-size: 26px; padding: 14px; border-radius: 12px; }
        .icon-green { background: rgba(52, 211, 153, 0.15); color: #34d399; }
        .icon-blue { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
        .icon-pink { background: rgba(244, 114, 182, 0.15); color: #f472b6; }

        .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; }
        @media (max-width: 900px) { .charts-grid { grid-template-columns: 1fr; } }
        .chart-card {
            background: rgba(30, 41, 59, 0.55);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }
        .chart-header { margin-bottom: 20px; font-size: 16px; font-weight: 600; color: #f1f5f9; display: flex; align-items: center; gap: 8px; }
        .chart-box { position: relative; height: 320px; width: 100%; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-bar">
        <div class="title-area">
            <h2><i class="fa-solid fa-chart-line"></i> Real-Time Sales Dashboard</h2>
        </div>
        <a href="dashboard.php" class="btn-dash"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    <!-- Live Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3>₹<?php echo number_format($total_revenue, 2); ?></h3>
                <p>Total Real Sales</p>
            </div>
            <div class="stat-icon icon-green"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3><?php echo $total_orders; ?></h3>
                <p>Completed Orders</p>
            </div>
            <div class="stat-icon icon-blue"><i class="fa-solid fa-bag-shopping"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3><?php echo count($categories); ?></h3>
                <p>Product Categories</p>
            </div>
            <div class="stat-icon icon-pink"><i class="fa-solid fa-tags"></i></div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <div class="chart-card">
            <div class="chart-header">
                <i class="fa-solid fa-wave-square" style="color:#38bdf8;"></i> Real Monthly Revenue Trend (₹)
            </div>
            <div class="chart-box">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <i class="fa-solid fa-chart-pie" style="color:#a78bfa;"></i> Items by Category
            </div>
            <div class="chart-box">
                <canvas id="categoryDoughnutChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Live Trend Line Chart
const ctxTrend = document.getElementById('salesTrendChart').getContext('2d');
const gradientTrend = ctxTrend.createLinearGradient(0, 0, 0, 300);
gradientTrend.addColorStop(0, 'rgba(56, 189, 248, 0.45)');
gradientTrend.addColorStop(1, 'rgba(56, 189, 248, 0.0)');

new Chart(ctxTrend, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_months); ?>,
        datasets: [{
            label: 'Actual Revenue (₹)',
            data: <?php echo json_encode($chart_sales); ?>,
            borderColor: '#38bdf8',
            borderWidth: 3,
            backgroundColor: gradientTrend,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#38bdf8',
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } },
            y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8', callback: v => '₹' + v } }
        }
    }
});

// Category Doughnut Chart
const ctxDoughnut = document.getElementById('categoryDoughnutChart').getContext('2d');
new Chart(ctxDoughnut, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($categories); ?>,
        datasets: [{
            data: <?php echo json_encode($cat_counts); ?>,
            backgroundColor: ['#38bdf8', '#34d399', '#fbbf24', '#f472b6', '#a78bfa'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { color: '#cbd5e1', padding: 15 } }
        },
        cutout: '70%'
    }
});
</script>

</body>
</html>