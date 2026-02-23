<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    exit('Unauthorized');
}

$id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['role'] === 'admin';

if ($is_admin) {
    $query = "SELECT a.*, u.name as user_name, c.name as category_name
              FROM aspirations a 
              JOIN users u ON a.user_id = u.id 
              JOIN categories c ON a.category_id = c.id
              WHERE a.id = $id";
} else {
    $query = "SELECT a.*, u.name as user_name, c.name as category_name
              FROM aspirations a 
              JOIN users u ON a.user_id = u.id 
              JOIN categories c ON a.category_id = c.id
              WHERE a.id = $id AND a.user_id = $user_id";
}

$conn = getConnection();
$result = $conn->query($query);

if ($result->num_rows === 0) {
    echo '<p class="text-red-500">Data tidak ditemukan</p>';
    exit;
}

$row = $result->fetch_assoc();
$conn->close();

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
    <div class="flex justify-between items-start">
        <div>
            <h4 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($row['title']) ?></h4>
            <p class="text-sm text-gray-500 mt-1">Diajukan oleh: <?= htmlspecialchars($row['user_name']) ?></p>
        </div>
        <span class="px-4 py-2 rounded-full text-sm font-medium border <?= $statusColors[$row['status']] ?>">
            <?= $statusLabels[$row['status']] ?>
        </span>
    </div>

    <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
        <div>
            <span class="text-sm text-gray-600">Kategori:</span>
            <p class="font-medium"><?= htmlspecialchars($row['category_name']) ?></p>
        </div>
        <div>
            <span class="text-sm text-gray-600">Tanggal Pengajuan:</span>
            <p class="font-medium"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></p>
        </div>
        <div>
            <span class="text-sm text-gray-600">Terakhir Update:</span>
            <p class="font-medium"><?= date('d/m/Y H:i', strtotime($row['updated_at'])) ?></p>
        </div>
        <div>
            <span class="text-sm text-gray-600">Status:</span>
            <p class="font-medium"><?= $statusLabels[$row['status']] ?></p>
        </div>
    </div>

    <?php if ($row['photo'] && file_exists($row['photo'])): ?>
        <div class="border rounded-lg overflow-hidden">
            <div class="bg-gray-100 px-4 py-2 border-b">
                <h5 class="font-semibold text-gray-700">📷 Foto Dokumentasi</h5>
            </div>
            <div class="p-4">
                <img src="<?= htmlspecialchars($row['photo']) ?>" 
                     alt="Foto Aspirasi" 
                     class="max-w-full h-auto rounded-lg shadow-md cursor-pointer hover:shadow-xl transition"
                     onclick="openImageModalInDetail(this.src)">
                <p class="text-xs text-gray-500 mt-2 text-center">Klik gambar untuk memperbesar</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="border-l-4 border-gray-300 pl-4">
        <h5 class="font-semibold text-gray-700 mb-2">Deskripsi Pengaduan:</h5>
        <p class="text-gray-700 whitespace-pre-line"><?= htmlspecialchars($row['description']) ?></p>
    </div>

    <?php if ($row['progress']): ?>
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
            <h5 class="font-semibold text-blue-800 mb-2">📊 Progress Perbaikan:</h5>
            <p class="text-blue-700 whitespace-pre-line"><?= htmlspecialchars($row['progress']) ?></p>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 border-l-4 border-gray-300 p-4 rounded">
            <p class="text-gray-500 italic">Belum ada progress yang dilaporkan</p>
        </div>
    <?php endif; ?>

    <?php if ($row['feedback']): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
            <h5 class="font-semibold text-green-800 mb-2">💬 Umpan Balik Admin:</h5>
            <p class="text-green-700 whitespace-pre-line"><?= htmlspecialchars($row['feedback']) ?></p>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 border-l-4 border-gray-300 p-4 rounded">
            <p class="text-gray-500 italic">Belum ada umpan balik dari admin</p>
        </div>
    <?php endif; ?>

    <div class="border-t pt-4">
        <h5 class="font-semibold text-gray-700 mb-3">Timeline:</h5>
        <div class="space-y-3">
            <div class="flex gap-3">
                <div class="w-2 h-2 rounded-full bg-blue-500 mt-2"></div>
                <div>
                    <p class="font-medium">Aspirasi Diajukan</p>
                    <p class="text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></p>
                </div>
            </div>
            <?php if ($row['status'] !== 'pending'): ?>
                <div class="flex gap-3">
                    <div class="w-2 h-2 rounded-full bg-yellow-500 mt-2"></div>
                    <div>
                        <p class="font-medium">Status Diperbarui</p>
                        <p class="text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($row['updated_at'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($row['status'] === 'selesai'): ?>
                <div class="flex gap-3">
                    <div class="w-2 h-2 rounded-full bg-green-500 mt-2"></div>
                    <div>
                        <p class="font-medium">Selesai Ditangani</p>
                        <p class="text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($row['updated_at'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function openImageModalInDetail(src) {
    if (typeof window.parent.openImageModal === 'function') {
        window.parent.openImageModal(src);
    } else if (typeof openImageModal === 'function') {
        openImageModal(src);
    } else {
        window.open(src, '_blank');
    }
}
</script>