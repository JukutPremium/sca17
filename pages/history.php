<?php
if ($isAdmin) {
    $query = "SELECT a.*, u.name as user_name, c.name as category_name 
              FROM aspirations a 
              JOIN users u ON a.user_id = u.id 
              JOIN categories c ON a.category_id = c.id
              ORDER BY a.updated_at DESC";
} else {
    $query = "SELECT a.*, u.name as user_name, c.name as category_name 
              FROM aspirations a 
              JOIN users u ON a.user_id = u.id 
              JOIN categories c ON a.category_id = c.id
              WHERE a.user_id = {$_SESSION['user_id']}
              ORDER BY a.updated_at DESC";
}

$conn = getConnection();
$result = $conn->query($query);

$statusColors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'proses' => 'bg-blue-100 text-blue-800',
    'selesai' => 'bg-green-100 text-green-800'
];

$statusLabels = [
    'pending' => 'Menunggu',
    'proses' => 'Proses',
    'selesai' => 'Selesai'
];
?>

<div class="space-y-4">
    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="text-xl font-bold text-gray-800">Histori Aspirasi</h2>
        <p class="text-sm text-gray-600 mt-1">
            <?= $isAdmin ? 'Semua histori aspirasi dari seluruh siswa' : 'Histori aspirasi Anda' ?>
        </p>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
            Belum ada histori aspirasi
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <?php if ($isAdmin): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Siswa</th>
                        <?php endif; ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    $no = 1;
                    while ($row = $result->fetch_assoc()): 
                    ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $no++ ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d/m/Y', strtotime($row['created_at'])) ?>
                            </td>
                            <?php if ($isAdmin): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($row['user_name']) ?>
                            </td>
                            <?php endif; ?>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="max-w-xs truncate"><?= htmlspecialchars($row['title']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusColors[$row['status']] ?>">
                                <?= $statusLabels[$row['status']] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button onclick="viewDetail(<?= $row['id'] ?>)" 
                                        class="text-blue-600 hover:text-blue-900">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-4">
            <h3 class="text-xl font-bold">Detail Aspirasi</h3>
            <button onclick="closeDetailModal()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
        </div>
        <div id="detailContent"></div>
    </div>
</div>

<div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-90 z-[60] flex items-center justify-center p-4" onclick="closeImageModal()">
    <div class="relative max-w-7xl max-h-full">
        <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-4xl hover:text-gray-300">×</button>
        <img id="modalImage" src="" alt="Preview" class="max-w-full max-h-screen rounded-lg">
    </div>
</div>

<script>
async function viewDetail(id) {
    try {
        const response = await fetch(`detail.php?id=${id}`);
        const html = await response.text();
        document.getElementById('detailContent').innerHTML = html;
        document.getElementById('detailModal').classList.remove('hidden');
    } catch (error) {
        alert('Gagal memuat detail');
    }
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
}
</script>

<?php $conn->close(); ?>