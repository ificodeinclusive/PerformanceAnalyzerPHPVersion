<?php
include('../includes/db_connect.php');
require_once('../includes/functions.php');
session_start();
// Auth guard: require admin login
if (!isset($_SESSION['admin_id'])) {
  header('Location: ../login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
  exit;
}

// Grade thresholds aligned with index.php
function computeGrade($p) {
  if ($p >= 80) return 'A';
  if ($p >= 70) return 'B';
  if ($p >= 60) return 'C';
  if ($p >= 50) return 'D';
  return 'F';
}
function in_range($n) { return is_numeric($n) && $n >= 0 && $n <= 100; }

$messages = [];

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  $_SESSION['activity_log'][] = ['type' => 'export_csv', 'time' => date('Y-m-d H:i:s'), 'meta' => 'Students CSV exported'];
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="students_export.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['ID','Name','Math','Science','English','Computer','Total','Percentage','Grade']);
  $resCsv = $conn->query("SELECT id, name, math, science, english, computer FROM students");
  while ($row = $resCsv->fetch_assoc()) {
    $totalRow = $row['math'] + $row['science'] + $row['english'] + $row['computer'];
    $perc = round($totalRow / 4, 2);
    $grade = computeGrade($perc);
    fputcsv($out, [$row['id'],$row['name'],$row['math'],$row['science'],$row['english'],$row['computer'],$totalRow,$perc,$grade]);
  }
  fclose($out);
  exit;
}

// CRUD actions: insert, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  if ($action === 'insert') {
    $name = trim($_POST['name'] ?? '');
    $math = (int)($_POST['math'] ?? 0);
    $science = (int)($_POST['science'] ?? 0);
    $english = (int)($_POST['english'] ?? 0);
    $computer = (int)($_POST['computer'] ?? 0);
    $valid = $name !== '' && in_range($math) && in_range($science) && in_range($english) && in_range($computer);
    if ($valid) {
      $stmt = $conn->prepare("INSERT INTO students (name, math, science, english, computer) VALUES (?,?,?,?,?)");
      $stmt->bind_param("siiii", $name, $math, $science, $english, $computer);
      if ($stmt->execute()) { $messages[] = 'Student inserted successfully.'; $_SESSION['activity_log'][] = ['type' => 'insert', 'time' => date('Y-m-d H:i:s'), 'meta' => 'Added '. $name .' (ID '. $conn->insert_id .')']; }
      else { $messages[] = 'Insert failed: ' . $stmt->error; }
      $stmt->close();
    } else {
      $messages[] = 'Invalid input. Provide name and marks between 0 and 100.';
    }
  } elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $math = (int)($_POST['math'] ?? 0);
    $science = (int)($_POST['science'] ?? 0);
    $english = (int)($_POST['english'] ?? 0);
    $computer = (int)($_POST['computer'] ?? 0);
    $valid = $id > 0 && $name !== '' && in_range($math) && in_range($science) && in_range($english) && in_range($computer);
    if ($valid) {
      $stmt = $conn->prepare("UPDATE students SET name=?, math=?, science=?, english=?, computer=? WHERE id=?");
      $stmt->bind_param("siiiii", $name, $math, $science, $english, $computer, $id);
      if ($stmt->execute()) { $messages[] = 'Student updated successfully.'; $_SESSION['activity_log'][] = ['type' => 'update', 'time' => date('Y-m-d H:i:s'), 'meta' => 'Updated #'. $id .' ('. $name .')']; }
      else { $messages[] = 'Update failed: ' . $stmt->error; }
      $stmt->close();
    } else {
      $messages[] = 'Invalid update. Check inputs.';
    }
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
      $stmt->bind_param("i", $id);
      if ($stmt->execute()) { $messages[] = 'Student deleted successfully.'; $_SESSION['activity_log'][] = ['type' => 'delete', 'time' => date('Y-m-d H:i:s'), 'meta' => 'Deleted #'. $id]; }
      else { $messages[] = 'Delete failed: ' . $stmt->error; }
      $stmt->close();
    } else {
      $messages[] = 'Invalid delete request.';
    }
  }
}

// Filters
$gradeFilter = isset($_GET['grade']) ? strtoupper(trim($_GET['grade'])) : '';
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

// Collect students and stats
$students = [];
$total = 0; $overallAvg = 0; $passRate = 0; $topper = null;
$resAll = $conn->query("SELECT id, name, math, science, english, computer FROM students");
if ($resAll) {
  while ($row = $resAll->fetch_assoc()) {
    $totalMarks = $row['math'] + $row['science'] + $row['english'] + $row['computer'];
    $perc = round($totalMarks / 4, 2);
    $grade = computeGrade($perc);
    $row['total'] = $totalMarks;
    $row['percentage'] = $perc;
    $row['grade'] = $grade;
    $students[] = $row;
  }
}
$total = count($students);
if ($total > 0) {
  $overallAvg = round(array_sum(array_column($students, 'percentage')) / $total, 2);
  $passCount = 0; foreach ($students as $s) { if ($s['grade'] !== 'F') $passCount++; }
  $passRate = round(($passCount / $total) * 100, 1);
  $sorted = $students; usort($sorted, function($a,$b){ return $b['total'] <=> $a['total']; });
  $topper = $sorted[0];
}

