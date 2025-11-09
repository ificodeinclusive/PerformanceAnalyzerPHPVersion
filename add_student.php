<?php // Load database connection for saving new students
include('includes/db_connect.php'); ?>
<?php
// Prepare feedback message for the user (success or error)
$flashType = null; $flashMsg = null;
// If the form was submitted, read and validate inputs
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Remove extra spaces from the name
  $name = trim($_POST['name'] ?? '');
  // Turn inputs into integers so math is safe
  $math = (int)($_POST['math'] ?? 0);
  $science = (int)($_POST['science'] ?? 0);
  $english = (int)($_POST['english'] ?? 0);
  $computer = (int)($_POST['computer'] ?? 0);

  // Escape the name to avoid breaking SQL; simple defense
  $nameEsc = $conn->real_escape_string($name);
  // Save the new student; show a friendly message
  $sql = "INSERT INTO students (name, math, science, english, computer) VALUES ('$nameEsc','$math','$science','$english','$computer')";
  if ($conn->query($sql) === TRUE) { $flashType = 'success'; $flashMsg = 'Student added successfully!'; }
  else { $flashType = 'error'; $flashMsg = 'Error: ' . htmlspecialchars($conn->error); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Student · Smart Student Analyzer</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/add_student.css">
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
    <nav class="nav-links">
      <a href="index.php">Home</a>
      <a href="add_student.php">Add Student</a>
      <a href="analyze.php">Analyze</a>
      <a href="search.php">Search</a>
      <a href="career.php">Career</a>
      <a href="admin/dashboard.php">Admin</a>
      <a href="logout.php">Logout</a>
    </nav>
  </header>

  <section class="page-wrap">
    <div class="page-header">
      <h1 class="page-title"><span class="page-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"></circle><path d="M5.5 21c1.2-3 4-5 6.5-5s5.3 2 6.5 5"></path><path d="M19 3v6M22 6h-6"></path></svg></span> Add New Student</h1>
      <p class="page-sub">Enter student details and marks (0–100). Fields are required.</p>
    </div>

    <?php if ($flashType): ?>
      <!-- Show success or error message after saving -->
      <div class="alert <?= $flashType === 'success' ? 'alert-success' : 'alert-error' ?>">
        <span class="alert-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php if ($flashType === 'success'): ?><path d="M3 12l5 5 13-13"></path><?php else: ?><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path><?php endif; ?></svg></span>
        <?= htmlspecialchars($flashMsg) ?>
      </div>
    <?php endif; ?>

    <div class="form-card">
      <!-- Form to capture student details and marks -->
      <form method="POST">
        <div class="form-grid">
          <div class="form-field">
            <label for="name" class="form-label">Name</label>
            <div class="input-wrap">
              <span class="field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"></circle><path d="M5.5 21c1.2-3 4-5 6.5-5s5.3 2 6.5 5"></path></svg></span>
              <input class="field-input" type="text" id="name" name="name" placeholder="Full name" required>
            </div>
          </div>

          <div class="form-field">
            <label for="math" class="form-label">Math</label>
            <div class="input-wrap">
              <span class="field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="2"></rect><path d="M8 20h8M10 16h4"></path></svg></span>
              <input class="field-input" type="number" id="math" name="math" min="0" max="100" step="1" placeholder="0–100" required>
            </div>
          </div>

          <div class="form-field">
            <label for="science" class="form-label">Science</label>
            <div class="input-wrap">
              <span class="field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v4l-5 8a5 5 0 005 8h4a5 5 0 005-8l-5-8V2"></path></svg></span>
              <input class="field-input" type="number" id="science" name="science" min="0" max="100" step="1" placeholder="0–100" required>
            </div>
          </div>

          <div class="form-field">
            <label for="english" class="form-label">English</label>
            <div class="input-wrap">
              <span class="field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19h16V5a2 2 0 00-2-2H6a2 2 0 00-2 2v14z"></path><path d="M8 7h8M8 11h8M8 15h6"></path></svg></span>
              <input class="field-input" type="number" id="english" name="english" min="0" max="100" step="1" placeholder="0–100" required>
            </div>
          </div>

          <div class="form-field">
            <label for="computer" class="form-label">Computer</label>
            <div class="input-wrap">
              <span class="field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="2"></rect><path d="M8 20h8M10 16h4"></path></svg></span>
              <input class="field-input" type="number" id="computer" name="computer" min="0" max="100" step="1" placeholder="0–100" required>
            </div>
          </div>
        </div>

        <div class="actions-row">
          <button type="submit" class="btn">Save Student</button>
          <a href="index.php" class="btn btn-outline">Cancel</a>
          <button type="reset" class="btn btn-outline" onclick="return true;">Reset</button>
        </div>
      </form>
    </div>
  </section>

  <footer class="app-footer">© 2025 iFiCode Inclusive Pvt. Ltd.</footer>
</body>
</html>
