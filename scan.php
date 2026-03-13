<?php
require_once 'includes/db.php';
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php?next=" . urlencode('scan.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner Absensi - <?= htmlspecialchars($app_settings['app_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="scan-page d-flex flex-column h-100 min-vh-100">

<!-- Topbar -->
<div class="px-4 py-3 border-bottom border-light border-opacity-10 d-flex justify-content-between align-items-center" style="background: rgba(0,0,0,0.2); backdrop-filter: blur(10px);">
    <h5 class="mb-0 fw-bold text-white"><i class="fas fa-qrcode me-2" style="color: #3b82f6;"></i> Absen Digital</h5>
    <div>
        <span class="badge fw-medium fs-6 py-2 px-3 me-2" id="clock" style="background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); color: #93c5fd;">00:00:00</span>
        <a href="admin/index.php" class="btn btn-outline-light btn-sm rounded-pill px-3 me-2">
            <i class="fas fa-gauge"></i> Dashboard
        </a>
        <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
            <i class="fas fa-right-from-bracket"></i> Logout
        </a>
    </div>
</div>

<div class="container flex-grow-1 d-flex flex-column justify-content-center align-items-center py-4">
    <div class="text-center mb-4 mt-2">
        <h2 class="fw-bold mb-2 text-white" style="letter-spacing: -0.5px;"><?= htmlspecialchars($app_settings['app_name']) ?></h2>
        <p class="text-white-50" style="font-size: 1.1rem;">Arahkan QR Code Kartu ke kamera</p>
    </div>

    <div class="scan-container m-0 w-100 p-2" style="max-width: 480px;">
        <div class="scanner-wrapper position-relative overflow-hidden" style="border-radius: 24px;">
            <!-- Scanner Container -->
            <div class="scanner-wrap">
                <div id="reader" class="scanner-box"></div>
                <div class="scanner-overlay"></div>
                <div class="scanner-scanner"></div>
            </div>

            <!-- Result Box Overlay -->
            <div class="scan-result-card text-center shadow-lg" id="result-card" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 100; background: rgba(255,255,255,0.95); backdrop-filter: blur(12px); border-radius: 24px; padding: 30px; border: 2px solid rgba(59, 130, 246, 0.1);">
                <div class="d-flex flex-column h-100 justify-content-center align-items-center">
                    <div id="result-icon-bg" class="result-icon-wrapper mb-3">
                        <i id="result-icon"></i>
                    </div>
                    <h4 class="fw-bold mt-2 mb-2" id="result-title">Berhasil</h4>
                    <div id="result-message" class="mb-0">Memproses...</div>
                    <div id="resume-container"></div>
                </div>
            </div>
        </div>
        
        <!-- Announcement Container -->
        <?php if(!empty($app_settings['scanner_announcement'])): ?>
        <div class="mt-4 announcement-box shadow-sm">
            <div class="d-flex align-items-center bg-white rounded-pill px-4 py-2 border overflow-hidden position-relative">
                <div class="announcement-icon me-3 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="marquee-text-wrap w-100 overflow-hidden">
                    <div class="marquee-text fw-bold text-dark" style="font-size: 1.1rem;">
                        <?= htmlspecialchars($app_settings['scanner_announcement']) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .announcement-box {
                max-width: 600px;
                margin: 0 auto;
                min-height: 60px;
                z-index: 100;
                position: relative;
            }
            .marquee-text-wrap {
                white-space: nowrap;
                position: relative;
            }
            .marquee-text {
                display: inline-block;
                padding-left: 100%;
                animation: marquee 15s linear infinite;
            }
            @keyframes marquee {
                0%   { transform: translateX(0); }
                100% { transform: translateX(-100%); }
            }
            .announcement-icon {
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
            }
        </style>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- HTML5 QR Code Library -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<!-- JS Confetti -->
<script src="https://cdn.jsdelivr.net/npm/js-confetti@latest/dist/js-confetti.browser.js"></script>

<script>
const jsConfetti = new JSConfetti();
function updateClock() {
    var now = new Date();
    var h = ('0' + now.getHours()).slice(-2);
    var m = ('0' + now.getMinutes()).slice(-2);
    var s = ('0' + now.getSeconds()).slice(-2);
    document.getElementById('clock').textContent = h + ':' + m + ':' + s;
}
setInterval(updateClock, 1000);
updateClock();

let isScanning = true;
let html5QrcodeScanner = null;

