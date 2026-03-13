<?php
require_once 'header.php';

$current_role = $_SESSION['admin_role'] ?? 'admin';
$is_admin = $current_role === 'admin';
$current_user_id = (int)($_SESSION['admin_id'] ?? 0);

$guru_res = $conn->query("SELECT id, name, phone, role FROM users WHERE role IN ('guru','admin') ORDER BY (role = 'admin') ASC, name ASC");
$guru_options = [];
if ($guru_res && $guru_res->num_rows > 0) {
    while ($g = $guru_res->fetch_assoc()) {
        $guru_options[] = $g;
    }
}

// Handle Delete Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmtSantri = $conn->prepare("SELECT photo, teacher_id FROM santri WHERE id = ? LIMIT 1");
    if ($stmtSantri) {
        $stmtSantri->bind_param("i", $id);
        $stmtSantri->execute();
        $stmtSantri->bind_result($photo_to_delete, $teacher_id_of_row);
        $found = $stmtSantri->fetch();
        $stmtSantri->close();
        if ($found) {
            if (!$is_admin && (int)$teacher_id_of_row !== $current_user_id) {
                echo "<script>window.location='santri.php?msg=unauthorized';</script>";
                exit;
            }
            if (!empty($photo_to_delete) && file_exists("../assets/images/" . $photo_to_delete)) {
                unlink("../assets/images/" . $photo_to_delete);
            }
            $stmtDel = $conn->prepare("DELETE FROM santri WHERE id = ?");
            if ($stmtDel) {
                $stmtDel->bind_param("i", $id);
                $stmtDel->execute();
                $stmtDel->close();
            }
        }
    }
    echo "<script>window.location='santri.php?msg=deleted';</script>";
    exit;
}

