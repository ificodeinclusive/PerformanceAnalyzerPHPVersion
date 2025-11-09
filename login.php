<?php
session_start();
require_once('includes/db_connect.php');

$error = '';
$next = isset($_GET['next']) ? $_GET['next'] : (isset($_POST['next']) ? $_POST['next'] : 'admin/dashboard.php');
// If already logged in, send to destination
if (isset($_SESSION['admin_id'])) {
  header('Location: ' . $next);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $admin_id = isset($_POST['admin_id']) ? (int)$_POST['admin_id'] : 0;
  $username = isset($_POST['username']) ? trim($_POST['username']) : '';
  $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

  if ($admin_id > 0 && $username !== '' && $password !== '') {
    $stmt = $conn->prepare("SELECT admin_id, name, role, username, password FROM admin WHERE admin_id = ? AND username = ?");
    $stmt->bind_param('is', $admin_id, $username);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      $admin = $res ? $res->fetch_assoc() : null;
      if ($admin) {
        // Note: plaintext passwords in admin.sql; in production use password_hash
        if ($admin['password'] === $password) {
          $_SESSION['admin_id'] = (int)$admin['admin_id'];
          $_SESSION['admin_username'] = $admin['username'];
          $_SESSION['admin_name'] = $admin['name'];
          $_SESSION['admin_role'] = $admin['role'];
          header('Location: ' . $next);
          exit;
        } else {
          $error = 'Invalid password.';
        }
      } else {
        $error = 'Admin not found with provided admin_id and username.';
      }
    } else {
      $error = 'Login query failed: ' . $stmt->error;
    }
    $stmt->close();
  } else {
    $error = 'Please provide admin ID, username, and password.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login · Smart Student Analyzer</title>
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/login.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
</head>
<body>
  <header class="app-header">
    <div class="brand"><span class="brand-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 3l9 4-9 4-9-4 9-4z"></path>
        <path d="M7 10v3a5 5 0 0010 0v-3"></path>
        <path d="M19 8v6"></path>
      </svg>
    </span> Smart Student Analyzer</div>
  </header>

  <section class="login-wrap">
    <div class="login-card">
      <div class="login-header">
        <div class="login-title"><span class="login-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="14" rx="2"></rect><path d="M7 8h6M7 12h10"></path></svg></span> Admin Login</div>
        <a href="index.php" class="btn btn-outline">Back</a>
      </div>
      <?php if ($error): ?>
        <div class="login-alert" role="alert">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      <form method="POST" action="login.php<?= isset($_GET['next']) ? ('?next=' . urlencode($_GET['next'])) : '' ?>" class="login-form" autocomplete="off">
        <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>" />
        <div class="input-grid">
          <div class="input-group">
            <label for="admin_id" class="input-label">Admin ID</label>
            <div class="input-wrap">
              <span class="field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"></circle><path d="M5.5 21c1.2-3 4-5 6.5-5s5.3 2 6.5 5"></path></svg></span>
              <input type="number" id="admin_id" name="admin_id" required class="field-input" placeholder="Enter your admin ID" />
            </div>
          </div>
          <div class="input-group">
            <label for="username" class="input-label">Username</label>
            <div class="input-wrap">
              <span class="field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"></circle><path d="M5.5 21c1.2-3 4-5 6.5-5s5.3 2 6.5 5"></path></svg></span>
              <input type="text" id="username" name="username" required class="field-input" placeholder="Enter your username" />
            </div>
          </div>
          <div class="input-group">
            <label for="password" class="input-label">Password</label>
            <div class="input-wrap">
              <span class="field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V8a5 5 0 0 1 10 0v3"></path></svg></span>
              <input type="password" id="password" name="password" required class="field-input" placeholder="Enter your password" />
            </div>
          </div>
        </div>
        <div class="login-actions">
          <button type="submit" class="btn">Login</button>
          <a href="admin/dashboard.php" class="btn btn-outline">Dashboard</a>
        </div>
        <p class="login-note">Use credentials stored in <code>database/admin.sql</code>.</p>
      </form>
    </div>
  </section>

  <footer class="app-footer">© 2025 iFiCode Inclusive Pvt. Ltd.</footer>
</body>
</html>