function showResult(status, message, resultData = null) {
    const resultCard = document.getElementById('result-card');
    const resultIcon = document.getElementById('result-icon');
    const resultIconBg = document.getElementById('result-icon-bg');
    const resultTitle = document.getElementById('result-title');
    const resultMsg = document.getElementById('result-message');
    
    // Create elements for additional data
    let extraDataHtml = '';
    if (resultData && resultData.nis) {
        
        // Cek jika ada foto
        let photoHtml = '';
        if(resultData.photo) {
            photoHtml = `
            <div class="text-center mb-2 mt-4">
                <img src="assets/images/${resultData.photo}" alt="Foto" width="90" height="90" class="rounded-circle object-fit-cover border shadow-sm" style="border-width: 3px !important; border-color: rgba(255,255,255,0.5) !important;">
            </div>`;
        }
        
        extraDataHtml = photoHtml + `
            <div class="mt-3 py-2 px-3 rounded text-start" style="background: rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05);">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small">Nama:</span>
                    <span class="fw-bold text-dark">${resultData.name}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small">NIS:</span>
                    <span class="fw-medium text-dark">${resultData.nis}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted small">Kelas:</span>
                    <span class="fw-medium text-dark">${resultData.class_name}</span>
                </div>
            </div>
        `;
    }

    resultCard.style.display = 'block';
    
    // Reset Classes
    resultIconBg.className = 'result-icon-wrapper mb-3';
    resultIcon.className = '';
    resultTitle.className = 'fw-bold mt-2 mb-2';

    if(status == 'success') {
        resultIconBg.classList.add('success-bg', 'success-theme');
        resultIcon.classList.add('fas', 'fa-check');
        resultTitle.classList.add('success-theme');
        resultTitle.textContent = 'Absen Berhasil!';
        
        // Fire Confetti!
        jsConfetti.addConfetti({
            emojis: ['🎉', '✨', '👏', '🏫'],
            confettiNumber: 40,
        });

    } else if (status == 'warning') {
        resultIconBg.classList.add('warning-bg', 'warning-theme');
        resultIcon.classList.add('fas', 'fa-exclamation');
        resultTitle.classList.add('warning-theme');
        resultTitle.textContent = 'Sudah Absen';
    } else {
        resultIconBg.classList.add('error-bg', 'error-theme');
        resultIcon.classList.add('fas', 'fa-times');
        resultTitle.classList.add('error-theme');
        resultTitle.textContent = 'Akses Ditolak';
    }
    resultMsg.innerHTML = message + extraDataHtml;
    
    // Add resume button if not already there
    const resumeContainer = document.getElementById('resume-container');
    resumeContainer.innerHTML = '';
    
    const btnResume = document.createElement('button');
    btnResume.id = 'btn-resume';
    btnResume.className = 'btn btn-primary rounded-pill px-5 fw-bold mt-4 shadow-sm';
    btnResume.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Scan Lagi';
    btnResume.onclick = function() {
        resultCard.style.display = 'none';
        isScanning = true;
        if (html5QrcodeScanner) {
            html5QrcodeScanner.resume();
        }
    };
    resumeContainer.appendChild(btnResume);
}

function onScanSuccess(decodedText, decodedResult) {
    if(!isScanning) return;
    isScanning = false; 
    
    // Play beep
    let audio = new Audio('https://www.soundjay.com/buttons/button-09.mp3');
    audio.play().catch(e => {});

    fetch('process_scan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'qrcode=' + encodeURIComponent(decodedText)
    })
    .then(response => response.json())
    .then(data => {
        let msgHtml = data.message.split(':')[0] + '!'; 
        
        // Pause camera on success or warning
        if (data.status != 'error' && html5QrcodeScanner) {
            html5QrcodeScanner.pause();
        }
        
        showResult(data.status, `<strong class="fs-5 d-block text-dark">${msgHtml}</strong>`, data.data);
    })
    .catch(error => {
        showResult('error', 'Koneksi ke server gagal.');
        console.error(error);
    });
}

function onScanFailure(error) {
    // ignore
}

document.addEventListener("DOMContentLoaded", function() {
    html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", 
        { fps: 15, qrbox: {width: 250, height: 250}, aspectRatio: 1.0 },
        false
    );
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    
    // Style adjustments for the html5-qrcode buttons
    setTimeout(() => {
        document.querySelectorAll('#reader button').forEach(btn => {
            btn.classList.add('btn', 'btn-outline-light', 'btn-sm', 'm-1', 'rounded-pill', 'px-4');
            btn.style.borderColor = 'rgba(255,255,255,0.3)';
        });
        document.querySelectorAll('#reader select').forEach(sel => {
            sel.classList.add('form-select', 'form-select-sm', 'd-inline-block', 'w-auto', 'mx-auto', 'mb-2');
        });
        let aLinks = document.querySelectorAll('#reader a');
        aLinks.forEach(a => {
            a.style.display = 'none'; // hide the watermark
        });
    }, 1500);
});
</script>
</body>
</html>
