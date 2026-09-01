/**
 * Toast Notification System
 * Modern animated floating notifications with auto-dismiss
 * Usage: showToast('Message', 'success|error|warning|info')
 */

const ToastManager = {
  container: null,

  init() {
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      this.container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        pointer-events: none;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      `;
      document.body.appendChild(this.container);
    }
  },

  create(message, type = 'success') {
    this.init();

    const toastEl = document.createElement('div');
    const toastId = `toast-${Date.now()}`;
    toastEl.id = toastId;
    toastEl.className = `toast toast-${type}`;

    // Color scheme based on type
    const colors = {
      success: { bg: '#10b981', icon: '✓', light: '#d1fae5' },
      error: { bg: '#ef4444', icon: '✕', light: '#fee2e2' },
      warning: { bg: '#f59e0b', icon: '⚠', light: '#fef3c7' },
      info: { bg: '#3b82f6', icon: 'ℹ', light: '#dbeafe' }
    };

    const config = colors[type] || colors.success;

    toastEl.style.cssText = `
      display: flex;
      align-items: center;
      gap: 12px;
      background: linear-gradient(135deg, ${config.bg}15 0%, ${config.bg}08 100%);
      backdrop-filter: blur(10px);
      border: 1px solid ${config.bg}30;
      color: #1f2937;
      padding: 14px 18px;
      border-radius: 12px;
      margin-bottom: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      min-width: 300px;
      max-width: 400px;
      word-wrap: break-word;
      pointer-events: auto;
      animation: slideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
      font-size: 14px;
      font-weight: 500;
      letter-spacing: 0.3px;
    `;

    // Icon
    const icon = document.createElement('span');
    icon.style.cssText = `
      flex-shrink: 0;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: ${config.bg};
      color: white;
      border-radius: 50%;
      font-size: 12px;
      font-weight: bold;
    `;
    icon.textContent = config.icon;

    // Message
    const msgSpan = document.createElement('span');
    msgSpan.style.cssText = `
      flex: 1;
      line-height: 1.4;
    `;
    msgSpan.textContent = message;

    // Close button
    const closeBtn = document.createElement('button');
    closeBtn.style.cssText = `
      background: none;
      border: none;
      color: #6b7280;
      cursor: pointer;
      font-size: 18px;
      padding: 0;
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      transition: color 0.2s;
    `;
    closeBtn.innerHTML = '×';
    closeBtn.onmouseover = () => { closeBtn.style.color = '#1f2937'; };
    closeBtn.onmouseout = () => { closeBtn.style.color = '#6b7280'; };
    closeBtn.onclick = () => this.remove(toastId);

    toastEl.appendChild(icon);
    toastEl.appendChild(msgSpan);
    toastEl.appendChild(closeBtn);
    this.container.appendChild(toastEl);

    // Auto-dismiss after 3 seconds
    setTimeout(() => this.remove(toastId), 3000);

    return toastId;
  },

  remove(toastId) {
    const toastEl = document.getElementById(toastId);
    if (toastEl) {
      toastEl.style.animation = 'slideOut 0.3s cubic-bezier(0.64, 0, 0.78, 0) forwards';
      setTimeout(() => toastEl.remove(), 300);
    }
  }
};

// Global function for easy access
function showToast(message, type = 'success') {
  return ToastManager.create(message, type);
}

// Add animations to stylesheet
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }

  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(400px);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);
