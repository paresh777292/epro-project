/**
 * E-PRO E-Commerce Store - Core Client Script
 */

document.addEventListener("DOMContentLoaded", () => {
    console.log("E-PRO JS Loaded & Initialized Successfully ⚡");

    // 1. Auto-Dismiss Toast Notifications & Alerts
    const alerts = document.querySelectorAll(".toast-msg, .error-alert, .error");
    alerts.forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = "opacity 0.6s ease, transform 0.6s ease";
            alert.style.opacity = "0";
            alert.style.transform = "translateY(-10px)";
            setTimeout(() => alert.remove(), 600);
        }, 3500);
    });

    // 2. Smooth Scrolling for Anchor Links (e.g., #about)
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener("click", function (e) {
            const targetId = this.getAttribute("href");
            if (targetId.length > 1) {
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    e.preventDefault();
                    targetElement.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });
                }
            }
        });
    });

    // 3. Confirm Item Removal from Cart
    const deleteButtons = document.querySelectorAll(".btn-delete-cart, .btn-remove");
    deleteButtons.forEach((btn) => {
        btn.addEventListener("click", (e) => {
            const confirmDelete = confirm("Kya aap iss item ko cart se hatana chahte hain?");
            if (!confirmDelete) {
                e.preventDefault();
            }
        });
    });

    // 4. Client-side Form Validation Helpers
    const signupForm = document.querySelector("form[action*='signup.php']");
    if (signupForm) {
        signupForm.addEventListener("submit", (e) => {
            const password = signupForm.querySelector("input[name='password']");
            if (password && password.value.length < 4) {
                alert("Password kam se kam 4 characters ka hona chahiye!");
                e.preventDefault();
            }
        });
    }
});

/**
 * 5. Interactive Product Image & Variant Switcher
 * Used across product detail showcases (product1.php, product2.php, etc.)
 */
function changeImage(fullSrc, relativePath, element) {
    const mainImg = document.getElementById("mainImage");
    const hiddenInput = document.getElementById("selectedImageInput");

    if (mainImg) {
        mainImg.style.opacity = "0.3";
        setTimeout(() => {
            mainImg.src = fullSrc;
            mainImg.style.opacity = "1";
        }, 150);
    }

    if (hiddenInput) {
        hiddenInput.value = relativePath;
    }

    const allThumbs = document.querySelectorAll(".thumb-btn");
    allThumbs.forEach((btn) => btn.classList.remove("active"));
    if (element) {
        element.classList.add("active");
    }
}

/**
 * 6. Payment Method Selector Handler (payment.php / checkout.php)
 */
function selectMethod(type) {
    const radioBtn = document.getElementById(type);
    if (radioBtn) {
        radioBtn.checked = true;
    }

    const gpayBox = document.getElementById("gpay_details");
    const codBox = document.getElementById("cod_details");

    if (gpayBox) {
        gpayBox.style.display = type === "gpay" ? "block" : "none";
    }
    if (codBox) {
        codBox.style.display = type === "cod" ? "block" : "none";
    }
}