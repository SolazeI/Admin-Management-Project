// dashboard.js — allData is defined in the Blade template before this file loads

let chart;

function fmt(v) {
    if (v >= 1000000) return '₱' + (v / 1000000).toFixed(1) + 'M';
    if (v >= 1000)    return '₱' + Math.round(v / 1000) + 'K';
    return '₱' + v;
}

//Chart builder
function buildChart(months) {
    const labels  = allData.labels.slice(-months);
    const revenue = allData.revenue.slice(-months);
    const profit  = allData.profit.slice(-months);

    if (chart) chart.destroy();

    if (!revenue.length) {
        document.getElementById('revenueChart').style.display = 'none';
        return;
    }

    document.getElementById('revenueChart').style.display = '';

    chart = new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Trip Revenue',
                    data: revenue,
                    backgroundColor: '#1e3a8a',
                    borderRadius: 5,
                    borderSkipped: false,
                    barPercentage: .55,
                    categoryPercentage: .75,
                },
                {
                    label: 'Net Profit',
                    data: profit,
                    backgroundColor: '#60a5fa',
                    borderRadius: 5,
                    borderSkipped: false,
                    barPercentage: .55,
                    categoryPercentage: .75,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: { label: ctx => ' ' + fmt(ctx.raw) },
                    backgroundColor: '#0f1a2e',
                    titleColor: '#fff',
                    bodyColor: '#94a3b8',
                    padding: 10,
                    cornerRadius: 8,
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 11 }, color: '#64748b' },
                    border: { display: false },
                },
                y: {
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { family: 'Poppins', size: 10 }, color: '#64748b', callback: v => fmt(v) },
                    border: { display: false },
                }
            }
        }
    });
}

function setRange(n, btn) {
    document.querySelectorAll('.filter-range').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('range-label').textContent =
        n === 12 ? 'Last 12 Months' : n === 9 ? 'Last 9 Months' : 'Last 6 Months';
    buildChart(n);
}

buildChart(6);