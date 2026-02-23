<?php

$filterDate = $_GET['filter_date'] ?? '';
$filterMonth = $_GET['filter_month'] ?? '';
$filterUser = $_GET['filter_user'] ?? '';
$filterCategory = $_GET['filter_category'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';

$where = [];
$params = [];
$types = '';

if ($filterDate) {
    $where[] = "DATE(a.created_at) = ?";
    $params[] = $filterDate;
    $types .= 's';
}

if ($filterMonth) {
    $where[] = "DATE_FORMAT(a.created_at, '%Y-%m') = ?";
    $params[] = $filterMonth;
    $types .= 's';
}

if ($filterUser && $isAdmin) {
    $where[] = "a.user_id = ?";
    $params[] = $filterUser;
    $types .= 'i';
}

if ($filterCategory) {
    $where[] = "a.category_id = ?";
    $params[] = $filterCategory;
    $types .= 'i';
}

if ($filterStatus) {
    $where[] = "a.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

if (!$isAdmin) {
    $where[] = "a.user_id = ?";
    $params[] = $_SESSION['user_id'];
    $types .= 'i';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT a.*, u.name as user_name, c.name as category_name
          FROM aspirations a 
          JOIN users u ON a.user_id = u.id 
          JOIN categories c ON a.category_id = c.id
          $whereClause
          ORDER BY a.created_at DESC";

$conn = getConnection();

if ($params) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$users = [];
if ($isAdmin) {
    $usersResult = $conn->query("SELECT id, name FROM users WHERE role = 'siswa' ORDER BY name");
    while ($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
}

$categoriesResult = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

$statusColors = [
    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
    'proses' => 'bg-blue-100 text-blue-800 border-blue-300',
    'selesai' => 'bg-green-100 text-green-800 border-green-300'
];

$statusLabels = [
    'pending' => 'Menunggu',
    'proses' => 'Proses',
    'selesai' => 'Selesai'
];
?>

<div class="space-y-4">
    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold mb-3">Filter Aspirasi</h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <input type="hidden" name="page" value="list">
            
            <div>
                <label class="block text-sm text-gray-600 mb-1">Tanggal</label>
                <input type="date" name="filter_date" value="<?= $filterDate ?>"
                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            
            <div>
                <label class="block text-sm text-gray-600 mb-1">Bulan</label>
                <input type="month" name="filter_month" value="<?= $filterMonth ?>"
                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            
            <?php if ($isAdmin): ?>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Siswa</label>
                <select name="filter_user" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= $filterUser == $user['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div>
                <label class="block text-sm text-gray-600 mb-1">Kategori</label>
                <select name="filter_category" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm text-gray-600 mb-1">Status</label>
                <select name="filter_status" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua</option>
                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Menunggu</option>
                    <option value="proses" <?= $filterStatus === 'proses' ? 'selected' : '' ?>>Proses</option>
                    <option value="selesai" <?= $filterStatus === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>
            
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                    Terapkan
                </button>
                <a href="?page=list" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 text-sm">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="space-y-4">
        <?php if ($result->num_rows === 0): ?>
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                Tidak ada aspirasi yang ditemukan
            </div>
        <?php else: ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($row['title']) ?></h3>
                            <div class="flex gap-4 mt-2 text-sm text-gray-600">
                                <span>📅 <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></span>
                                <span>👤 <?= htmlspecialchars($row['user_name']) ?></span>
                                <span><?= htmlspecialchars($row['category_name']) ?></span>
                                <?php if ($row['photo']): ?>
                                    <span class="text-blue-600">📷 Ada Foto</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="px-4 py-2 rounded-full text-sm font-medium border <?= $statusColors[$row['status']] ?>">
                            <?= $statusLabels[$row['status']] ?>
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-<?= $row['photo'] ? '2' : '1' ?> gap-4 mb-4">
                        <div>
                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                        </div>
                        
                        <?php if ($row['photo'] && file_exists($row['photo'])): ?>
                            <div class="flex justify-center items-center">
                                <img src="<?= htmlspecialchars($row['photo']) ?>" 
                                     alt="Foto Aspirasi" 
                                     class="max-h-48 rounded-lg shadow-md cursor-pointer hover:shadow-xl transition"
                                     onclick="openImageModal('<?= htmlspecialchars($row['photo']) ?>')">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($row['progress']): ?>
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-3 mb-3">
                            <div class="font-semibold text-blue-800 text-sm mb-1">📊 Progress:</div>
                            <p class="text-blue-700 text-sm"><?= nl2br(htmlspecialchars($row['progress'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($row['feedback']): ?>
                        <div class="bg-green-50 border-l-4 border-green-500 p-3 mb-3">
                            <div class="font-semibold text-green-800 text-sm mb-1">💬 Umpan Balik:</div>
                            <p class="text-green-700 text-sm"><?= nl2br(htmlspecialchars($row['feedback'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($isAdmin): ?>
                        <button onclick="openFeedbackModal(<?= $row['id'] ?>, '<?= $row['status'] ?>', '<?= htmlspecialchars($row['feedback'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($row['progress'] ?? '', ENT_QUOTES) ?>')"
                                class="mt-3 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                            Kelola Umpan Balik
                        </button>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($isAdmin): ?>
<div id="feedbackModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4">
        <h3 class="text-xl font-bold mb-4">Kelola Umpan Balik & Status</h3>
        <form method="POST">
            <input type="hidden" name="aspiration_id" id="modal_id">
            
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Status</label>
                <select name="status" id="modal_status" required
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="pending">Menunggu</option>
                    <option value="proses">Proses</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Progress Perbaikan</label>
                <textarea name="progress" id="modal_progress" rows="3"
                          placeholder="Jelaskan progress yang sedang dilakukan..."
                          class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                <p class="text-xs text-gray-500 mt-1">Contoh: Teknisi sudah datang dan sedang melakukan perbaikan, estimasi selesai 2 hari</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Umpan Balik</label>
                <textarea name="feedback" id="modal_feedback" rows="3"
                          placeholder="Berikan umpan balik untuk siswa..."
                          class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                <p class="text-xs text-gray-500 mt-1">Contoh: Terima kasih atas laporannya, masalah sedang kami tangani</p>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" name="update_status"
                        class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                    Simpan
                </button>
                <button type="button" onclick="closeFeedbackModal()"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4" onclick="closeImageModal()">
    <div class="relative max-w-7xl max-h-full">
        <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-4xl hover:text-gray-300">×</button>
        <img id="modalImage" src="" alt="Preview" class="max-w-full max-h-screen rounded-lg">
    </div>
</div>

<script>
<?php if ($isAdmin): ?>
function openFeedbackModal(id, status, feedback, progress) {
    document.getElementById('modal_id').value = id;
    document.getElementById('modal_status').value = status;
    document.getElementById('modal_feedback').value = feedback;
    document.getElementById('modal_progress').value = progress;
    document.getElementById('feedbackModal').classList.remove('hidden');
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').classList.add('hidden');
}
<?php endif; ?>

function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
}
</script>

<?php
if (isset($stmt)) $stmt->close();
$conn->close();
?>