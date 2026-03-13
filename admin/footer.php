    </div> <!-- End p-4 -->
</div> <!-- End main-content -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/script.js"></script>
    
    <?php if(basename($_SERVER['PHP_SELF']) == 'index.php' && isset($chart_labels)): ?>
    <script>
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        if(ctx) {
            // Create Gradient
            const gradient = ctx.createLinearGradient(0, 0, 0, 250);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0.01)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chart_labels) ?>,
                    datasets: [{
                        label: 'Jumlah Hadir',
                        data: <?= json_encode($chart_data) ?>,
                        borderWidth: 4,
                        borderColor: '#3b82f6',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.45,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            padding: 12,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 },
                            displayColors: false,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: 'rgba(0,0,0,0.03)' },
                            ticks: { 
                                stepSize: 1,
                                font: { size: 11 }
                            }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
