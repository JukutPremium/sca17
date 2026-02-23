<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        }
    }
    $error = "Username atau password salah!";
    $stmt->close();
    $conn->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $reg_username = trim($_POST['reg_username']);
    $reg_name = trim($_POST['reg_name']);
    $reg_password = $_POST['reg_password'];
    $reg_password_confirm = $_POST['reg_password_confirm'];
    $reg_role = 'siswa';

    if (empty($reg_username) || empty($reg_name) || empty($reg_password)) {
        $reg_error = "Semua field harus diisi!";
    } elseif (strlen($reg_username) < 3) {
        $reg_error = "Username minimal 3 karakter!";
    } elseif (strlen($reg_password) < 6) {
        $reg_error = "Password minimal 6 karakter!";
    } elseif ($reg_password !== $reg_password_confirm) {
        $reg_error = "Konfirmasi password tidak cocok!";
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $reg_username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $reg_error = "Username sudah digunakan, pilih username lain!";
        } else {
            $hashed_password = password_hash($reg_password, PASSWORD_DEFAULT);
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO users (username, name, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $reg_username, $reg_name, $hashed_password, $reg_role);
            if ($stmt->execute()) {
                $reg_success = "Registrasi berhasil! Silakan login dengan akun Anda.";
                $show_register = false;
            } else {
                $reg_error = "Gagal melakukan registrasi, coba lagi!";
            }
        }
        $stmt->close();
        $conn->close();
    }
    $show_register = isset($reg_error); // stay on register form if error
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_aspirasi']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $category_id = intval($_POST['category']);
    $title = $_POST['title'];
    $description = $_POST['description'];
    $photo_path = null;
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $max_size = 5 * 1024 * 1024;
        
        $file_type = $_FILES['photo']['type'];
        $file_size = $_FILES['photo']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $upload_error = "Hanya file JPG, JPEG, dan PNG yang diperbolehkan!";
        } elseif ($file_size > $max_size) {
            $upload_error = "Ukuran file maksimal 5MB!";
        } else {
            $upload_dir = 'uploads/aspirations/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $new_filename = 'aspiration_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $photo_path = $upload_path;
            } else {
                $upload_error = "Gagal mengupload foto!";
            }
        }
    }
    
    if (!isset($upload_error)) {
        $conn = getConnection();
        $stmt = $conn->prepare("INSERT INTO aspirations (user_id, category_id, title, description, photo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $user_id, $category_id, $title, $description, $photo_path);
        
        if ($stmt->execute()) {
            $success = "Aspirasi berhasil diajukan!";
        } else {
            $upload_error = "Gagal menyimpan aspirasi!";
        }
        $stmt->close();
        $conn->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && $_SESSION['role'] === 'admin') {
    $id = $_POST['aspiration_id'];
    $status = $_POST['status'];
    $feedback = $_POST['feedback'];
    $progress = $_POST['progress'];
    
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE aspirations SET status = ?, feedback = ?, progress = ? WHERE id = ?");
    $stmt->bind_param("sssi", $status, $feedback, $progress, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    header('Location: index.php?page=list');
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && $_SESSION['role'] === 'admin';
$currentPage = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Pengaduan Sarana Sekolah</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<?php
    $show_register = isset($show_register) ? $show_register : (isset($_GET['tab']) && $_GET['tab'] === 'register');
?>
<?php if (!$isLoggedIn): ?>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
            <h2 class="text-2xl font-bold text-center mb-6 text-blue-600">Aplikasi Pengaduan Sarana Sekolah</h2>

            <!-- Tab Toggle -->
            <div class="flex rounded-lg overflow-hidden border border-blue-200 mb-6">
                <button onclick="showTab('login')" id="tab-login"
                    class="flex-1 py-2 text-sm font-semibold transition <?= !$show_register ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 hover:bg-blue-50' ?>">
                    Login
                </button>
                <button onclick="showTab('register')" id="tab-register"
                    class="flex-1 py-2 text-sm font-semibold transition <?= $show_register ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 hover:bg-blue-50' ?>">
                    Daftar Akun
                </button>
            </div>

            <!-- LOGIN FORM -->
            <div id="form-login" class="<?= $show_register ? 'hidden' : '' ?>">
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($reg_success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm">
                        <?= htmlspecialchars($reg_success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium mb-1">Username</label>
                        <input type="text" name="username" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-medium mb-1">Password</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <button type="submit" name="login"
                            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition font-semibold">
                        Login
                    </button>
                </form>

                <div class="mt-5 text-xs text-gray-500 text-center border-t pt-4">
                    <p class="font-semibold mb-1">Demo Account:</p>
                    <p>Admin: <strong>admin</strong> / <strong>admin123</strong></p>
                    <p>Siswa: <strong>siswa1</strong> / <strong>siswa123</strong></p>
                </div>
            </div>

            <!-- REGISTER FORM -->
            <div id="form-register" class="<?= !$show_register ? 'hidden' : '' ?>">
                <?php if (isset($reg_error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                        <?= htmlspecialchars($reg_error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium mb-1">Username</label>
                        <input type="text" name="reg_username" required minlength="3"
                               value="<?= isset($reg_username) ? htmlspecialchars($reg_username) : '' ?>"
                               placeholder="Minimal 3 karakter"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium mb-1">Nama Lengkap</label>
                        <input type="text" name="reg_name" required
                               value="<?= isset($reg_name) ? htmlspecialchars($reg_name) : '' ?>"
                               placeholder="Masukkan nama lengkap Anda"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium mb-1">Password</label>
                        <input type="password" name="reg_password" required minlength="6"
                               placeholder="Minimal 6 karakter"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium mb-1">Konfirmasi Password</label>
                        <input type="password" name="reg_password_confirm" required
                               placeholder="Ulangi password Anda"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-medium mb-1">Role</label>
                        <input type="text" value="Siswa" disabled
                               class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-500 text-sm cursor-not-allowed">
                        <p class="text-xs text-gray-400 mt-1">Role akun baru otomatis sebagai Siswa</p>
                    </div>
                    <button type="submit" name="register"
                            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition font-semibold">
                        Daftar Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tab) {
            const loginForm = document.getElementById('form-login');
            const registerForm = document.getElementById('form-register');
            const tabLogin = document.getElementById('tab-login');
            const tabRegister = document.getElementById('tab-register');

            if (tab === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                tabLogin.classList.add('bg-blue-600', 'text-white');
                tabLogin.classList.remove('bg-white', 'text-blue-600', 'hover:bg-blue-50');
                tabRegister.classList.remove('bg-blue-600', 'text-white');
                tabRegister.classList.add('bg-white', 'text-blue-600', 'hover:bg-blue-50');
            } else {
                registerForm.classList.remove('hidden');
                loginForm.classList.add('hidden');
                tabRegister.classList.add('bg-blue-600', 'text-white');
                tabRegister.classList.remove('bg-white', 'text-blue-600', 'hover:bg-blue-50');
                tabLogin.classList.remove('bg-blue-600', 'text-white');
                tabLogin.classList.add('bg-white', 'text-blue-600', 'hover:bg-blue-50');
            }
        }
    </script>

<?php else: ?>
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Pengaduan Sarana Sekolah</h1>
            <div class="flex items-center gap-4">
                <span><?= $_SESSION['name'] ?> (<?= ucfirst($_SESSION['role']) ?>)</span>
                <a href="?logout=1" class="bg-red-500 px-4 py-2 rounded hover:bg-red-600">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-6 px-4">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
            <a href="?page=dashboard" 
               class="<?= $currentPage === 'dashboard' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' ?> p-4 rounded-lg shadow hover:shadow-lg transition">
                <div class="font-semibold">Dashboard</div>
            </a>
            <a href="?page=form" 
               class="<?= $currentPage === 'form' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' ?> p-4 rounded-lg shadow hover:shadow-lg transition">
                <div class="font-semibold">Form Aspirasi</div>
            </a>
            <a href="?page=list" 
               class="<?= $currentPage === 'list' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' ?> p-4 rounded-lg shadow hover:shadow-lg transition">
                <div class="font-semibold">Daftar Aspirasi</div>
            </a>
            <a href="?page=history" 
               class="<?= $currentPage === 'history' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' ?> p-4 rounded-lg shadow hover:shadow-lg transition">
                <div class="font-semibold">Histori</div>
            </a>
            <?php if ($isAdmin): ?>
            <a href="?page=users" 
               class="<?= $currentPage === 'users' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' ?> p-4 rounded-lg shadow hover:shadow-lg transition">
                <div class="font-semibold">Kelola User</div>
            </a>
            <a href="?page=categories" 
               class="<?= $currentPage === 'categories' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' ?> p-4 rounded-lg shadow hover:shadow-lg transition">
                <div class="font-semibold">Kelola Kategori</div>
            </a>
            <?php endif; ?>
        </div>

        <?php
        switch ($currentPage) {
            case 'form':
                include 'pages/form.php';
                break;
            case 'list':
                include 'pages/list.php';
                break;
            case 'history':
                include 'pages/history.php';
                break;
            case 'users':
                if ($isAdmin) include 'pages/users.php';
                break;
            case 'categories':
                if ($isAdmin) include 'pages/categories.php';
                break;
            default:
                include 'pages/dashboard.php';
        }
        ?>
    </div>
<?php endif; ?>

</body>
</html>