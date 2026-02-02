<?php
session_start();
require_once 'config/database.php';

// Handle Login
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

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle Submit Aspirasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_aspirasi']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $category_id = intval($_POST['category']);
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO aspirations (user_id, category_id, title, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $user_id, $category_id, $title, $description);
    
    if ($stmt->execute()) {
        $success = "Aspirasi berhasil diajukan!";
    }
    $stmt->close();
    $conn->close();
}

// Handle Update Status & Feedback (Admin only)
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

<?php if (!$isLoggedIn): ?>
    <!-- Login Page -->
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg w-96">
            <h2 class="text-2xl font-bold text-center mb-6 text-blue-600">Login Aplikasi Pengaduan</h2>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Username</label>
                    <input type="text" name="username" required 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <button type="submit" name="login"
                        class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                    Login
                </button>
            </form>
            
            <div class="mt-4 text-sm text-gray-600 text-center">
                <p>Demo Account:</p>
                <p>Admin: admin / admin123</p>
                <p>Siswa: siswa1 / siswa123</p>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Dashboard Page -->
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
        // Include pages based on current page
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
