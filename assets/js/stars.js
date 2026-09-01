/**
 * Star Rating Component
 * Display and interact with star ratings
 * Usage:
 * - Display: new StarRating('#container', rating, isInteractive=false)
 * - Interactive: new StarRating('#form-container', 0, true, callback)
 */

class StarRating {
    constructor(selector, rating = 0, interactive = false, onChange = null) {
        this.container = typeof selector === 'string' ? 
            document.querySelector(selector) : selector;
        
        if (!this.container) {
            console.error('StarRating container not found:', selector);
            return;
        }

        this.rating = Math.round(rating * 2) / 2; // Round to nearest 0.5
        this.interactive = interactive;
        this.onChange = onChange;
        this.hoveredRating = 0;

        this.render();
        
        if (interactive) {
            this.attachEvents();
        }
    }

    /**
     * Get star HTML based on rating
     */
    getStarHTML(index) {
        const starValue = index + 1;
        let fillPercentage = 0;

        if (this.interactive && this.hoveredRating > 0) {
            // Show hovered state
            fillPercentage = this.hoveredRating >= starValue ? 100 : 
                            this.hoveredRating - index > 0 ? 
                            (this.hoveredRating - index) * 100 : 0;
        } else {
            // Show actual rating
            fillPercentage = this.rating >= starValue ? 100 : 
                            this.rating - index > 0 ? 
                            (this.rating - index) * 100 : 0;
        }

        const fillId = `star-fill-${index}-${Math.random().toString(36).substr(2, 9)}`;

        return `
            <svg class="star" data-value="${starValue}" viewBox="0 0 24 24" width="24" height="24">
                <defs>
                    <linearGradient id="${fillId}" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="${fillPercentage}%" stop-color="currentColor" />
                        <stop offset="${fillPercentage}%" stop-color="#d1d5db" />
                    </linearGradient>
                </defs>
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" 
                      fill="url(#${fillId})" stroke="currentColor" stroke-width="0.5"/>
            </svg>
        `;
    }

    /**
     * Render stars to DOM
     */
    render() {
        let starsHTML = '';
        for (let i = 0; i < 5; i++) {
            starsHTML += this.getStarHTML(i);
        }

        this.container.innerHTML = `
            <div class="star-rating ${this.interactive ? 'interactive' : 'static'}">
                <div class="stars-container">
                    ${starsHTML}
                </div>
                <span class="rating-text">${this.rating}/5</span>
            </div>
        `;
    }

    /**
     * Attach mouse events for interactive mode
     */
    attachEvents() {
        const stars = this.container.querySelectorAll('.star');

        stars.forEach(star => {
            star.addEventListener('mouseover', (e) => this.handleHover(e));
            star.addEventListener('mouseleave', () => this.handleLeave());
            star.addEventListener('click', (e) => this.handleClick(e));
        });
    }

    /**
     * Handle star hover
     */
    handleHover(e) {
        const value = parseInt(e.target.closest('.star').dataset.value);
        this.hoveredRating = value;
        this.render();
        this.attachEvents(); // Re-attach after re-render
    }

    /**
     * Handle mouse leave
     */
    handleLeave() {
        this.hoveredRating = 0;
        this.render();
        this.attachEvents();
    }

    /**
     * Handle star click
     */
    handleClick(e) {
        const value = parseInt(e.target.closest('.star').dataset.value);
        this.rating = value;
        this.hoveredRating = 0;
        this.render();
        this.attachEvents();

        if (this.onChange) {
            this.onChange(value);
        }
    }

    /**
     * Set rating programmatically
     */
    setRating(value) {
        this.rating = Math.round(value * 2) / 2;
        this.render();
        if (this.interactive) {
            this.attachEvents();
        }
    }

    /**
     * Get current rating
     */
    getRating() {
        return this.rating;
    }
}

/**
 * Helper function to create a mini star display
 * @param {number} rating - Rating value (1-5)
 * @param {boolean} showText - Show rating text
 * @returns {string} HTML string
 */
function createStarDisplay(rating, showText = true) {
    const filledStars = Math.floor(rating);
    const hasHalf = rating % 1 >= 0.5;
    const emptyStars = 5 - filledStars - (hasHalf ? 1 : 0);

    let html = '<div class="star-display">';

    // Filled stars
    for (let i = 0; i < filledStars; i++) {
        html += '<i class="fas fa-star" style="color: #fbbf24;"></i>';
    }

    // Half star
    if (hasHalf) {
        html += '<i class="fas fa-star-half-alt" style="color: #fbbf24;"></i>';
    }

    // Empty stars
    for (let i = 0; i < emptyStars; i++) {
        html += '<i class="far fa-star" style="color: #d1d5db;"></i>';
    }

    if (showText) {
        html += `<span class="rating-text" style="margin-left: 6px; font-size: 12px; font-weight: 600; color: #374151;">${rating.toFixed(1)}/5</span>`;
    }

    html += '</div>';
    return html;
}

/**
 * CSS Styles for Star Rating Component
 */
const starRatingStyles = `
    .star-rating {
        display: flex;
        align-items: center;
        gap: 12px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .star-rating.interactive {
        cursor: pointer;
    }

    .stars-container {
        display: flex;
        gap: 4px;
        align-items: center;
    }

    .star {
        width: 24px;
        height: 24px;
        cursor: pointer;
        color: #fbbf24;
        transition: transform 0.1s;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
    }

    .star-rating.interactive .star:hover {
        transform: scale(1.15);
    }

    .rating-text {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
    }

    .star-display {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .star-display i {
        font-size: 16px;
    }
`;

// Auto-inject styles if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        const style = document.createElement('style');
        style.textContent = starRatingStyles;
        document.head.appendChild(style);
    });
} else {
    const style = document.createElement('style');
    style.textContent = starRatingStyles;
    document.head.appendChild(style);
}