// Handle Send Card to WA
if (isset($_GET['send_card'])) {
    require_once '../includes/mpwa_helper.php';
    $id = (int)$_GET['send_card'];

    $stmtSantri = $conn->prepare("SELECT id, name, parent_phone, qrcode_hash, teacher_id FROM santri WHERE id = ? LIMIT 1");
    if ($stmtSantri) {
        $stmtSantri->bind_param("i", $id);
        $stmtSantri->execute();
        $stmtSantri->bind_result($santri_id, $name, $parent_phone, $hash, $teacher_id_of_row);
        $found = $stmtSantri->fetch();
        $stmtSantri->close();
    } else {
        $found = false;
    }

    if ($found) {
        if (!$is_admin && (int)$teacher_id_of_row !== $current_user_id) {
            echo "<script>window.location='santri.php?msg=unauthorized';</script>";
            exit;
        }
        
        // Build Link
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $path = str_replace('admin/santri.php', '', $_SERVER['PHP_SELF']);
        $path = trim($path, '/');
        $card_url = "$protocol://$host/$path/view_card.php?hash=$hash";
        
        // Template Message
        $msg_tpl = $app_settings['wa_card_message'] ?? "Halo, berikut adalah *Kartu Identitas Digital* [nama] untuk absensi di [instansi]. Silakan simpan link berikut untuk mencetak mandiri:\n\n[link]";
        $msg = str_replace(['[nama]', '[instansi]', '[link]'], [$name, $app_settings['app_name'], $card_url], $msg_tpl);
        
        $res = send_wa_notification($app_settings['mpwa_url'], $app_settings['mpwa_token'], $app_settings['mpwa_sender'], $parent_phone, $msg);
        
        if($res) {
            echo "<script>window.location='santri.php?msg=sent&to=".urlencode($name)."';</script>";
        } else {
            echo "<script>alert('Gagal mengirim WhatsApp. Cek konfigurasi MPWA.'); window.location='santri.php';</script>";
        }
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_assign'])) {
    if (!$is_admin) {
        echo "<script>window.location='santri.php?msg=unauthorized';</script>";
        exit;
    }

    $target_class = isset($_POST['target_class']) ? $conn->real_escape_string($_POST['target_class']) : '';
    $target_teacher_id = isset($_POST['target_teacher_id']) && $_POST['target_teacher_id'] !== '' ? (int)$_POST['target_teacher_id'] : null;
    $override = isset($_POST['override']) ? 1 : 0;

    if ($target_class === '') {
        echo "<script>window.location='santri.php?msg=invalid';</script>";
        exit;
    }

    $where_extra = $override ? "" : " AND (teacher_id IS NULL OR teacher_id = 0)";

    if ($target_teacher_id === null || $target_teacher_id <= 0) {
        $stmtAssign = $conn->prepare("UPDATE santri SET teacher_id = NULL WHERE class_name = ? $where_extra");
        if ($stmtAssign) {
            $stmtAssign->bind_param("s", $target_class);
            $stmtAssign->execute();
            $stmtAssign->close();
        }
    } else {
        $stmtAssign = $conn->prepare("UPDATE santri SET teacher_id = ? WHERE class_name = ? $where_extra");
        if ($stmtAssign) {
            $stmtAssign->bind_param("is", $target_teacher_id, $target_class);
            $stmtAssign->execute();
            $stmtAssign->close();
        }
    }

    echo "<script>window.location='santri.php?msg=assigned&kelas=" . urlencode($target_class) . "';</script>";
    exit;
}

// Handle Import CSV
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");
    $rowCount = 0;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rowCount++;
        if ($rowCount == 1) continue;
        if (count($data) < 5) continue; 

        $nis = $conn->real_escape_string($data[0]);
        $name = $conn->real_escape_string($data[1]);
        $class_name = $conn->real_escape_string($data[2]);
        $gender = $conn->real_escape_string($data[3]);
        $parent_phone = $conn->real_escape_string($data[4]);

        if (strpos($parent_phone, '0') === 0) {
            $parent_phone = '62' . substr($parent_phone, 1);
        }

        $cek = $conn->query("SELECT id FROM santri WHERE nis = '$nis'");
        if ($cek->num_rows > 0) {
            $existing = $cek->fetch_assoc();
            $existing_id = (int)$existing['id'];
            if (!$is_admin) {
                $stmtOwn = $conn->prepare("SELECT teacher_id FROM santri WHERE id = ? LIMIT 1");
                if ($stmtOwn) {
                    $stmtOwn->bind_param("i", $existing_id);
                    $stmtOwn->execute();
                    $stmtOwn->bind_result($teacher_id_of_row);
                    $found = $stmtOwn->fetch();
                    $stmtOwn->close();
                    if (!$found || (int)$teacher_id_of_row !== $current_user_id) {
                        continue;
                    }
                }
                $conn->query("UPDATE santri SET name='$name', class_name='$class_name', gender='$gender', parent_phone='$parent_phone' WHERE nis='$nis' AND teacher_id=$current_user_id");
            } else {
                $conn->query("UPDATE santri SET name='$name', class_name='$class_name', gender='$gender', parent_phone='$parent_phone' WHERE nis='$nis'");
            }
        } else {
            $qrcode_hash = md5($nis . time() . rand(1000,9999));
            if ($is_admin) {
                $conn->query("INSERT INTO santri (nis, name, class_name, gender, parent_phone, qrcode_hash, teacher_id) VALUES ('$nis', '$name', '$class_name', '$gender', '$parent_phone', '$qrcode_hash', NULL)");
            } else {
                $conn->query("INSERT INTO santri (nis, name, class_name, gender, parent_phone, qrcode_hash, teacher_id) VALUES ('$nis', '$name', '$class_name', '$gender', '$parent_phone', '$qrcode_hash', $current_user_id)");
            }
        }
    }
    fclose($handle);
    echo "<script>window.location='santri.php?msg=imported';</script>";
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = $conn->real_escape_string($_POST['nis']);
    $name = $conn->real_escape_string($_POST['name']);
    $class_name = $conn->real_escape_string($_POST['class_name']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $parent_phone = $conn->real_escape_string($_POST['parent_phone']);
    $teacher_id = isset($_POST['teacher_id']) && $_POST['teacher_id'] !== '' ? (int)$_POST['teacher_id'] : null;
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // Formatting parent phone to start with 62
    if (strpos($parent_phone, '0') === 0) {
        $parent_phone = '62' . substr($parent_phone, 1);
    }
    
    // Image Upload Logic
    $photo_name = '';
    $photo_query_update = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $tmp_path = $_FILES['photo']['tmp_name'];
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower($ext);
        if (in_array($ext, $allowed) && @getimagesize($tmp_path)) {
            $photo_name = "santri_" . time() . "_" . rand(100,999) . "." . $ext;
            move_uploaded_file($tmp_path, "../assets/images/" . $photo_name);
            $photo_query_update = ", photo='$photo_name'";
        }
    }

    if ($id > 0) {
        if (!$is_admin) {
            $stmtOwn = $conn->prepare("SELECT teacher_id FROM santri WHERE id = ? LIMIT 1");
            if ($stmtOwn) {
                $stmtOwn->bind_param("i", $id);
                $stmtOwn->execute();
                $stmtOwn->bind_result($teacher_id_of_row);
                $found = $stmtOwn->fetch();
                $stmtOwn->close();
                if (!$found || (int)$teacher_id_of_row !== $current_user_id) {
                    echo "<script>window.location='santri.php?msg=unauthorized';</script>";
                    exit;
                }
            }
            $teacher_id = $current_user_id;
        }
        // Update
        if ($is_admin) {
            if ($teacher_id === null || $teacher_id <= 0) {
                $conn->query("UPDATE santri SET nis='$nis', name='$name', class_name='$class_name', gender='$gender', parent_phone='$parent_phone', teacher_id=NULL $photo_query_update WHERE id=$id");
            } else {
                $conn->query("UPDATE santri SET nis='$nis', name='$name', class_name='$class_name', gender='$gender', parent_phone='$parent_phone', teacher_id=$teacher_id $photo_query_update WHERE id=$id");
            }
        } else {
            $conn->query("UPDATE santri SET nis='$nis', name='$name', class_name='$class_name', gender='$gender', parent_phone='$parent_phone', teacher_id=$teacher_id $photo_query_update WHERE id=$id");
        }
        echo "<script>window.location='santri.php?msg=updated';</script>";
    } else {
        if (!$is_admin) {
            $teacher_id = $current_user_id;
        }
        // Insert
        // Generate unique MD5 Hash for QR string
        $qrcode_hash = md5($nis . time() . rand(1000,9999));

        if ($is_admin) {
            if ($teacher_id === null || $teacher_id <= 0) {
                $sql = "INSERT INTO santri (nis, name, class_name, gender, parent_phone, qrcode_hash, photo, teacher_id) 
                        VALUES ('$nis', '$name', '$class_name', '$gender', '$parent_phone', '$qrcode_hash', '$photo_name', NULL)";
            } else {
                $sql = "INSERT INTO santri (nis, name, class_name, gender, parent_phone, qrcode_hash, photo, teacher_id) 
                        VALUES ('$nis', '$name', '$class_name', '$gender', '$parent_phone', '$qrcode_hash', '$photo_name', $teacher_id)";
            }
        } else {
            $sql = "INSERT INTO santri (nis, name, class_name, gender, parent_phone, qrcode_hash, photo, teacher_id) 
                    VALUES ('$nis', '$name', '$class_name', '$gender', '$parent_phone', '$qrcode_hash', '$photo_name', $teacher_id)";
        }
        
        if ($conn->query($sql)) {
            echo "<script>window.location='santri.php?msg=added';</script>";
        } else {
            echo "<script>alert('Gagal tambah: " . $conn->error . "');</script>";
        }
    }
    exit;
}

