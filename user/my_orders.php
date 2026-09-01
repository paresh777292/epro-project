<?php
/**
 * My Orders Page - Order History & Status Tracking
 * Displays user's past orders with visual timeline and detailed information
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: /EPRO/user/login.php?redirect=my_orders");
    exit;
}

require_once __DIR__ . '/../db_connect.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';

// Fetch user's orders with prepared statement (SECURE)
$orders_stmt = $conn->prepare(
    "SELECT id, order_date, total_amount, subtotal, discount_amount, tax_amount, 
            coupon_code, payment_status, order_status, delivery_address, utr_number
     FROM orders 
     WHERE user_id = ? 
     ORDER BY order_date DESC"
);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

$orders = [];
while ($order = $orders_result->fetch_assoc()) {
    $orders[] = $order;
}
$orders_stmt->close();

// Define order status colors
$status_colors = [
    'pending' => '#f59e0b',
    'confirmed' => '#3b82f6',
    'shipped' => '#8b5cf6',
    'delivered' => '#10b981',
    'cancelled' => '#ef4444'
];

$status_icons = [
    'pending' => 'clock',
    'confirmed' => 'check-circle',
    'shipped' => 'truck',
    'delivered' => 'box',
    'cancelled' => 'x-circle'
];

// Timeline stages
$timeline_stages = ['pending', 'confirmed', 'shipped', 'delivered'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - EPRO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1a1f36 100%);
            color: #e2e8f0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            color: #38bdf8;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header p {
            color: #94a3b8;
            font-size: 14px;
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .order-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid #38bdf825;
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }

        .order-card:hover {
            border-color: #38bdf8;
            box-shadow: 0 8px 32px rgba(56, 189, 248, 0.1);
        }

        .order-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #38bdf825;
            margin-bottom: 20px;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .order-label {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-value {
            font-size: 16px;
            color: #e2e8f0;
            font-weight: 600;
        }

        .order-value.amount {
            font-size: 20px;
            color: #38bdf8;
        }

        .order-value.status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            width: fit-content;
        }

        /* Timeline Section */
        .timeline-section {
            margin-bottom: 20px;
        }

        .timeline {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 24px;
            left: 0;
            right: 0;
            height: 2px;
            background: #38bdf825;
            z-index: 0;
        }

        .timeline-stage {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .timeline-dot {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #1e293b;
            border: 2px solid #38bdf825;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 18px;
            transition: all 0.3s;
        }

        .timeline-stage.active .timeline-dot {
            background: #38bdf8;
            border-color: #38bdf8;
            color: #0f172a;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.5);
        }

        .timeline-stage.completed .timeline-dot {
            background: #34d399;
            border-color: #34d399;
            color: #0f172a;
        }

        .timeline-label {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 600;
            text-align: center;
            min-width: 80px;
            transition: all 0.3s;
        }

        .timeline-stage.active .timeline-label,
        .timeline-stage.completed .timeline-label {
            color: #e2e8f0;
            font-weight: 700;
        }

        .timeline-date {
            font-size: 11px;
            color: #475569;
            text-align: center;
            min-width: 80px;
        }

        /* Items Section */
        .items-section {
            margin-bottom: 20px;
        }

        .items-section-title {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .items-list {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid #38bdf825;
            border-radius: 8px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .item-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid #38bdf840;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            color: #cbd5e1;
            width: fit-content;
        }

        .item-badge .qty {
            background: #38bdf8;
            color: #0f172a;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 11px;
        }

        /* Price Section */
        .price-section {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid #38bdf825;
            border-radius: 8px;
            padding: 12px;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 16px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .price-label {
            color: #94a3b8;
        }

        .price-value {
            color: #e2e8f0;
            font-weight: 600;
            text-align: right;
        }

        .price-row.total {
            border-top: 1px solid #38bdf825;
            padding-top: 8px;
            margin-top: 8px;
            font-size: 15px;
            font-weight: 700;
            color: #38bdf8;
        }

        .price-row.discount {
            color: #34d399;
        }

        /* Actions Section */
        .actions-section {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%);
            color: #0f172a;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(56, 189, 248, 0.3);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #38bdf8;
            color: #38bdf8;
            flex: 1;
        }

        .btn-secondary:hover {
            background: #38bdf815;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px dashed #38bdf825;
            border-radius: 16px;
        }

        .empty-state i {
            font-size: 64px;
            color: #38bdf840;
            margin-bottom: 16px;
        }

        .empty-state h2 {
            font-size: 20px;
            color: #e2e8f0;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .empty-state a {
            display: inline-block;
            padding: 10px 24px;
            background: #38bdf8;
            color: #0f172a;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .empty-state a:hover {
            background: #0284c7;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .order-header {
                grid-template-columns: 1fr;
            }

            .timeline {
                flex-direction: column;
                gap: 16px;
            }

            .timeline::before {
                content: '';
                position: absolute;
                left: 24px;
                top: 0;
                bottom: 0;
                width: 2px;
                height: auto;
                background: #38bdf825;
            }

            .timeline-stage {
                align-items: flex-start;
                margin-left: 60px;
            }

            .actions-section {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-box-open"></i> My Orders</h1>
            <p>View and track all your past orders</p>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h2>No Orders Yet</h2>
                <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                <a href="/EPRO/user/products.php">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <!-- Order Header Info -->
                        <div class="order-header">
                            <div class="order-info">
                                <span class="order-label">Order ID</span>
                                <span class="order-value">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="order-info">
                                <span class="order-label">Order Date</span>
                                <span class="order-value"><?php echo date('d M Y, H:i', strtotime($order['order_date'])); ?></span>
                            </div>
                            <div class="order-info">
                                <span class="order-label">Total Amount</span>
                                <span class="order-value amount">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="timeline-section">
                            <div class="timeline">
                                <?php
                                $current_status_index = array_search(strtolower($order['order_status']), $timeline_stages);
                                foreach ($timeline_stages as $index => $stage):
                                    $is_completed = $index < $current_status_index;
                                    $is_active = $index == $current_status_index;
                                    $stage_class = $is_completed ? 'completed' : ($is_active ? 'active' : '');
                                ?>
                                    <div class="timeline-stage <?php echo $stage_class; ?>">
                                        <div class="timeline-dot">
                                            <i class="fas fa-<?php echo $status_icons[$stage]; ?>"></i>
                                        </div>
                                        <div class="timeline-label"><?php echo ucfirst($stage); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Items -->
                        <div class="items-section">
                            <div class="items-section-title">Items in this order</div>
                            <div class="items-list">
                                <?php
                                // Fetch order items with prepared statement (SECURE)
                                $items_stmt = $conn->prepare(
                                    "SELECT product_name, quantity, product_price 
                                     FROM order_items 
                                     WHERE order_id = ?"
                                );
                                $order_id = $order['id'];
                                $items_stmt->bind_param("i", $order_id);
                                $items_stmt->execute();
                                $items_result = $items_stmt->get_result();

                                if ($items_result->num_rows > 0):
                                    while ($item = $items_result->fetch_assoc()):
                                ?>
                                    <div class="item-badge">
                                        <i class="fas fa-cube" style="color: #38bdf8;"></i>
                                        <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                                        <span class="qty">x<?php echo intval($item['quantity']); ?></span>
                                    </div>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                    <div style="color: #94a3b8; font-size: 12px;">No items in this order</div>
                                <?php endif;
                                $items_stmt->close();
                                ?>
                            </div>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="price-section">
                            <div style="flex: 1;">
                                <div class="price-row">
                                    <span class="price-label">Subtotal</span>
                                    <span class="price-value">₹<?php echo number_format($order['subtotal'] ?? $order['total_amount'] + $order['discount_amount'], 2); ?></span>
                                </div>
                                <?php if (!empty($order['tax_amount']) && $order['tax_amount'] > 0): ?>
                                    <div class="price-row">
                                        <span class="price-label">Tax</span>
                                        <span class="price-value">₹<?php echo number_format($order['tax_amount'], 2); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                    <div class="price-row discount">
                                        <span class="price-label">
                                            Discount <?php if (!empty($order['coupon_code'])): ?>(<?php echo htmlspecialchars($order['coupon_code']); ?>)<?php endif; ?>
                                        </span>
                                        <span class="price-value">-₹<?php echo number_format($order['discount_amount'], 2); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="price-row total">
                                    <span>Total</span>
                                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="actions-section">
                            <a href="/EPRO/user/invoice.php?order_id=<?php echo intval($order['id']); ?>" class="btn btn-primary">
                                <i class="fas fa-file-pdf"></i> View Invoice
                            </a>
                            <?php if (strtolower($order['order_status']) !== 'delivered'): ?>
                                <button class="btn btn-secondary" onclick="trackOrder(<?php echo intval($order['id']); ?>)">
                                    <i class="fas fa-map-marker-alt"></i> Track Order
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="/EPRO/assets/js/toast.js"></script>
    <script>
        function trackOrder(orderId) {
            showToast('Tracking details will be sent to your email shortly', 'info');
            // Future: Integrate with logistics API
        }
    </script>
</body>
</html>
