<?php
if (!$isAdmin) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['delete_category']) && $_GET['delete_category']) {
    $category_id = intval($_GET['delete_category']);
    
    $conn = getConnection();
    
    $check = $conn->query("SELECT COUNT(*) as count FROM aspirations WHERE category_id = $category_id");
    $count = $check->fetch_assoc()['count'];
    
    if ($count > 0) {
        $error = "Kategori tidak dapat dihapus karena masih digunakan oleh $count aspirasi!";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        
        if ($stmt->execute()) {
            $success = "Kategori berhasil dihapus!";
        } else {
            $error = "Gagal menghapus kategori!";
        }
        
        $stmt->close();
    }
    
    $conn->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $id = $_POST['category_id'] ?? null;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $conn = getConnection();
    
    if ($id) {
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sssii", $name, $description, $is_active, $id);
        
        if ($stmt->execute()) {
            $success = "Kategori berhasil diupdate!";
        } else {
            $error = "Gagal mengupdate kategori: " . $conn->error;
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name, description, is_active) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $description, $is_active);
        
        if ($stmt->execute()) {
            $success = "Kategori berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan kategori: " . $conn->error;
        }
    }
    
    $stmt->close();
    $conn->close();
}

if (isset($_GET['toggle_category']) && $_GET['toggle_category']) {
    $category_id = intval($_GET['toggle_category']);
    
    $conn = getConnection();
    $conn->query("UPDATE categories SET is_active = NOT is_active WHERE id = $category_id");
    $conn->close();
    
    header('Location: ?page=categories');
    exit;
}

$conn = getConnection();
$result = $conn->query("
    SELECT c.*, 
           COUNT(a.id) as usage_count 
    FROM categories c 
    LEFT JOIN aspirations a ON c.id = a.category_id 
    GROUP BY c.id 
    ORDER BY c.name
");
?>

<div class="space-y-4">
    <div class="bg-white rounded-lg shadow p-4 flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Kelola Kategori</h2>
            <p class="text-sm text-gray-600 mt-1">Manajemen kategori pengaduan sarana sekolah</p>
        </div>
        <button onclick="openCategoryModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            Tambah Kategori
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="bg-white rounded-lg shadow p-6 <?= $row['is_active'] ? '' : 'opacity-50' ?>">
                <div class="flex justify-between items-start mb-3">
                    
                    <div class="flex gap-1">
                        <?php if ($row['is_active']): ?>
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Aktif</span>
                        <?php else: ?>
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Nonaktif</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-2">
                    <?= htmlspecialchars($row['name']) ?>
                </h3>
                
                <p class="text-sm text-gray-600 mb-4 min-h-[40px]">
                    <?= htmlspecialchars($row['description']) ?>
                </p>
                
                <div class="flex items-center justify-between pt-4 border-t">
                    <div class="text-sm text-gray-500">
                        <?= $row['usage_count'] ?> aspirasi
                    </div>
                    <div class="flex gap-2">
                        <button onclick='editCategory(<?= json_encode($row) ?>)' 
                                class="text-blue-600 hover:text-blue-900 text-sm">
                            Edit
                        </button>
                        <button onclick="toggleCategory(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>', <?= $row['is_active'] ?>)" 
                                class="text-yellow-600 hover:text-yellow-900 text-sm">
                            <?= $row['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </button>
                        <?php if ($row['usage_count'] == 0): ?>
                            <button onclick="deleteCategory(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')" 
                                    class="text-red-600 hover:text-red-900 text-sm">
                                Hapus
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4" id="modalTitle">Tambah Kategori</h3>
        <form method="POST">
            <input type="hidden" name="category_id" id="category_id">
            
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Nama Kategori *</label>
                <input type="text" name="name" id="name" required maxlength="100"
                       placeholder="Contoh: Ruang Kelas"
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Deskripsi</label>
                <textarea name="description" id="description" rows="3"
                          placeholder="Jelaskan kategori ini..."
                          class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" checked
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-gray-700">Kategori Aktif</span>
                </label>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" name="save_category"
                        class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                    Simpan
                </button>
                <button type="button" onclick="closeCategoryModal()"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCategoryModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Kategori';
    document.getElementById('category_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('description').value = '';
    document.getElementById('is_active').checked = true;
    document.getElementById('categoryModal').classList.remove('hidden');
}

function editCategory(category) {
    document.getElementById('modalTitle').textContent = 'Edit Kategori';
    document.getElementById('category_id').value = category.id;
    document.getElementById('name').value = category.name;
    document.getElementById('description').value = category.description;
    document.getElementById('is_active').checked = category.is_active == 1;
    document.getElementById('categoryModal').classList.remove('hidden');
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}

function toggleCategory(id, name, isActive) {
    const action = isActive ? 'menonaktifkan' : 'mengaktifkan';
    if (confirm(`Apakah Anda yakin ingin ${action} kategori "${name}"?`)) {
        window.location.href = `?page=categories&toggle_category=${id}`;
    }
}

function deleteCategory(id, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus kategori "${name}"?`)) {
        window.location.href = `?page=categories&delete_category=${id}`;
    }
}
</script>

<?php $conn->close(); ?>