// Get available classes for filter
$class_where = '';
if (!$is_admin) {
    $class_where = "WHERE teacher_id = $current_user_id";
}
$class_res = $conn->query("SELECT DISTINCT class_name FROM santri $class_where ORDER BY class_name ASC");
$class_options = [];
if ($class_res && $class_res->num_rows > 0) {
    while ($c = $class_res->fetch_assoc()) {
        $class_options[] = $c['class_name'];
    }
}
$filter_class = isset($_GET['kelas']) ? $conn->real_escape_string($_GET['kelas']) : '';

// Get Data with optional class filter
$where_parts = [];
if (!empty($filter_class)) {
    $where_parts[] = "s.class_name = '$filter_class'";
}
if (!$is_admin) {
    $where_parts[] = "s.teacher_id = $current_user_id";
}
$where = '';
if (!empty($where_parts)) {
    $where = "WHERE " . implode(' AND ', $where_parts);
}
$res = $conn->query("SELECT s.*, u.name as teacher_name, u.phone as teacher_phone FROM santri s LEFT JOIN users u ON u.id = s.teacher_id $where ORDER BY s.class_name ASC, s.name ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">DATA SANTRI</h4>
    <div class="d-flex flex-wrap gap-1 align-items-center">
        <button class="btn btn-outline-dark btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalImport">
            <i class="fas fa-file-import"></i> Impor CSV
        </button>
        <a href="santri_export.php<?= !empty($filter_class) ? '?kelas='.urlencode($filter_class) : '' ?>" class="btn btn-outline-success btn-sm rounded-pill">
            <i class="fas fa-file-excel"></i> Ekspor CSV
        </a>
        <a href="santri_print.php<?= !empty($filter_class) ? '?kelas='.urlencode($filter_class) : '' ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill">
            <i class="fas fa-print"></i> Cetak ID <?= !empty($filter_class) ? htmlspecialchars($filter_class) : 'Semua' ?>
        </a>
        <?php if($is_admin): ?>
            <button class="btn btn-outline-secondary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAssignGuru">
                <i class="fas fa-user-check"></i> Assign Guru Kelas
            </button>
        <?php endif; ?>
        <button class="btn btn-primary bg-primary-custom btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fas fa-plus"></i> Tambah Data Baru
        </button>
    </div>
</div>

<!-- Filter Kelas -->
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="form-label mb-0 fw-bold small text-muted"><i class="fas fa-filter me-1"></i> Filter Kelas:</label>
            <select name="kelas" class="form-select form-select-sm" style="max-width: 200px;" onchange="this.form.submit()">
                <option value="">-- Semua Kelas --</option>
                <?php foreach($class_options as $class_name_opt): ?>
                    <option value="<?= htmlspecialchars($class_name_opt) ?>" <?= $filter_class == $class_name_opt ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class_name_opt) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if(!empty($filter_class)): ?>
                <a href="santri.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="fas fa-times me-1"></i> Reset</a>
            <?php endif; ?>
            <span class="text-muted small ms-auto"><?= $res->num_rows ?> data ditemukan</span>
        </form>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        if($_GET['msg'] == 'added') echo "Data berhasil ditambahkan!";
        elseif($_GET['msg'] == 'updated') echo "Data berhasil diperbarui!";
        elseif($_GET['msg'] == 'deleted') echo "Data berhasil dihapus!";
        elseif($_GET['msg'] == 'imported') echo "Data berhasil diimpor dari CSV!";
        elseif($_GET['msg'] == 'assigned') echo "Assign guru per kelas berhasil disimpan untuk kelas " . htmlspecialchars($_GET['kelas'] ?? '') . "!";
        elseif($_GET['msg'] == 'sent') echo "Kartu digital " . htmlspecialchars($_GET['to'] ?? '') . " berhasil dikirim ke nomor wali!";
        elseif($_GET['msg'] == 'unauthorized') echo "Akses ditolak.";
        elseif($_GET['msg'] == 'invalid') echo "Data tidak valid.";
        else echo "Tugas berhasil disimpan!";
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <?php if($is_admin): ?>
                            <th>Guru</th>
                        <?php endif; ?>
                        <th>L/P</th>
                        <th>No. WA Wali</th>
                        <th class="text-center">QR Code & Foto</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($res->num_rows > 0): $no=1; ?>
                        <?php while($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nis']) ?></td>
                                <td class="fw-medium">
                                    <?php if(!empty($row['photo'])): ?>
                                        <img src="../assets/images/<?= $row['photo'] ?>" alt="Foto" width="30" height="30" class="rounded-circle me-2 object-fit-cover border">
                                    <?php else: ?>
                                        <div class="d-inline-block rounded-circle bg-secondary text-white text-center me-2" style="width: 30px; height: 30px; line-height:30px;"><i class="fas fa-user-alt fa-xs"></i></div>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($row['name']) ?>
                                </td>
                                <td><?= htmlspecialchars($row['class_name']) ?></td>
                                <?php if($is_admin): ?>
                                    <td>
                                        <?php if(!empty($row['teacher_name'])): ?>
                                            <div class="fw-medium"><?= htmlspecialchars($row['teacher_name']) ?></div>
                                            <?php if(!empty($row['teacher_phone'])): ?>
                                                <a href="https://wa.me/<?= htmlspecialchars($row['teacher_phone']) ?>" target="_blank" class="text-success text-decoration-none small">
                                                    <i class="fab fa-whatsapp"></i> <?= htmlspecialchars($row['teacher_phone']) ?>
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td><?= $row['gender'] ?></td>
                                <td>
                                    <a href="https://wa.me/<?= $row['parent_phone'] ?>" target="_blank" class="text-success text-decoration-none">
                                        <i class="fab fa-whatsapp"></i> <?= htmlspecialchars($row['parent_phone']) ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-light border rounded" data-bs-toggle="modal" data-bs-target="#modalQR<?= $row['id'] ?>">
                                        <i class="fas fa-qrcode text-dark"></i> Lihat
                                    </button>
                                </td>
                                <td class="text-end">
                                    <a href="santri.php?send_card=<?= $row['id'] ?>" class="btn btn-sm btn-outline-success border-0" title="Kirim Kartu ke WA Wali">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="santri.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus data ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $is_admin ? '9' : '8' ?>" class="text-center py-4 text-muted">Belum ada data murid. Silakan tambah data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- All Modals (QR & Edit) moved here for HTML validity -->
<?php 
if($res->num_rows > 0): 
    $res->data_seek(0); // Reset result pointer
    while($row = $res->fetch_assoc()): 
?>
    <!-- Modal QR -->
    <div class="modal fade" id="modalQR<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pt-0 pb-4">
                    <div class="mb-3">
                        <?php if(!empty($row['photo'])): ?>
                            <img src="../assets/images/<?= $row['photo'] ?>" alt="Foto" width="70" height="70" class="rounded-circle object-fit-cover shadow-sm border">
                        <?php endif; ?>
                    </div>
                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($row['name']) ?></h6>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($row['nis']) ?> - <?= htmlspecialchars($row['class_name']) ?></p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= $row['qrcode_hash'] ?>" alt="QR Code" class="img-fluid border rounded p-2 shadow-sm mb-3">
                    <div class="d-grid gap-2">
                        <a href="santri_print.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-primary btn-sm rounded-pill">
                            <i class="fas fa-print"></i> Cetak Kartu
                        </a>
                        <button type="button" class="btn btn-light btn-sm rounded-pill border" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Data Murid</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <?php if($is_admin): ?>
                            <div class="mb-3">
                                <label class="form-label">Guru Penanggung Jawab</label>
                                <select name="teacher_id" class="form-select">
                                    <option value="">-- Tidak ditentukan --</option>
                                    <?php foreach($guru_options as $g): ?>
                                        <option value="<?= (int)$g['id'] ?>" <?= ((int)($row['teacher_id'] ?? 0) === (int)$g['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($g['name']) ?><?= ($g['role'] ?? '') === 'admin' ? ' (Admin)' : '' ?><?= !empty($g['phone']) ? ' - ' . htmlspecialchars($g['phone']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="teacher_id" value="<?= $current_user_id ?>">
                        <?php endif; ?>
                        
                        <div class="text-center mb-3">
                            <?php if(!empty($row['photo'])): ?>
                                <img src="../assets/images/<?= $row['photo'] ?>" alt="Foto" width="80" height="80" class="rounded-circle object-fit-cover shadow-sm border mb-2">
                            <?php else: ?>
                                <div class="d-inline-block rounded-circle bg-light text-muted text-center border mb-2" style="width: 80px; height: 80px; line-height:80px;"><i class="fas fa-user-alt fa-2x"></i></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ganti Foto Profil <small class="text-muted">(opsional)</small></label>
                            <input type="file" name="photo" class="form-control" accept="image/png, image/jpeg, image/jpg">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">NIS</label>
                            <input type="text" name="nis" class="form-control" required value="<?= htmlspecialchars($row['nis']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($row['name']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <input type="text" name="class_name" class="form-control" required value="<?= htmlspecialchars($row['class_name']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="gender" class="form-select" required>
                                <option value="L" <?= $row['gender'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= $row['gender'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. WA Wali (<small>awali 08 atau 62</small>)</label>
                            <input type="text" name="parent_phone" class="form-control" required value="<?= htmlspecialchars($row['parent_phone']) ?>">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php 
    endwhile; 
endif; 
?>

<?php if($is_admin): ?>
<!-- Modal Assign Guru -->
<div class="modal fade" id="modalAssignGuru" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="bulk_assign" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Assign Guru per Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Kelas</label>
                        <select name="target_class" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach($class_options as $class_name_opt): ?>
                                <option value="<?= htmlspecialchars($class_name_opt) ?>">
                                    <?= htmlspecialchars($class_name_opt) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Guru Penanggung Jawab</label>
                        <select name="target_teacher_id" class="form-select">
                            <option value="">-- Kosongkan (hapus assign) --</option>
                            <?php foreach($guru_options as $g): ?>
                                <option value="<?= (int)$g['id'] ?>">
                                    <?= htmlspecialchars($g['name']) ?><?= ($g['role'] ?? '') === 'admin' ? ' (Admin)' : '' ?><?= !empty($g['phone']) ? ' - ' . htmlspecialchars($g['phone']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="1" id="overrideAssign" name="override">
                        <label class="form-check-label" for="overrideAssign">
                            Timpa assign yang sudah ada
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Add -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Murid Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if($is_admin): ?>
                        <div class="mb-3">
                            <label class="form-label">Guru Penanggung Jawab</label>
                            <select name="teacher_id" class="form-select">
                                <option value="">-- Tidak ditentukan --</option>
                                <?php foreach($guru_options as $g): ?>
                                    <option value="<?= (int)$g['id'] ?>">
                                        <?= htmlspecialchars($g['name']) ?><?= ($g['role'] ?? '') === 'admin' ? ' (Admin)' : '' ?><?= !empty($g['phone']) ? ' - ' . htmlspecialchars($g['phone']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="teacher_id" value="<?= $current_user_id ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">NIS</label>
                        <input type="text" name="nis" class="form-control" required placeholder="Ex: 1001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" required placeholder="Nama Lengkap">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <input type="text" name="class_name" class="form-control" required placeholder="Misal: 1A">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="gender" class="form-select" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. WA Wali (<small>awali 08 atau 628</small>)</label>
                        <input type="text" name="parent_phone" class="form-control" required placeholder="08123456789">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto Profil <small class="text-muted">(opsional)</small></label>
                        <input type="file" name="photo" class="form-control" accept="image/png, image/jpeg, image/jpg">
                    </div>
                </div>
                <div class="modal-footer border-0 pb-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill mb-3">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Impor Murid (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        Pastikan file CSV memiliki urutan kolom: <strong>NIS, Nama, Kelas, L/P, No WA Wali</strong>. <br>
                        Gunakan baris pertama sebagai header (akan dilewati).
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih File CSV</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Mulai Impor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
