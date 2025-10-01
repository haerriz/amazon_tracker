// Product Page JavaScript
class ProductPage {
    constructor() {
        this.chart = null;
        this.init();
    }

    init() {
        this.initChart();
        this.initChartControls();
        this.initScoreCircles();
        this.initPriceAlert();
    }

    initChart() {
        const ctx = document.getElementById('priceChart');
        if (!ctx || !window.productData) return;

        const data = window.productData.priceHistory;
        
        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => new Date(item.date).toLocaleDateString()),
                datasets: [{
                    label: 'Price (₹)',
                    data: data.map(item => item.price),
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#4f46e5',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return `Price: ₹${context.parsed.y.toLocaleString()}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 6
                        }
                    },
                    y: {
                        display: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    initChartControls() {
        const buttons = document.querySelectorAll('.chart-btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Remove active class from all buttons
                buttons.forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                e.target.classList.add('active');
                
                const period = parseInt(e.target.dataset.period);
                this.updateChartPeriod(period);
            });
        });
    }

    updateChartPeriod(days) {
        if (!this.chart || !window.productData) return;

        const allData = window.productData.priceHistory;
        const cutoffDate = new Date();
        cutoffDate.setDate(cutoffDate.getDate() - days);

        const filteredData = allData.filter(item => 
            new Date(item.date) >= cutoffDate
        );

        this.chart.data.labels = filteredData.map(item => 
            new Date(item.date).toLocaleDateString()
        );
        this.chart.data.datasets[0].data = filteredData.map(item => item.price);
        
        this.chart.update('active');
    }

    initScoreCircles() {
        const circles = document.querySelectorAll('.score-circle');
        circles.forEach(circle => {
            const score = parseInt(circle.dataset.score) || 0;
            circle.style.setProperty('--score', score);
            
            // Animate the circle
            setTimeout(() => {
                circle.style.transform = `rotate(${score * 3.6}deg)`;
            }, 500);
        });
    }

    initPriceAlert() {
        const alertBtn = document.querySelector('.btn-primary');
        if (alertBtn) {
            alertBtn.addEventListener('click', () => {
                const price = document.getElementById('alertPrice').value;
                if (price && window.productData) {
                    this.setPriceAlert(window.productData.asin, price);
                }
            });
        }
    }

    async setPriceAlert(asin, targetPrice) {
        try {
            const response = await fetch('backend/api/products.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    asin: asin,
                    target_price: targetPrice,
                    action: 'set_alert'
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Price alert set successfully!', 'success');
            } else {
                this.showNotification('Failed to set price alert', 'error');
            }
        } catch (error) {
            console.error('Error setting price alert:', error);
            this.showNotification('Error setting price alert', 'error');
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Style the notification
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            background: type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#4f46e5',
            color: 'white',
            padding: '1rem 1.5rem',
            borderRadius: '8px',
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
            zIndex: '1000',
            transform: 'translateX(100%)',
            transition: 'transform 0.3s ease'
        });
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
}

// Global function for price alert (called from PHP)
window.setPriceAlert = function(asin) {
    const price = document.getElementById('alertPrice').value;
    if (window.productPageInstance) {
        window.productPageInstance.setPriceAlert(asin, price);
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.productPageInstance = new ProductPage();
});

// Add structured data for SEO
function addStructuredData() {
    if (!window.productData) return;
    
    const structuredData = {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": window.productData.title,
        "image": document.querySelector('.product-image img')?.src,
        "description": `Price history and deals for ${window.productData.title}`,
        "brand": {
            "@type": "Brand",
            "name": "Amazon"
        },
        "offers": {
            "@type": "Offer",
            "url": window.location.href,
            "priceCurrency": "INR",
            "price": window.productData.currentPrice,
            "priceValidUntil": new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            "availability": "https://schema.org/InStock",
            "seller": {
                "@type": "Organization",
                "name": "Amazon"
            }
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.5",
            "reviewCount": "100"
        }
    };
    
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.textContent = JSON.stringify(structuredData);
    document.head.appendChild(script);
}

// Add structured data when page loads
document.addEventListener('DOMContentLoaded', addStructuredData);