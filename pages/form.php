<?php
// Get active categories
$conn = getConnection();
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$conn->close();
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Form Aspirasi Siswa</h2>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $success ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-700 font-medium mb-2">Kategori Sarana *</label>
                <select name="category" required 
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Kategori</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>">
                            <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">Judul Pengaduan *</label>
                <input type="text" name="title" required maxlength="200"
                       placeholder="Contoh: Kipas Angin Rusak di Kelas XII IPA 1"
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">Deskripsi Pengaduan *</label>
                <textarea name="description" required rows="6"
                          placeholder="Jelaskan kondisi sarana yang bermasalah secara detail..."
                          class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                <p class="text-sm text-gray-500 mt-1">Deskripsikan masalah dengan jelas agar dapat ditindaklanjuti dengan baik</p>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" name="submit_aspirasi"
                        class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition font-medium">
                    Kirim Aspirasi
                </button>
                <button type="reset"
                        class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Reset
                </button>
            </div>
        </form>
    </div>
    
    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
        <h4 class="font-semibold text-blue-800 mb-2">ℹ️ Informasi</h4>
        <ul class="text-sm text-blue-700 space-y-1">
            <li>• Aspirasi akan ditinjau oleh admin sekolah</li>
            <li>• Anda dapat melihat status pengaduan di menu "Daftar Aspirasi"</li>
            <li>• Histori pengaduan dapat dilihat di menu "Histori"</li>
        </ul>
    </div>
</div>
