<?php
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
        
        <?php if (isset($upload_error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $upload_error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-gray-700 font-medium mb-2">Kategori Sarana *</label>
                <select name="category" required 
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Kategori</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>">
                            <?= htmlspecialchars($cat['name']) ?>
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
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">Upload Foto (Opsional)</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition">
                    <input type="file" name="photo" id="photo" accept="image/*" 
                           class="hidden" onchange="previewImage(this)">
                    <label for="photo" class="cursor-pointer">
                        <div id="preview-container" class="hidden mb-4">
                            <img id="preview-image" class="max-h-64 mx-auto rounded-lg shadow">
                        </div>
                        <div id="upload-placeholder">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">
                                <span class="font-medium text-blue-600">Klik untuk upload foto</span> atau drag & drop
                            </p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, JPEG maks. 5MB</p>
                        </div>
                    </label>
                </div>
                <p class="text-sm text-gray-500 mt-2">Upload foto kondisi sarana yang rusak untuk mempercepat proses perbaikan</p>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" name="submit_aspirasi"
                        class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition font-medium">
                    Kirim Aspirasi
                </button>
                <button type="reset" onclick="resetPreview()"
                        class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Reset
                </button>
            </div>
        </form>
    </div>
    
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
        <h4 class="font-semibold text-blue-800 mb-2">ℹ️ Informasi</h4>
        <ul class="text-sm text-blue-700 space-y-1">
            <li>• Aspirasi akan ditinjau oleh admin sekolah</li>
            <li>• Upload foto kondisi sarana untuk dokumentasi</li>
            <li>• Anda dapat melihat status pengaduan di menu "Daftar Aspirasi"</li>
            <li>• Histori pengaduan dapat dilihat di menu "Histori"</li>
        </ul>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('preview-image');
    const previewContainer = document.getElementById('preview-container');
    const placeholder = document.getElementById('upload-placeholder');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

function resetPreview() {
    const preview = document.getElementById('preview-image');
    const previewContainer = document.getElementById('preview-container');
    const placeholder = document.getElementById('upload-placeholder');
    
    preview.src = '';
    previewContainer.classList.add('hidden');
    placeholder.classList.remove('hidden');
}
</script>