<!-- Payment Method Container -->
<div class="payment-card-box">
    <div class="payment-header">
        <i class="fa-solid fa-credit-card"></i> Payment Method
    </div>

    <!-- Dynamic Amount & UPI Info -->
    <div class="upi-container">
        <p class="scan-text">Scan QR code with any UPI App or choose direct payment</p>

        <!-- Dynamic QR Code (Auto-generates from amount) -->
        <div class="qr-wrapper" id="qrWrapperBox">
            <?php 
                $upi_id = "eprostore@okhdfcbank"; // Yahan apna UPI ID dalein
                $order_amount = isset($total_amount) ? (float)$total_amount : 34290.00;
                $upi_string = "upi://pay?pa=" . urlencode($upi_id) . "&pn=" . urlencode("E-PRO Store") . "&am=" . number_format($order_amount, 2, '.', '') . "&cu=INR&tn=" . urlencode("Order Payment");
                $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($upi_string);
            ?>
            <img 
                src="<?php echo $qr_url; ?>" 
                alt="UPI QR Code" 
                class="qr-code-img"
                onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=upi://pay?pa=<?php echo $upi_id; ?>';"
            >
        </div>

        <div class="amount-display">
            Amount: <span class="amount-val">₹<?php echo number_format($order_amount, 2); ?></span>
        </div>

        <!-- Copy UPI ID Pill -->
        <div class="upi-id-pill" onclick="copyUpiId('<?php echo $upi_id; ?>')">
            <span>UPI ID: <strong><?php echo $upi_id; ?></strong></span>
            <i class="fa-regular fa-copy" title="Copy UPI ID"></i>
        </div>

        <!-- Direct UPI Apps Section -->
        <div class="upi-apps-title">Pay Directly via App (Mobile Only):</div>
        <div class="upi-apps-grid">
            <!-- Google Pay -->
            <button type="button" class="app-btn gpay-btn" onclick="openUpiApp('gpay')">
                <i class="fa-brands fa-google-pay"></i> GPay
            </button>

            <!-- PhonePe -->
            <button type="button" class="app-btn phonepe-btn" onclick="openUpiApp('phonepe')">
                <i class="fa-solid fa-mobile-screen-button"></i> PhonePe
            </button>

            <!-- Paytm -->
            <button type="button" class="app-btn paytm-btn" onclick="openUpiApp('paytm')">
                <i class="fa-solid fa-wallet"></i> Paytm
            </button>

            <!-- Any UPI -->
            <button type="button" class="app-btn any-upi-btn" onclick="openUpiApp('upi')">
                <i class="fa-solid fa-bolt"></i> Any UPI
            </button>
        </div>
    </div>
</div>

<style>
/* Payment Box Glassmorphism Styling */
.payment-card-box {
    background: rgba(15, 23, 42, 0.65);
    border: 1px solid rgba(56, 189, 248, 0.25);
    border-radius: 18px;
    padding: 24px;
    backdrop-filter: blur(16px);
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.35);
    margin: 20px auto;
    max-width: 480px;
    color: #f8fafc;
}

.payment-header {
    font-size: 20px;
    font-weight: 700;
    color: #38bdf8;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 12px;
}

.upi-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.scan-text {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 16px;
}

/* QR Code Container with Neon Glow */
.qr-wrapper {
    background: #ffffff;
    padding: 12px;
    border-radius: 16px;
    box-shadow: 0 0 25px rgba(56, 189, 248, 0.25);
    display: inline-block;
    margin-bottom: 16px;
    transition: all 0.3s ease;
}

.qr-code-img {
    width: 180px;
    height: 180px;
    display: block;
    border-radius: 8px;
}

.amount-display {
    font-size: 15px;
    color: #cbd5e1;
    margin-bottom: 12px;
}

.amount-val {
    font-size: 20px;
    font-weight: 800;
    color: #34d399;
}

/* Copyable UPI Pill */
.upi-id-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid rgba(56, 189, 248, 0.3);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    color: #38bdf8;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 20px;
}

.upi-id-pill:hover {
    background: rgba(56, 189, 248, 0.15);
    transform: scale(1.03);
}

.upi-apps-title {
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* UPI Apps Grid */
.upi-apps-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    width: 100%;
}

.app-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
    text-decoration: none;
    color: white;
    border: none;
    cursor: pointer;
    outline: none;
    transition: transform 0.2s ease, filter 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    width: 100%;
}

.gpay-btn {
    background: linear-gradient(135deg, #1a73e8, #4285f4);
    font-size: 14px;
}
.phonepe-btn {
    background: linear-gradient(135deg, #5f259f, #7c3aed);
}
.paytm-btn {
    background: linear-gradient(135deg, #00b9f1, #0284c7);
}
.any-upi-btn {
    background: linear-gradient(135deg, #10b981, #059669);
}

.app-btn:hover {
    transform: translateY(-2px);
    filter: brightness(1.1);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
}

.app-btn:active {
    transform: translateY(0);
}
</style>

<script>
function openUpiApp(app) {
    const upiString = "<?php echo addslashes($upi_string); ?>";
    
    // Check if user is on a mobile device
    const isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    if (isMobile) {
        // Mobile browser directly triggers the UPI application
        window.location.href = upiString;
    } else {
        // Desktop browsers cannot handle upi:// intents directly
        if (typeof showToast === 'function') {
            showToast('UPI Apps are available on mobile phones. Please scan the QR code above to pay!', 'info');
        } else {
            alert('UPI Apps (GPay, PhonePe, Paytm) are open only on mobile device .\n\n please scan the QR code above to pay.');
        }

        // Highlight QR Code
        const qrBox = document.getElementById('qrWrapperBox');
        if (qrBox) {
            qrBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            qrBox.style.boxShadow = '0 0 35px #38bdf8';
            setTimeout(() => {
                qrBox.style.boxShadow = '0 0 25px rgba(56, 189, 248, 0.25)';
            }, 2000);
        }
    }
}

function copyUpiId(upiId) {
    navigator.clipboard.writeText(upiId).then(() => {
        if (typeof showToast === 'function') {
            showToast('UPI ID copied: ' + upiId, 'success');
        } else {
            alert('UPI ID copied to clipboard: ' + upiId);
        }
    }).catch(err => {
        console.error('Copy failed: ', err);
    });
}
</script>