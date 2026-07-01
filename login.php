<?php
require_once __DIR__ . '/../config/database.php';

session_start();
if (isset($_SESSION['lgu_user'])) {
    header('Location: dashboard.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT id, username, password_hash, role, barangay_id
             FROM lgu_accounts WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $account = $stmt->fetch();

        if ($account && password_verify($password, $account['password_hash'])) {
            $_SESSION['lgu_user'] = [
                'id'            => (int) $account['id'],
                'username'      => $account['username'],
                'role'          => $account['role'],
                'barangay_id'   => $account['barangay_id'] !== null ? (int) $account['barangay_id'] : null,
                'last_activity' => time(),
            ];
            header('Location: dashboard.php');
            exit;
        }
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BRFE — LGU Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-900 to-blue-700 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-8">
    <div class="text-center mb-8">
      <div class="text-4xl mb-2">🌊</div>
      <h1 class="text-2xl font-bold text-gray-900">BRFE App</h1>
      <p class="text-sm text-gray-500 mt-1">LGU / Barangay Official Portal</p>
    </div>

    <?php if ($error): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="space-y-4">
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
        <input
          type="text"
          name="username"
          required
          autocomplete="username"
          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter username"
        />
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
        <input
          type="password"
          name="password"
          required
          autocomplete="current-password"
          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter password"
        />
      </div>
      <button
        type="submit"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm"
      >
        Sign In
      </button>
    </form>

    <p class="text-center text-xs text-gray-400 mt-6">
      Bago City Flood Evacuees Management System
    </p>
  </div>
</body>
</html>