// Apply filters
$filtered = $students;
if ($gradeFilter) { $filtered = array_values(array_filter($filtered, fn($s) => $s['grade'] === $gradeFilter)); }
if ($keyword !== '') {
  $kw = strtolower($keyword);
  $filtered = array_values(array_filter($filtered, function($s) use ($kw) {
    return strpos(strtolower($s['name']), $kw) !== false || (string)$s['id'] === $kw;
  }));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/add_dashboard.css">
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
      <a href="../index.php">Home</a>
      <a href="../add_student.php">Add Student</a>
      <a href="../analyze.php">Analyze</a>
      <a href="../search.php">Search</a>
      <a href="../career.php">Career</a>
      <a href="dashboard.php">Admin</a>
      <a href="../logout.php">Logout</a>
    </nav>
  </header>

  <div class="dashboard-wrap">
    <div class="page-title"><span class="title-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="14" rx="2"></rect><path d="M7 8h6M7 12h10"></path></svg>
    </span> Admin Dashboard</div>

    <?php if (!empty($messages)): ?>
      <div class="messages">
        <?php foreach ($messages as $m): ?>
          <div class="msg msg-success"><?= htmlspecialchars($m) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="admin-toolbar">
      <a href="#add-form" class="btn btn-muted"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"></path></svg></span> Add Student</a>
      <a href="dashboard.php?export=csv" class="btn btn-outline"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg></span> Export CSV</a>
      <a href="../analyze.php" class="btn"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17h18"></path><path d="M4 15l5-5 4 3 7-7"></path></svg></span> Analyze</a>
      <a href="../search.php" class="btn btn-outline"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.3-4.3"></path></svg></span> Search</a>
    </div>

    <div class="stats-grid">
      <div class="stat-card"><h3>Total Students</h3><div class="value"><?= $total ?></div></div>
      <div class="stat-card"><h3>Overall Average</h3><div class="value"><?= $overallAvg ?>%</div></div>
      <div class="stat-card"><h3>Pass Rate</h3><div class="value"><?= $passRate ?>%</div></div>
      <div class="stat-card"><h3>Topper</h3><div class="value"><?= $topper ? htmlspecialchars($topper['name']) : '—' ?></div><div class="stat-sub">Total <?= $topper ? $topper['total'] : '—' ?></div></div>
  </div>

    <div id="add-form" class="card">
      <div class="card-title"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"></path></svg></span> Add Student</div>
      <form method="post" class="form-grid">
        <input type="hidden" name="action" value="insert">
        <input name="name" placeholder="Student name" required>
        <input name="math" type="number" min="0" max="100" placeholder="Math" required>
        <input name="science" type="number" min="0" max="100" placeholder="Science" required>
        <input name="english" type="number" min="0" max="100" placeholder="English" required>
        <input name="computer" type="number" min="0" max="100" placeholder="Computer" required>
        <div class="form-actions">
          <button type="submit" class="btn"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6l-11 11H5v-4z"></path></svg></span> Save</button>
          <a href="#" class="btn btn-outline"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L18 6"></path><path d="M6 6l12 12"></path></svg></span> Cancel</a>
        </div>
      </form>
    </div>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])): ?>
      <?php
        $eid = (int)$_GET['id'];
        $row = null;
        $resEdit = $conn->query("SELECT * FROM students WHERE id={$eid} LIMIT 1");
        if ($resEdit && $resEdit->num_rows) { $row = $resEdit->fetch_assoc(); }
      ?>
      <?php if ($row): ?>
        <div class="card">
          <div class="card-title"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5l4 4-11 11H5v-4z"></path></svg></span> Edit Student #<?= $row['id'] ?></div>
          <form method="post" class="form-grid">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <input name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
            <input name="math" type="number" min="0" max="100" value="<?= (int)$row['math'] ?>" required>
            <input name="science" type="number" min="0" max="100" value="<?= (int)$row['science'] ?>" required>
            <input name="english" type="number" min="0" max="100" value="<?= (int)$row['english'] ?>" required>
            <input name="computer" type="number" min="0" max="100" value="<?= (int)$row['computer'] ?>" required>
            <div class="form-actions">
              <button type="submit" class="btn"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6l-11 11H5v-4z"></path></svg></span> Update</button>
              <a href="dashboard.php" class="btn btn-outline">Cancel</a>
            </div>
          </form>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="card">
      <div class="card-title"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v4H3z"></path><path d="M3 11h18v10H3z"></path></svg></span> Manage Students</div>
      <form method="get" class="filter-row" action="dashboard.php">
        <div>
          <label>Search</label>
          <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Name or ID">
        </div>
        <div>
          <label>Grade</label>
          <select name="grade">
            <option value="">All</option>
            <option value="A" <?= $gradeFilter==='A'?'selected':'' ?>>A</option>
            <option value="B" <?= $gradeFilter==='B'?'selected':'' ?>>B</option>
            <option value="C" <?= $gradeFilter==='C'?'selected':'' ?>>C</option>
            <option value="D" <?= $gradeFilter==='D'?'selected':'' ?>>D</option>
            <option value="F" <?= $gradeFilter==='F'?'selected':'' ?>>F</option>
          </select>
        </div>
        <div>
          <label>&nbsp;</label>
          <button class="btn btn-muted" type="submit"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg></span> Apply</button>
        </div>
      </form>

      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Total</th>
            <th>%</th>
            <th>Grade</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($filtered as $s): ?>
            <tr>
              <td><?= $s['id'] ?></td>
              <td><?= htmlspecialchars($s['name']) ?></td>
              <td><?= $s['total'] ?></td>
              <td><?= $s['percentage'] ?>%</td>
              <td>
                <?php $class = 'badge-'.strtolower($s['grade']); ?>
                <?php $mapIcon = [
                  'A' => "<svg viewBox='0 0 24 24' width='14' height='14' xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M12 4l6 16M6 20L12 4M8.5 13h7'></path></svg>",
                  'B' => "<svg viewBox='0 0 24 24' width='14' height='14' xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M6 4h8a4 4 0 010 8H6z'></path><path d='M6 12h9a4 4 0 010 8H6z'></path></svg>",
                  'C' => "<svg viewBox='0 0 24 24' width='14' height='14' xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M18 8a6 6 0 10-6 10'></path></svg>",
                  'D' => "<svg viewBox='0 0 24 24' width='14' height='14' xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M6 4h6a6 6 0 010 16H6z'></path></svg>",
                  'F' => "<svg viewBox='0 0 24 24' width='14' height='14' xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M6 6l12 12M18 6L6 18'></path></svg>"
                ]; ?>
                <span class="grade-badge <?= $class ?>"><span class="title-icon" aria-hidden="true"><?= $mapIcon[$s['grade']] ?></span><?= $s['grade'] ?></span>
              </td>
              <td>
                <div class="row-actions">
                  <a class="icon-btn" href="../report.php?id=<?= $s['id'] ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13v13H8z"></path><path d="M3 8h5v13H3z"></path></svg>
                    Report
                  </a>
                  <a class="icon-btn" href="dashboard.php?action=edit&id=<?= $s['id'] ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5l4 4-11 11H5v-4z"></path></svg>
                    Edit
                  </a>
                  <form method="post" style="display:inline" onsubmit="return confirm('Delete this student?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button class="icon-btn" type="submit">
                      <svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6M14 11v6"></path></svg>
                      Delete
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="card-title"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v4H4z"></path><path d="M4 12h16v8H4z"></path></svg></span> Activity Log</div>
      <?php $activity = isset($_SESSION['activity_log']) ? array_slice(array_reverse($_SESSION['activity_log']), 0, 12) : []; ?>
      <?php if (!empty($activity)): ?>
        <ul class="activity-list">
          <?php foreach ($activity as $item): ?>
            <li class="activity-item">
              <span class="activity-type">
                <?php
                  $icons = [
                    'insert' => "<svg viewBox='0 0 24 24' width='14' height='14' xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M12 5v14M5 12h14'></path></svg>",
                    'update' => "<svg viewBox='0 0 24 24' width='14' height='14' xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M12 20h9'></path><path d='M16.5 3.5l4 4-11 11H5v-4z'></path></svg>",
                    'delete' => "<svg viewBox='0 0 24 24' width='14' height='14' xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M3 6h18'></path><path d='M8 6V4h8v2'></path><path d='M19 6l-1 14H6L5 6'></path></svg>",
                    'export_csv' => "<svg viewBox='0 0 24 24' width='14' height='14' xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'></path><path d='M7 10l5 5 5-5'></path><path d='M12 15V3'></path></svg>"
                  ];
                  $icon = isset($icons[$item['type']]) ? $icons[$item['type']] : "<svg viewBox='0 0 24 24' width='14' height='14' xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='9'></circle></svg>";
                ?>
                <span class="title-icon" aria-hidden="true"><?= $icon ?></span>
                <?= htmlspecialchars(strtoupper(str_replace('_',' ', $item['type']))) ?>
              </span>
              <span class="activity-meta"><?= htmlspecialchars($item['meta']) ?> · <?= htmlspecialchars($item['time']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="activity-empty">No activity yet.</div>
      <?php endif; ?>
    </div>
  </div>
  <footer class="app-footer">© 2025 iFiCode Inclusive Pvt. Ltd.</footer>
</body>
</html>