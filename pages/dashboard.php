<?php
$conn = getConnection();

$totalResult = $conn->query("SELECT COUNT(*) as total FROM aspirations");
$total = $totalResult->fetch_assoc()['total'];

$statusResult = $conn->query("SELECT status, COUNT(*) as count FROM aspirations GROUP BY status");
$statusData = [];
while ($row = $statusResult->fetch_assoc()) {
    $statusData[$row['status']] = $row['count'];
}

$categoryResult = $conn->query("
    SELECT c.name, COUNT(a.id) as count 
    FROM categories c 
    LEFT JOIN aspirations a ON c.id = a.category_id 
    GROUP BY c.id 
    HAVING count > 0
    ORDER BY count DESC 
    LIMIT 5
");
$categoryData = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categoryData[] = $row;
}

$recentQuery = "SELECT a.*, u.name as user_name, c.name as category_name 
                FROM aspirations a 
                JOIN users u ON a.user_id = u.id 
                JOIN categories c ON a.category_id = c.id
                ORDER BY a.created_at DESC LIMIT 5";
$recentResult = $conn->query($recentQuery);

$conn->close();

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

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="text-gray-500 text-sm">Total Aspirasi</div>
            <div class="text-3xl font-bold text-blue-600"><?= $total ?></div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="text-gray-500 text-sm">Menunggu</div>
            <div class="text-3xl font-bold text-yellow-600"><?= $statusData['pending'] ?? 0 ?></div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="text-gray-500 text-sm">Proses</div>
            <div class="text-3xl font-bold text-blue-600"><?= $statusData['proses'] ?? 0 ?></div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="text-gray-500 text-sm">Selesai</div>
            <div class="text-3xl font-bold text-green-600"><?= $statusData['selesai'] ?? 0 ?></div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Kategori Paling Banyak Dilaporkan</h3>
        <div class="space-y-3">
            <?php foreach ($categoryData as $cat): ?>
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium">
                            <?= $cat['name'] ?>
                        </span>
                        <span class="text-sm text-gray-500"><?= $cat['count'] ?> laporan</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?= ($cat['count'] / $total * 100) ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold">Aspirasi Terbaru</h3>
        </div>
        <div class="divide-y">
            <?php while ($row = $recentResult->fetch_assoc()): ?>
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900"><?= htmlspecialchars($row['title']) ?></h4>
                            <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars(substr($row['description'], 0, 100)) ?>...</p>
                            <div class="flex gap-3 mt-2 text-xs text-gray-500">
                                <span>📅 <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></span>
                                <span>👤 <?= htmlspecialchars($row['user_name']) ?></span>
                                <span><?= $row['category_name'] ?></span>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium <?= $statusColors[$row['status']] ?>">
                            <?= $statusLabels[$row['status']] ?>
                        </span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
