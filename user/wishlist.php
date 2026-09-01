<?php
/**
 * Wishlist Page - E-PRO
 * Display user's wishlist items with Move to Cart functionality
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Fetch wishlist items
$wishlist_items = [];
$query = "
    SELECT 
        p.id,
        p.name,
        p.price,
        p.image,
        p.category,
        pr.id as reaction_id
    FROM product_reactions pr
    JOIN products p ON pr.product_id = p.id
    WHERE pr.reaction_type = 'wishlist' 
    AND (pr.user_id = ? OR (pr.user_id IS NULL AND pr.user_ip = ?))
    ORDER BY pr.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $user_ip);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $wishlist_items[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - E-PRO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/EPRO/assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 40px;
            padding: 32px;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.1) 0%, rgba(129, 140, 248, 0.1) 100%);
            border: 1px solid #38bdf825;
            border-radius: 16px;
            backdrop-filter: blur(10px);
        }

        .header-icon {
            font-size: 48px;
            color: #38bdf8;
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #38bdf815;
            border-radius: 12px;
        }

        .header-content h1 {
            font-size: 32px;
            color: #38bdf8;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .header-content p {
            color: #94a3b8;
            font-size: 14px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.05) 0%, rgba(129, 140, 248, 0.05) 100%);
            border: 2px dashed #38bdf840;
            border-radius: 16px;
            backdrop-filter: blur(10px);
        }

        .empty-state-icon {
            font-size: 80px;
            color: #38bdf840;
            margin-bottom: 24px;
            display: inline-block;
        }

        .empty-state h2 {
            font-size: 24px;
            color: #e2e8f0;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: #94a3b8;
            margin-bottom: 32px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-continue-shopping {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%);
            color: #0f172a;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 15px #38bdf840;
        }

        .btn-continue-shopping:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px #38bdf8;
        }

        /* Wishlist Grid */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .wishlist-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid #38bdf825;
            border-radius: 14px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .wishlist-card:hover {
            border-color: #38bdf8;
            transform: translateY(-8px);
            box-shadow: 0 12px 40px #38bdf820;
        }

        .card-image-container {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
            overflow: hidden;
            background: #0f172a;
        }

        .card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .wishlist-card:hover .card-image {
            transform: scale(1.05);
        }

        .remove-from-wishlist-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(239, 68, 68, 0.9);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.2s;
            backdrop-filter: blur(10px);
        }

        .remove-from-wishlist-btn:hover {
            background: rgba(239, 68, 68, 1);
            transform: scale(1.1);
        }

        .card-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-category {
            font-size: 12px;
            color: #818cf8;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 12px;
            line-height: 1.3;
            flex: 1;
        }

        .card-price {
            font-size: 20px;
            color: #38bdf8;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .card-actions {
            display: flex;
            gap: 12px;
        }

        .btn-move-to-cart {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%);
            color: #0f172a;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px #38bdf840;
        }

        .btn-move-to-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px #38bdf8;
        }

        .btn-move-to-cart:active {
            transform: translateY(0);
        }

        .btn-move-to-cart.loading {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                padding: 24px;
            }

            .header-content h1 {
                font-size: 24px;
            }

            .wishlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 16px;
            }

            .empty-state {
                padding: 60px 20px;
            }
        }

        @media (max-width: 480px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
            }

            .card-title {
                font-size: 15px;
            }

            .card-price {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="header-content">
                <h1>My Wishlist</h1>
                <p><?php echo count($wishlist_items); ?> item<?php echo count($wishlist_items) !== 1 ? 's' : ''; ?> saved</p>
            </div>
        </div>

        <!-- Wishlist Content -->
        <?php if (empty($wishlist_items)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-heart-broken"></i>
                </div>
                <h2>Your wishlist is empty</h2>
                <p>Start adding your favorite products to your wishlist and they'll appear here!</p>
                <a href="/EPRO/user/products.php" class="btn-continue-shopping">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <!-- Wishlist Grid -->
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="wishlist-card" data-product-id="<?php echo $item['id']; ?>">
                        <div class="card-image-container">
                            <img 
                                src="/EPRO/assets/images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                alt="<?php echo htmlspecialchars($item['name']); ?>"
                                class="card-image"
                                onerror="this.src='/EPRO/assets/images/placeholder.png'"
                            >
                            <button class="remove-from-wishlist-btn" title="Remove from wishlist" 
                                data-product-id="<?php echo $item['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="card-content">
                            <span class="card-category"><?php echo htmlspecialchars($item['category']); ?></span>
                            <h3 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <div class="card-price">₹<?php echo number_format($item['price'], 2); ?></div>
                            <div class="card-actions">
                                <button class="btn-move-to-cart" data-product-id="<?php echo $item['id']; ?>">
                                    <i class="fas fa-shopping-cart"></i> Move to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Include Toast Notification System -->
    <script src="/EPRO/assets/js/toast.js"></script>

    <script>
        /**
         * Wishlist Page Manager
         */
        const WishlistPageManager = {
            init() {
                // Move to cart buttons
                document.querySelectorAll('.btn-move-to-cart').forEach(btn => {
                    btn.addEventListener('click', (e) => this.moveToCart(e.target.closest('button')));
                });

                // Remove buttons
                document.querySelectorAll('.remove-from-wishlist-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => this.removeItem(e.target.closest('button')));
                });
            },

            moveToCart(btn) {
                if (btn.classList.contains('loading')) return;

                const productId = btn.dataset.productId;
                btn.classList.add('loading');
                btn.disabled = true;

                const formData = new FormData();
                formData.append('action', 'move_to_cart');
                formData.append('product_id', productId);

                fetch('/EPRO/api/wishlist_handler.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            // Remove card after delay
                            setTimeout(() => {
                                const card = document.querySelector(`[data-product-id="${productId}"]`);
                                if (card) {
                                    card.style.animation = 'fadeOut 0.3s forwards';
                                    setTimeout(() => card.remove(), 300);

                                    // Check if wishlist is now empty
                                    if (document.querySelectorAll('.wishlist-card').length === 0) {
                                        location.reload();
                                    }
                                }
                            }, 500);
                        } else {
                            showToast(data.message || 'Error moving to cart', 'error');
                            btn.classList.remove('loading');
                            btn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to move to cart', 'error');
                        btn.classList.remove('loading');
                        btn.disabled = false;
                    });
            },

            removeItem(btn) {
                if (!confirm('Remove this item from wishlist?')) return;

                const productId = btn.dataset.productId;
                const formData = new FormData();
                formData.append('action', 'remove_from_wishlist');
                formData.append('product_id', productId);

                fetch('/EPRO/api/wishlist_handler.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            // Remove card with animation
                            const card = document.querySelector(`[data-product-id="${productId}"]`);
                            if (card) {
                                card.style.animation = 'fadeOut 0.3s forwards';
                                setTimeout(() => {
                                    card.remove();
                                    // Check if wishlist is now empty
                                    if (document.querySelectorAll('.wishlist-card').length === 0) {
                                        location.reload();
                                    }
                                }, 300);
                            }
                        } else {
                            showToast(data.message || 'Error removing item', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to remove item', 'error');
                    });
            }
        };

        // Add fadeOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                to {
                    opacity: 0;
                    transform: translateY(20px);
                }
            }
        `;
        document.head.appendChild(style);

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => WishlistPageManager.init());
    </script>
</body>
</html>
