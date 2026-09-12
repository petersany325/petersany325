@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script defer>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;
    var palette = ['#14b8a6','#f59e0b','#3b82f6','#ef4444','#a78bfa','#22c55e','#fb7185','#94a3b8','#eab308','#06b6d4'];
    document.querySelectorAll('canvas[data-chart-labels]').forEach(function (el) {
        try {
            var labels = JSON.parse(el.getAttribute('data-chart-labels') || '[]');
            var values = JSON.parse(el.getAttribute('data-chart-values') || '[]');
            var type = el.getAttribute('data-chart-type') || 'bar';
            if (!labels.length) return;
            var colors = labels.map(function (_, i) { return palette[i % palette.length]; });
            new Chart(el.getContext('2d'), {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'مقدار',
                        data: values,
                        backgroundColor: type === 'line' ? 'rgba(20,184,166,.25)' : colors,
                        borderColor: type === 'line' ? '#14b8a6' : colors,
                        borderWidth: type === 'line' ? 2 : 1,
                        fill: type === 'line',
                        tension: 0.25
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: type === 'doughnut', position: 'bottom', labels: { color: '#d6c7a8', boxWidth: 12 } }
                    },
                    scales: type === 'doughnut' ? {} : {
                        x: { ticks: { color: '#b9a88a', maxRotation: 45, minRotation: 0 }, grid: { color: 'rgba(255,255,255,.06)' } },
                        y: { ticks: { color: '#b9a88a' }, grid: { color: 'rgba(255,255,255,.06)' }, beginAtZero: true }
                    }
                }
            });
        } catch (e) {}
    });
});
</script>
@endpush
