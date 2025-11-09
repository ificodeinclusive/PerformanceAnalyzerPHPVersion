<?php // Load DB and helper functions for search and calculations
include('includes/db_connect.php'); include('includes/functions.php'); ?>
<?php
// Read filters from GET (text, grade, percent range)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$gradeFilter = isset($_GET['grade']) ? strtoupper(trim($_GET['grade'])) : '';
$minPercent = isset($_GET['min_percent']) && $_GET['min_percent'] !== '' ? (float)$_GET['min_percent'] : null;
$maxPercent = isset($_GET['max_percent']) && $_GET['max_percent'] !== '' ? (float)$_GET['max_percent'] : null;

// Build the base dataset: use free-text search if provided; else fetch all
if ($q !== '') { $res = searchStudent($conn, $q); } else { $res = $conn->query("SELECT * FROM students"); }
$students = [];
if ($res) {
  while ($row = $res->fetch_assoc()) {
    // For each student, compute totals, percentage, and grade
    $marks = [$row['math'], $row['science'], $row['english'], $row['computer']];
    $row['total'] = calculateTotal($marks);
    $row['percentage'] = calculatePercentage($marks);
    $row['grade'] = assignGrade($row['percentage']);
    $students[] = $row;
  }
}

// Apply filters step by step to keep the logic clear
$filtered = [];
foreach ($students as $s) {
  if ($gradeFilter && strtoupper($s['grade']) !== $gradeFilter) continue;
  if ($minPercent !== null && $s['percentage'] < $minPercent) continue;
  if ($maxPercent !== null && $s['percentage'] > $maxPercent) continue;
  $filtered[] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search · Smart Student Analyzer</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/add_search.css">
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
      <h1 class="page-title"><span class="page-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg></span> Search Students</h1>
      <p class="page-sub">Find students by name or ID and filter by grade and percentage.</p>
    </div>

    <div class="search-card">
      <form method="get" action="search.php">
        <div class="filters-grid">
          <div class="input-wrap">
            <span class="field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg></span>
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by ID or Name" aria-label="Search query">
          </div>
          <div class="input-wrap">
            <span class="field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l9 4-9 4-9-4 9-4z"></path><path d="M19 8v6a7 7 0 01-14 0V8"></path></svg></span>
            <select name="grade" aria-label="Grade filter">
              <option value="">All Grades</option>
              <option value="A" <?= $gradeFilter==='A'?'selected':'' ?>>Grade A</option>
              <option value="B" <?= $gradeFilter==='B'?'selected':'' ?>>Grade B</option>
              <option value="C" <?= $gradeFilter==='C'?'selected':'' ?>>Grade C</option>
              <option value="F" <?= $gradeFilter==='F'?'selected':'' ?>>Grade F</option>
            </select>
          </div>
          <div class="input-wrap">
            <span class="field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18"></path><path d="M12 3v18"></path></svg></span>
            <input type="number" step="0.01" name="min_percent" value="<?= $minPercent!==null?htmlspecialchars($minPercent):'' ?>" placeholder="Min %" aria-label="Minimum percentage">
          </div>
          <div class="input-wrap">
            <span class="field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18"></path><path d="M12 3v18"></path></svg></span>
            <input type="number" step="0.01" name="max_percent" value="<?= $maxPercent!==null?htmlspecialchars($maxPercent):'' ?>" placeholder="Max %" aria-label="Maximum percentage">
          </div>
        </div>
        <div class="search-actions">
          <button type="submit" class="btn">Apply Filters</button>
          <a class="btn btn-outline" href="search.php">Clear</a>
        </div>
      </form>
    </div>

    <div class="results-card">
      <table class="results-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Math</th>
            <th>Science</th>
            <th>English</th>
            <th>Computer</th>
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
              <td><?= $s['math'] ?></td>
              <td><?= $s['science'] ?></td>
              <td><?= $s['english'] ?></td>
              <td><?= $s['computer'] ?></td>
              <td><?= $s['total'] ?></td>
              <td><?= $s['percentage'] ?></td>
              <td>
                <?php
                  // Pick a badge color class based on the grade letter
                  $gClass = strtolower($s['grade']);
                  $gClass = $gClass === 'a' ? 'grade-a' : ($gClass === 'b' ? 'grade-b' : ($gClass === 'c' ? 'grade-c' : 'grade-f'));
                ?>
                <span class="grade-badge <?= $gClass ?>"><?= $s['grade'] ?></span>
              </td>
              <td>
                <div class="download-actions">
                  <!-- Links to view the detailed report or download as Excel -->
                  <a class="action-link" href="report.php?id=<?= $s['id'] ?>" target="_blank" title="View Report" aria-label="View Report">
                    <span class="action-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M9 7h6M9 11h6M9 15h4"></path></svg></span> View
                  </a>
                  <a class="action-link" href="report.php?id=<?= $s['id'] ?>&format=xls" title="Download Excel" aria-label="Download Excel">
                    <span class="action-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="M8 8h8M8 12h8M8 16h5"></path></svg></span> XLS
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; if (empty($filtered)): ?>
            <tr><td colspan="10">No matching students.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <footer class="app-footer">© 2025 iFiCode Inclusive Pvt. Ltd.</footer>
</body>
</html>
