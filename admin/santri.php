<?php
require_once 'header.php';

// Handle Delete Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Ambil foto lama untuk dihapus filenya
    $q_del = $conn->query("SELECT photo FROM santri WHERE id = $id");
    if($r_del = $q_del->fetch_assoc()) {
        if(!empty($r_del['photo']) && file_exists("../assets/images/" . $r_del['photo'])) {
            unlink("../assets/images/" . $r_del['photo']);
        }
    }

    $conn->query("DELETE FROM santri WHERE id = $id");
    echo "<script>window.location='santri.php?msg=deleted';</script>";
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = $conn->real_escape_string($_POST['nis']);
    $name = $conn->real_escape_string($_POST['name']);
    $class_name = $conn->real_escape_string($_POST['class_name']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $parent_phone = $conn->real_escape_string($_POST['parent_phone']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // Formatting parent phone to start with 62
    if (strpos($parent_phone, '0') === 0) {
        $parent_phone = '62' . substr($parent_phone, 1);
    }
    
    // Image Upload Logic
    $photo_name = '';
    $photo_query_update = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png'];
        if (in_array(strtolower($ext), $allowed)) {
            $photo_name = "santri_" . time() . "_" . rand(100,999) . "." . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], "../assets/images/" . $photo_name);
            $photo_query_update = ", photo='$photo_name'";
        }
    }

    if ($id > 0) {
        // Update
        $conn->query("UPDATE santri SET nis='$nis', name='$name', class_name='$class_name', gender='$gender', parent_phone='$parent_phone' $photo_query_update WHERE id=$id");
        echo "<script>window.location='santri.php?msg=updated';</script>";
    } else {
        // Insert
        // Generate unique MD5 Hash for QR string
        $qrcode_hash = md5($nis . time() . rand(1000,9999));
        
        $sql = "INSERT INTO santri (nis, name, class_name, gender, parent_phone, qrcode_hash, photo) 
                VALUES ('$nis', '$name', '$class_name', '$gender', '$parent_phone', '$qrcode_hash', '$photo_name')";
        
        if ($conn->query($sql)) {
            echo "<script>window.location='santri.php?msg=added';</script>";
        } else {
            echo "<script>alert('Gagal tambah: " . $conn->error . "');</script>";
        }
    }
    exit;
}

// Handle Import CSV
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");
    $rowCount = 0;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rowCount++;
        if ($rowCount == 1) continue; // Skip header
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
            $conn->query("UPDATE santri SET name='$name', class_name='$class_name', gender='$gender', parent_phone='$parent_phone' WHERE nis='$nis'");
        } else {
            $qrcode_hash = md5($nis . time() . rand(1000,9999));
            $conn->query("INSERT INTO santri (nis, name, class_name, gender, parent_phone, qrcode_hash) VALUES ('$nis', '$name', '$class_name', '$gender', '$parent_phone', '$qrcode_hash')");
        }
    }
    fclose($handle);
    echo "<script>window.location='santri.php?msg=imported';</script>";
    exit;
}

// Get Data
$res = $conn->query("SELECT * FROM santri ORDER BY id DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Data Murid</h4>
    <div>
        <button class="btn btn-outline-dark btn-sm rounded-pill mx-1" data-bs-toggle="modal" data-bs-target="#modalImport">
            <i class="fas fa-file-import"></i> Impor CSV
        </button>
        <a href="santri_export.php" class="btn btn-outline-success btn-sm rounded-pill mx-1">
            <i class="fas fa-file-excel"></i> Ekspor CSV
        </a>
        <a href="santri_print.php" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill mx-1">
            <i class="fas fa-print"></i> Cetak Semua ID
        </a>
        <button class="btn btn-primary bg-primary-custom btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fas fa-plus"></i> Tambah Murid
        </button>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        if($_GET['msg'] == 'added') echo "Data murid berhasil ditambahkan!";
        elseif($_GET['msg'] == 'updated') echo "Data murid berhasil diperbarui!";
        elseif($_GET['msg'] == 'deleted') echo "Data murid berhasil dihapus!";
        elseif($_GET['msg'] == 'imported') echo "Data murid berhasil diimpor dari CSV!";
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
                            <td colspan="8" class="text-center py-4 text-muted">Belum ada data murid. Silakan tambah data.</td>
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
