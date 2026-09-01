<?php
/**
 * Invoice Display & Print Page
 * Generates print-ready invoice for a specific order
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: /EPRO/user/login.php?redirect=invoice");
    exit;
}

require_once __DIR__ . '/../db_connect.php';

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    header("Location: /EPRO/user/my_orders.php");
    exit;
}

// Fetch order details with user verification (SECURE - prepared statement)
$order_stmt = $conn->prepare(
    "SELECT o.id, o.user_id, o.order_date, o.total_amount, o.subtotal, o.discount_amount, 
            o.tax_amount, o.coupon_code, o.payment_status, o.order_status, o.delivery_address, 
            o.utr_number, o.payment_method, u.name, u.email, u.phone
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     WHERE o.id = ? AND o.user_id = ?"
);
$order_stmt->bind_param("ii", $order_id, $user_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    http_response_code(404);
    die("Order not found or you don't have access to this invoice.");
}

$order = $order_result->fetch_assoc();
$order_stmt->close();

// Fetch order items (SECURE - prepared statement)
$items_stmt = $conn->prepare(
    "SELECT product_name, product_price, quantity, subtotal 
     FROM order_items 
     WHERE order_id = ?"
);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$order_items = [];
while ($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
}
$items_stmt->close();

// Calculate totals
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['subtotal'];
}

$tax_amount = floatval($order['tax_amount']) ?? 0;
$discount_amount = floatval($order['discount_amount']) ?? 0;
$total_amount = floatval($order['total_amount']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?> - EPRO</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .no-print {
                display: none;
            }

            .invoice-container {
                background: white;
                color: #333;
                border: none;
                box-shadow: none;
            }

            .invoice-header, .invoice-section {
                border-color: #d1d5db;
            }

            .status-badge {
                border: 1px solid #d1d5db;
                color: #333 !important;
                background: transparent !important;
            }

            @page {
                size: A4;
                margin: 0;
            }
        }

        .controls {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            justify-content: space-between;
            align-items: center;
        }

        .controls-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%);
            color: #0f172a;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(56, 189, 248, 0.3);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #38bdf8;
            color: #38bdf8;
        }

        .btn-secondary:hover {
            background: #38bdf815;
        }

        /* Invoice Styling */
        .invoice-container {
            background: white;
            color: #1f2937;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        /* Header Section */
        .invoice-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 30px;
            align-items: start;
        }

        .company-info h1 {
            font-size: 28px;
            color: #0284c7;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .company-info p {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.6;
        }

        .invoice-meta {
            text-align: right;
        }

        .invoice-meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .invoice-meta-label {
            color: #6b7280;
            font-weight: 600;
        }

        .invoice-meta-value {
            color: #1f2937;
            font-weight: 600;
            margin-left: 20px;
        }

        .invoice-meta-value.id {
            font-size: 16px;
            color: #0284c7;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #3b82f6;
            color: #0284c7;
            margin-top: 8px;
        }

        /* Parties Section */
        .parties-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .party-box {
            font-size: 13px;
            line-height: 1.7;
        }

        .party-title {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
            font-size: 12px;
            text-transform: uppercase;
            color: #6b7280;
        }

        .party-content {
            color: #1f2937;
        }

        .party-content p {
            margin-bottom: 4px;
        }

        /* Items Table */
        .items-section {
            margin-bottom: 30px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .items-table thead {
            background: #f3f4f6;
            border-top: 2px solid #d1d5db;
            border-bottom: 2px solid #d1d5db;
        }

        .items-table th {
            padding: 12px;
            text-align: left;
            font-weight: 700;
            color: #1f2937;
        }

        .items-table th:last-child {
            text-align: right;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #1f2937;
        }

        .items-table td:last-child {
            text-align: right;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Summary Section */
        .summary-section {
            display: flex;
            justify-content: space-between;
            gap: 40px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .summary-left {
            flex: 1;
        }

        .summary-right {
            width: 300px;
            text-align: right;
        }

        .summary-notes {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.6;
        }

        .summary-notes-title {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .summary-label {
            color: #6b7280;
        }

        .summary-value {
            color: #1f2937;
            font-weight: 600;
            min-width: 100px;
            text-align: right;
        }

        .summary-row.total {
            border-top: 2px solid #d1d5db;
            padding-top: 8px;
            margin-top: 8px;
            font-size: 16px;
            font-weight: 700;
        }

        .summary-row.total .summary-value {
            color: #0284c7;
            font-size: 18px;
        }

        .summary-row.discount {
            color: #059669;
        }

        /* Footer Section */
        .invoice-footer {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 40px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
        }

        .footer-box {
            text-align: center;
        }

        .footer-label {
            color: #6b7280;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .footer-signature {
            height: 40px;
            margin-bottom: 8px;
            border-top: 1px solid #1f2937;
        }

        .footer-name {
            color: #1f2937;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .invoice-container {
                padding: 20px;
            }

            .invoice-header, .parties-section, .invoice-footer {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .invoice-meta {
                text-align: left;
            }

            .summary-section {
                flex-direction: column;
            }

            .summary-right {
                width: 100%;
                text-align: right;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Controls -->
        <div class="controls no-print">
            <div>
                <a href="/EPRO/user/my_orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>
            <div class="controls-group">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print / PDF
                </button>
                <button class="btn btn-secondary" onclick="downloadInvoice()">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
        </div>

        <!-- Invoice -->
        <div class="invoice-container">
            <!-- Header -->
            <div class="invoice-header">
                <div class="company-info">
                    <h1><i class="fas fa-store"></i> EPRO Store</h1>
                    <p>
                        Premium E-Commerce Platform<br>
                        📧 support@epro-store.com<br>
                        📱 +91 9876543210<br>
                        🌐 www.epro-store.com
                    </p>
                </div>
                <div class="invoice-meta">
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">INVOICE #</span>
                        <span class="invoice-meta-value id"><?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">Invoice Date:</span>
                        <span class="invoice-meta-value"><?php echo date('d M Y', strtotime($order['order_date'])); ?></span>
                    </div>
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">Order Date:</span>
                        <span class="invoice-meta-value"><?php echo date('d M Y, H:i', strtotime($order['order_date'])); ?></span>
                    </div>
                    <div class="status-badge">
                        Status: <?php echo htmlspecialchars(ucfirst($order['order_status'])); ?>
                    </div>
                </div>
            </div>

            <!-- Parties Section -->
            <div class="parties-section">
                <div class="party-box">
                    <div class="party-title">Bill To</div>
                    <div class="party-content">
                        <p><strong><?php echo htmlspecialchars($order['name']); ?></strong></p>
                        <p>Email: <?php echo htmlspecialchars($order['email']); ?></p>
                        <?php if (!empty($order['phone'])): ?>
                            <p>Phone: <?php echo htmlspecialchars($order['phone']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($order['delivery_address'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="party-box">
                    <div class="party-title">Payment Information</div>
                    <div class="party-content">
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? 'UPI'); ?></p>
                        <p><strong>Payment Status:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></p>
                        <?php if (!empty($order['utr_number'])): ?>
                            <p><strong>UTR Number:</strong> <?php echo htmlspecialchars($order['utr_number']); ?></p>
                        <?php endif; ?>
                        <p><strong>Order Date:</strong> <?php echo date('d M Y, H:i', strtotime($order['order_date'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Items Section -->
            <div class="items-section">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td>₹<?php echo number_format($item['product_price'], 2); ?></td>
                                <td><?php echo intval($item['quantity']); ?></td>
                                <td>₹<?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Section -->
            <div class="summary-section">
                <div class="summary-left">
                    <div class="summary-notes">
                        <div class="summary-notes-title">Terms & Conditions</div>
                        <p>Thank you for your purchase! Items will be dispatched within 1-2 business days. For queries, please contact support@epro-store.com</p>
                    </div>
                </div>

                <div class="summary-right">
                    <div class="summary-row">
                        <span class="summary-label">Subtotal:</span>
                        <span class="summary-value">₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <?php if ($tax_amount > 0): ?>
                        <div class="summary-row">
                            <span class="summary-label">Tax (GST):</span>
                            <span class="summary-value">₹<?php echo number_format($tax_amount, 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($discount_amount > 0): ?>
                        <div class="summary-row discount">
                            <span class="summary-label">Discount<?php if (!empty($order['coupon_code'])): ?> (<?php echo htmlspecialchars($order['coupon_code']); ?>)<?php endif; ?>:</span>
                            <span class="summary-value">-₹<?php echo number_format($discount_amount, 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row total">
                        <span class="summary-label">Total Amount:</span>
                        <span class="summary-value">₹<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="invoice-footer">
                <div class="footer-box">
                    <div class="footer-label">Authorized By</div>
                    <div class="footer-signature"></div>
                    <div class="footer-name">EPRO Management</div>
                </div>
                <div class="footer-box">
                    <div class="footer-label">Customer Signature</div>
                    <div class="footer-signature"></div>
                    <div class="footer-name"><?php echo htmlspecialchars($order['name']); ?></div>
                </div>
                <div class="footer-box">
                    <div class="footer-label">Generated Date</div>
                    <div style="margin-top: 20px; font-weight: 600;">
                        <?php echo date('d M Y, H:i'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function downloadInvoice() {
            const element = document.querySelector('.invoice-container');
            const opt = {
                margin: 10,
                filename: 'Invoice_<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
            };

            // For now, use print dialog as pdf library fallback
            showToast('Opening print dialog. Select "Print to PDF" to save.', 'info');
            window.print();
        }

        function showToast(message, type = 'success') {
            console.log(message);
        }
    </script>
</body>
</html>
