<?php
require_once 'header.php';

function normalize_phone($phone) {
    $phone = trim((string)$phone);
    if ($phone === '') return '';
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if ($phone === '') return '';
    if (strpos($phone, '0') === 0) {
        $phone = '62' . substr($phone, 1);
    }
    return $phone;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $current_id = (int)($_SESSION['admin_id'] ?? 0);
    if ($id > 0 && $id !== $current_id) {
        $stmtRole = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        if ($stmtRole) {
            $stmtRole->bind_param("i", $id);
            $stmtRole->execute();
            $stmtRole->bind_result($role_to_delete);
            $found = $stmtRole->fetch();
            $stmtRole->close();
            if ($found) {
                if ($role_to_delete === 'admin') {
                    $admin_count = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")->fetch_assoc()['total'];
                    if ((int)$admin_count > 1) {
                        $stmtDel = $conn->prepare("DELETE FROM users WHERE id = ?");
                        if ($stmtDel) {
                            $stmtDel->bind_param("i", $id);
                            $stmtDel->execute();
                            $stmtDel->close();
                        }
                    }
                } else {
                    $stmtDel = $conn->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmtDel) {
                        $stmtDel->bind_param("i", $id);
                        $stmtDel->execute();
                        $stmtDel->close();
                    }
                }
            }
        }
    }
    echo "<script>window.location='users.php?msg=deleted';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
    $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
    $phone = isset($_POST['phone']) ? normalize_phone($_POST['phone']) : '';
    $role = isset($_POST['role']) ? trim((string)$_POST['role']) : 'guru';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($role !== 'admin' && $role !== 'guru') {
        $role = 'guru';
    }

    $username_ok = $username !== '' && preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username);
    $name_ok = $name !== '';

    if (!$username_ok) {
        echo "<script>window.location='users.php?msg=error&err=username';</script>";
        exit;
    }
    if (!$name_ok) {
        echo "<script>window.location='users.php?msg=error&err=name';</script>";
        exit;
    }

    if ($id <= 0 && strlen($password) < 6) {
        echo "<script>window.location='users.php?msg=error&err=password';</script>";
        exit;
    }
    if ($id > 0 && $password !== '' && strlen($password) < 6) {
        echo "<script>window.location='users.php?msg=error&err=password';</script>";
        exit;
    }

    $stmtDup = $conn->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
    if (!$stmtDup) {
        echo "<script>window.location='users.php?msg=error&err=db';</script>";
        exit;
    }
    $stmtDup->bind_param("si", $username, $id);
    $stmtDup->execute();
    $stmtDup->store_result();
    $dup = $stmtDup->num_rows > 0;
    $stmtDup->close();
    if ($dup) {
        echo "<script>window.location='users.php?msg=error&err=duplicate';</script>";
        exit;
    }

    if ($id > 0) {
        $stmtUp = $conn->prepare("UPDATE users SET username = ?, name = ?, phone = ?, role = ? WHERE id = ?");
        if (!$stmtUp) {
            echo "<script>window.location='users.php?msg=error&err=db';</script>";
            exit;
        }
        $stmtUp->bind_param("ssssi", $username, $name, $phone, $role, $id);
        $ok = $stmtUp->execute();
        $stmtUp->close();
        if (!$ok) {
            echo "<script>window.location='users.php?msg=error&err=db';</script>";
            exit;
        }

        if ($password !== '') {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmtPw = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if (!$stmtPw) {
                echo "<script>window.location='users.php?msg=error&err=db';</script>";
                exit;
            }
            $stmtPw->bind_param("si", $hashed, $id);
            $okPw = $stmtPw->execute();
            $stmtPw->close();
            if (!$okPw) {
                echo "<script>window.location='users.php?msg=error&err=db';</script>";
                exit;
            }
        }

        echo "<script>window.location='users.php?msg=updated';</script>";
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmtIn = $conn->prepare("INSERT INTO users (username, password, name, phone, role) VALUES (?, ?, ?, ?, ?)");
    if (!$stmtIn) {
        echo "<script>window.location='users.php?msg=error&err=db';</script>";
        exit;
    }
    $stmtIn->bind_param("sssss", $username, $hashed, $name, $phone, $role);
    $okIn = $stmtIn->execute();
    $stmtIn->close();
    if (!$okIn) {
        echo "<script>window.location='users.php?msg=error&err=db';</script>";
        exit;
    }

    echo "<script>window.location='users.php?msg=added';</script>";
    exit;
}

$res = $conn->query("SELECT id, username, name, phone, role FROM users ORDER BY (role = 'admin') DESC, name ASC, username ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">AKUN GURU / ADMIN</h4>
    <button class="btn btn-primary bg-primary-custom btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAddUser">
        <i class="fas fa-plus"></i> Tambah Akun
    </button>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div class="alert <?= $_GET['msg'] === 'error' ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show" role="alert">
        <?php
        if($_GET['msg'] == 'added') echo "Akun berhasil ditambahkan!";
        elseif($_GET['msg'] == 'updated') echo "Akun berhasil diperbarui!";
        elseif($_GET['msg'] == 'deleted') echo "Akun berhasil dihapus!";
        elseif($_GET['msg'] == 'error' && ($_GET['err'] ?? '') == 'username') echo "Gagal: Username minimal 3 karakter, tanpa spasi (huruf/angka/._-).";
        elseif($_GET['msg'] == 'error' && ($_GET['err'] ?? '') == 'name') echo "Gagal: Nama wajib diisi.";
        elseif($_GET['msg'] == 'error' && ($_GET['err'] ?? '') == 'password') echo "Gagal: Password minimal 6 karakter.";
        elseif($_GET['msg'] == 'error' && ($_GET['err'] ?? '') == 'duplicate') echo "Gagal: Username sudah digunakan.";
        else echo "Gagal menyimpan data.";
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
                        <th>Nama</th>
                        <th>Username</th>
                        <th>No. WA</th>
                        <th>Role</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($res && $res->num_rows > 0): $no=1; ?>
                        <?php while($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-medium"><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['phone'] ?? '') ?></td>
                                <td>
                                    <?php if(($row['role'] ?? '') === 'admin'): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Guru</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#modalEditUser<?= $row['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if((int)$row['id'] !== (int)($_SESSION['admin_id'] ?? 0)): ?>
                                        <a href="users.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus akun ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada data akun.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Akun</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required placeholder="misal: guru1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. WhatsApp (opsional)</label>
                        <input type="text" name="phone" class="form-control" placeholder="0812345678">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="guru">Guru</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="min 6 karakter">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
if($res && $res->num_rows > 0): 
    $res->data_seek(0);
    while($row = $res->fetch_assoc()):
?>
<div class="modal fade" id="modalEditUser<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Akun</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($row['username']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. WhatsApp (opsional)</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($row['phone'] ?? '') ?>" placeholder="0812345678">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="guru" <?= ($row['role'] ?? '') === 'guru' ? 'selected' : '' ?>>Guru</option>
                            <option value="admin" <?= ($row['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru (opsional)</label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white rounded-pill px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; endif; ?>

<?php require_once 'footer.php'; ?>
