<?php
if (!$isAdmin) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['delete_user']) && $_GET['delete_user']) {
    $user_id = intval($_GET['delete_user']);


    if ($user_id != $_SESSION['user_id']) {
        $conn = getConnection();
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $success = "User berhasil dihapus!";
        } else {
            $error = "Gagal menghapus user!";
        }

        $stmt->close();
        $conn->close();
    } else {
        $error = "Tidak dapat menghapus akun sendiri!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $id = $_POST['user_id'] ?? null;
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    $conn = getConnection();

    if ($id) {
        if ($password) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, role = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $username, $name, $role, $hashed, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $name, $role, $id);
        }

        if ($stmt->execute()) {
            $success = "User berhasil diupdate!";
        } else {
            $error = "Gagal mengupdate user: " . $conn->error;
        }
    } else {
        if (!$password) {
            $error = "Password wajib diisi untuk user baru!";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hashed, $name, $role);

            if ($stmt->execute()) {
                $success = "User berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan user: " . $conn->error;
            }
        }
    }

    if (isset($stmt))
        $stmt->close();
    $conn->close();
}

$conn = getConnection();
$result = $conn->query("SELECT * FROM users ORDER BY role, name");
?>

<div class="space-y-4">
    <div class="bg-white rounded-lg shadow p-4 flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Kelola User</h2>
            <p class="text-sm text-gray-600 mt-1">Manajemen akun admin dan siswa</p>
        </div>
        <button onclick="openUserModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            Tambah User
        </button>
    </div>

    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama
                        Lengkap</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terdaftar
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                $no = 1;
                while ($row = $result->fetch_assoc()):
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $no++ ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($row['username']) ?>
                            <?php if ($row['id'] == $_SESSION['user_id']): ?>
                                <span class="text-xs text-blue-600">(Anda)</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= htmlspecialchars($row['name']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($row['role'] === 'admin'): ?>
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                    Admin
                                </span>
                            <?php else: ?>
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Siswa
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('d/m/Y', strtotime($row['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <button onclick='editUser(<?= json_encode($row) ?>)' class="text-blue-600 hover:text-blue-900">
                                Edit
                            </button>
                            <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                <button onclick="deleteUser(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')"
                                    class="text-red-600 hover:text-red-900">
                                    Hapus
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="userModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4" id="modalTitle">Tambah User</h3>
        <form method="POST">
            <input type="hidden" name="user_id" id="user_id">

            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Username *</label>
                <input type="text" name="username" id="username" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Nama Lengkap *</label>
                <input type="text" name="name" id="name" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Role *</label>
                <select name="role" id="role" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="siswa">Siswa</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">
                    Password <span id="passwordLabel">(Wajib untuk user baru)</span>
                </label>
                <input type="password" name="password" id="password"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Minimal 6 karakter">
                <p class="text-xs text-gray-500 mt-1" id="passwordHint">
                    Kosongkan jika tidak ingin mengubah password
                </p>
            </div>

            <div class="flex gap-3">
                <button type="submit" name="save_user"
                    class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                    Simpan
                </button>
                <button type="button" onclick="closeUserModal()"
                    class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openUserModal() {
        document.getElementById('modalTitle').textContent = 'Tambah User';
        document.getElementById('user_id').value = '';
        document.getElementById('username').value = '';
        document.getElementById('name').value = '';
        document.getElementById('role').value = 'siswa';
        document.getElementById('password').value = '';
        document.getElementById('password').required = true;
        document.getElementById('passwordLabel').textContent = '*';
        document.getElementById('passwordHint').style.display = 'none';
        document.getElementById('userModal').classList.remove('hidden');
    }

    function editUser(user) {
        document.getElementById('modalTitle').textContent = 'Edit User';
        document.getElementById('user_id').value = user.id;
        document.getElementById('username').value = user.username;
        document.getElementById('name').value = user.name;
        document.getElementById('role').value = user.role;
        document.getElementById('password').value = '';
        document.getElementById('password').required = false;
        document.getElementById('passwordLabel').textContent = '';
        document.getElementById('passwordHint').style.display = 'block';
        document.getElementById('userModal').classList.remove('hidden');
    }

    function closeUserModal() {
        document.getElementById('userModal').classList.add('hidden');
    }

    function deleteUser(id, name) {
        if (confirm(`Apakah Anda yakin ingin menghapus user "${name}"?\n\nSemua aspirasi user ini juga akan terhapus!`)) {
            window.location.href = `?page=users&delete_user=${id}`;
        }
    }
</script>

<?php $conn->close(); ?>