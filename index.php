<?php
// Load database connection and make it ready for queries
include('includes/db_connect.php');

// Stats
// Count how many students are currently in the system
$total = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
// Find the student with the highest total marks
$topper = $conn->query("SELECT name, (math+science+english+computer) AS total FROM students ORDER BY total DESC LIMIT 1")->fetch_assoc();
// Compute class-wide average marks for each subject
$avg = $conn->query("SELECT ROUND(AVG(math),2) AS math, ROUND(AVG(science),2) AS science, ROUND(AVG(english),2) AS english, ROUND(AVG(computer),2) AS computer FROM students")->fetch_assoc();
// Build grade counts (A–F) using each student's average percentage
$g = $conn->query("SELECT
  SUM(CASE WHEN ((math+science+english+computer)/4) >= 80 THEN 1 ELSE 0 END) AS A_cnt,
  SUM(CASE WHEN ((math+science+english+computer)/4) BETWEEN 70 AND 79.999 THEN 1 ELSE 0 END) AS B_cnt,
  SUM(CASE WHEN ((math+science+english+computer)/4) BETWEEN 60 AND 69.999 THEN 1 ELSE 0 END) AS C_cnt,
  SUM(CASE WHEN ((math+science+english+computer)/4) BETWEEN 50 AND 59.999 THEN 1 ELSE 0 END) AS D_cnt,
  SUM(CASE WHEN ((math+science+english+computer)/4) < 50 THEN 1 ELSE 0 END) AS F_cnt
FROM students")->fetch_assoc();

// Decide the grade by comparing the percentage to simple cutoffs
// Helper: grade from percentage (aligned with distribution)
function computeGrade($p) {
  if ($p >= 80) return 'A';
  if ($p >= 70) return 'B';
  if ($p >= 60) return 'C';
  if ($p >= 50) return 'D';
  return 'F';
}

// Derived insights
// Average of all subjects — handy for the summary UI
$overallAvg = round(($avg['math'] + $avg['science'] + $avg['english'] + $avg['computer']) / 4, 2);
// Find which subject has the highest class average
$highestSubject = ['label' => '—', 'value' => 0.0];
$map = ['math' => 'Math', 'science' => 'Science', 'english' => 'English', 'computer' => 'Computer'];
foreach ($map as $col => $label) {
  $val = isset($avg[$col]) ? (float)$avg[$col] : 0.0;
  if ($val > $highestSubject['value']) {
    $highestSubject = ['label' => $label, 'value' => $val];
  }
}

// Helper: initials for profile chips
// Trim and split the name, then take the first letters
function computeInitials($name) {
  $name = trim($name);
  if ($name === '') return 'NA';
  $parts = preg_split('/\s+/', $name);
  $first = strtoupper(substr($parts[0], 0, 1));
  $second = isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '';
  return $first . $second;
}

// CSV export
// If the user asks for CSV, stream a simple file to download
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  // Tell the browser we are sending a CSV file
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="students_export.csv"');
  // Open output stream and write the column headers
  $out = fopen('php://output', 'w');
  fputcsv($out, ['ID','Name','Math','Science','English','Computer','Total','Percentage','Grade']);
  // Fetch all students and compute totals, percentage, and grade row by row
  $res = $conn->query("SELECT id, name, math, science, english, computer FROM students");
  while ($row = $res->fetch_assoc()) {
    $totalRow = $row['math'] + $row['science'] + $row['english'] + $row['computer'];
    $perc = round($totalRow / 4, 2);
    $grade = computeGrade($perc);
    fputcsv($out, [$row['id'],$row['name'],$row['math'],$row['science'],$row['english'],$row['computer'],$totalRow,$perc,$grade]);
  }
  fclose($out);
  // Stop here so we don't render the page HTML
  exit;
}

// Export placeholders: PDF & Excel
// PDF: not implemented yet — suggest libraries for later
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
  header('Content-Type: text/plain');
  header('Content-Disposition: attachment; filename="students_report_placeholder.pdf"');
  echo "PDF export is not implemented yet.\nInstall FPDF (https://www.fpdf.org/) or TCPDF and implement report generation.";
  exit;
}
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
  // Excel: send a simple tab-separated format most spreadsheets can open
  header('Content-Type: application/vnd.ms-excel');
  header('Content-Disposition: attachment; filename="students_export.xls"');
  echo "ID\tName\tMath\tScience\tEnglish\tComputer\tTotal\tPercentage\tGrade\n";
  $res = $conn->query("SELECT id, name, math, science, english, computer FROM students");
  while ($row = $res->fetch_assoc()) {
    $totalRow = $row['math'] + $row['science'] + $row['english'] + $row['computer'];
    $perc = round($totalRow / 4, 2);
    $grade = computeGrade($perc);
    echo $row['id']."\t".$row['name']."\t".$row['math']."\t".$row['science']."\t".$row['english']."\t".$row['computer']."\t".$totalRow."\t".$perc."\t".$grade."\n";
  }
  exit;
}

// Top 5 ranked
// Pull top 5 students by total marks and compute % + grade
$topFive = [];
$resTop5 = $conn->query("SELECT id, name, math, science, english, computer, (math+science+english+computer) AS total FROM students ORDER BY total DESC LIMIT 5");
if ($resTop5) {
  while ($r = $resTop5->fetch_assoc()) {
    $perc = round($r['total'] / 4, 2);
    $r['percentage'] = $perc;
    $r['grade'] = computeGrade($perc);
    $topFive[] = $r;
  }
}

// Subject toppers
// For each subject, find the student with the highest marks
$subjectToppers = [];
$map = ['math' => 'Math', 'science' => 'Science', 'english' => 'English', 'computer' => 'Computer'];
foreach ($map as $col => $label) {
  $res = $conn->query("SELECT name, $col AS marks FROM students ORDER BY $col DESC LIMIT 1");
  if ($res && $res->num_rows) {
    $row = $res->fetch_assoc();
    $subjectToppers[] = ['subject' => $label, 'name' => $row['name'], 'marks' => $row['marks']];
  }
}

// Recent additions
// Show last 5 students added for a quick activity view
$recentAdditions = [];
$resRecent = $conn->query("SELECT id, name, math, science, english, computer FROM students ORDER BY id DESC LIMIT 5");
if ($resRecent) {
  while ($row = $resRecent->fetch_assoc()) {
    $totalRow = $row['math'] + $row['science'] + $row['english'] + $row['computer'];
    $perc = round($totalRow / 4, 2);
    $recentAdditions[] = [
      'name' => $row['name'],
      'total' => $totalRow,
      'percentage' => $perc,
      'grade' => computeGrade($perc)
    ];
  }
}

// Subject icons map and topper percentage
// Icons used in UI badges; pre-calc topper percentage for summary
$subjectIcons = [
  'Math' => '<svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"></rect><path d="M8 12h8M12 8v8"></path></svg>',
  'Science' => '<svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v4l-4 7a5 5 0 004 7h4a5 5 0 004-7l-4-7V2"></path></svg>',
  'English' => '<svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="5" width="12" height="14"></rect><path d="M16 5h4v14h-4"></path><path d="M7 8h6M7 11h6M7 14h5"></path></svg>',
  'Computer' => '<svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="2"></rect><path d="M8 20h8M10 16h4"></path></svg>'
];
$topperPerc = isset($topper['total']) ? round($topper['total'] / 4, 1) : 0;
// Degrees for a potential progress ring in hero (100% = 360deg)
$avgDeg = (int)round($overallAvg * 3.6);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Student Performance Analyzer</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
</head>
<body>
  <!-- Minimal Teal Dashboard -->
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

  <section class="hero-pro">
    <div class="hero-content">
      <div class="hero-eyebrow">Smart Student Analyzer</div>
      <h1 class="hero-title">Insightful academics. Professional, actionable visuals.</h1>
      <p class="hero-subtitle">Track class performance, spot trends, and make confident decisions.</p>
      <div class="hero-actions">
        <a href="add_student.php" class="btn">Get Started</a>
        <a href="analyze.php" class="btn btn-outline">View Analytics</a>
      </div>
      <div class="hero-metrics">
        <span class="pill"><span class="pill-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="10" r="3"></circle><circle cx="16" cy="12" r="3"></circle><path d="M5 20c0-3 3-5 6-5s6 2 6 5"></path></svg></span><?= $total ?> Students</span>
        <span class="pill"><span class="pill-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17h18"></path><path d="M4 15l5-5 4 3 7-7"></path></svg></span><?= $overallAvg ?>% Avg</span>
        <span class="pill"><span class="pill-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h12v3a5 5 0 01-5 5h-2a5 5 0 01-5-5V4z"></path><path d="M8 20h8"></path><path d="M10 16h4"></path></svg></span><?= isset($topper['name']) ? htmlspecialchars($topper['name']) : '—' ?></span>
      </div>
    </div>
    <div class="hero-visual">
      <div class="hero-blob"></div>
      <div class="hero-ring"></div>
      <div class="hero-card">
        <div class="hero-card-title">Class Snapshot</div>
        <div class="hero-card-sub">Top: <?= isset($topper['name']) ? htmlspecialchars($topper['name']) : '—' ?> · Avg: <?= $overallAvg ?>%</div>
      </div>
    </div>
  </section>

  <section class="metrics">
    <div class="metric-card">
      <h3>Total Students</h3>
      <p><?= $total ?></p>
    </div>
    <div class="metric-card">
      <h3>Topper</h3>
      <p><?= $topper['name'] ?></p>
      <span>Total: <?= $topper['total'] ?></span>
    </div>
    <div class="metric-card">
      <h3>Overall Average</h3>
      <p><?= $overallAvg ?>%</p>
    </div>
  </section>

  <!-- Quick Insights Row -->
  <section class="insights-row">
    <div class="insight-card insight-a">
      <div class="insight-icon"><svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M8 13l-2 7 6-3 6 3-2-7"></path></svg></div>
      <div class="insight-text">A Grade Students</div>
      <div class="insight-value"><?= $g['A_cnt'] ?></div>
    </div>
    <div class="insight-card insight-f">
      <div class="insight-icon"><svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></div>
      <div class="insight-text">Failed Students</div>
      <div class="insight-value"><?= $g['F_cnt'] ?></div>
    </div>
    <div class="insight-card insight-average">
      <div class="insight-icon"><svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><rect x="4" y="12" width="3" height="8" rx="1"></rect><rect x="10" y="8" width="3" height="12" rx="1"></rect><rect x="16" y="4" width="3" height="16" rx="1"></rect></svg></div>
      <div class="insight-text">Overall Class Avg</div>
      <div class="insight-value"><?= $overallAvg ?>%</div>
    </div>
    <div class="insight-card insight-highest">
      <div class="insight-icon"><svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L6 14h6l-1 8 7-12h-6l1-8z"></path></svg></div>
      <div class="insight-text">Highest Subject Avg</div>
      <div class="insight-value"><?= $highestSubject['label'] ?> – <?= $highestSubject['value'] ?>%</div>
    </div>
  </section>

  <section class="charts-grid">
    <div class="chart-card">
      <div class="chart-title"><span class="title-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
          <rect x="4" y="12" width="3" height="8" rx="1"></rect>
          <rect x="10" y="8" width="3" height="12" rx="1"></rect>
          <rect x="16" y="4" width="3" height="16" rx="1"></rect>
        </svg>
      </span> Subject Averages</div>
      <canvas id="avgChart"></canvas>
    </div>
    <div class="chart-card">
      <div class="chart-title"><span class="title-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 17h18"></path>
          <path d="M4 15l5-5 4 3 7-7"></path>
          <circle cx="9" cy="10" r="1.5"></circle>
          <circle cx="13" cy="13" r="1.5"></circle>
          <circle cx="20" cy="6" r="1.5"></circle>
        </svg>
      </span> Performance Trend</div>
      <canvas id="trendChart"></canvas>
    </div>
    <div class="chart-card chart-card--split">
      <div class="chart-title"><span class="title-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="9"></circle>
          <path d="M12 12 L12 3"></path>
          <path d="M12 12 L20.5 10"></path>
          <circle cx="12" cy="12" r="5"></circle>
        </svg>
      </span> Grade Distribution</div>
      <div class="chart-with-legend">
        <canvas id="gradeChart"></canvas>
        <div class="legend-badges">
          <div class="legend-title">Grades</div>
          <div class="legend-list">
            <span class="legend-badge badge-a">A (80+)</span>
            <span class="legend-badge badge-b">B (70–79)</span>
            <span class="legend-badge badge-c">C (60–69)</span>
            <span class="legend-badge badge-d">D (50–59)</span>
            <span class="legend-badge badge-f">F (&lt;50)</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="cta-buttons">
    <a href="add_student.php" class="btn"><span class="btn-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"></path></svg></span>Add Student</a>
    <a href="analyze.php" class="btn"><span class="btn-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17h18"></path><path d="M4 15l5-5 4 3 7-7"></path></svg></span>Analyze</a>
    <a href="search.php" class="btn"><span class="btn-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg></span>Search</a>
    <a href="?export=csv" class="btn"><span class="btn-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v10"></path><path d="M8 11l4 4 4-4"></path><path d="M4 19h16"></path></svg></span>CSV</a>
    <a href="?export=pdf" class="btn"><span class="btn-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M8 7h8M8 11h8M8 15h5"></path></svg></span>PDF</a>
    <a href="?export=excel" class="btn"><span class="btn-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M7 9h3M11 9h3M15 9h3M7 13h3M11 13h3M15 13h3"></path></svg></span>Excel</a>
  </div>

  <!-- Dynamic Summary Banner -->
  <section class="summary-banner">
    <?php if ($total > 0 && isset($topper['name'])): ?>
      <p>Currently tracking <strong><?= $total ?></strong> students. Top performer: <strong><?= htmlspecialchars($topper['name']) ?></strong> with <strong><?= $topper['total'] ?></strong> marks (<?= $topperPerc ?>%).</p>
    <?php else: ?>
      <p>Currently tracking <strong><?= $total ?></strong> students. Add students to begin insights and analytics.</p>
    <?php endif; ?>
  </section>

  <section class="data-grid">
    <div class="card-panel">
      <div class="panel-title"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h12v3a5 5 0 01-5 5h-2a5 5 0 01-5-5V4z"></path><path d="M8 20h8"></path><path d="M10 16h4"></path></svg></span> Top 5 Ranked Students</div>
      <table>
        <thead>
          <tr><th>Rank</th><th>Student</th><th>Total</th><th>%</th><th>Grade</th></tr>
        </thead>
        <tbody>
        <?php 
          // Build table rows with rank badges and initials
          $rank = 1; foreach ($topFive as $s): 
          $badgeClass = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : ''));
          $rowClass = $rank === 1 ? 'topper-row' : '';
        ?>
          <tr class="<?= $rowClass ?>">
            <td><span class="rank-badge <?= $badgeClass ?>"><?= $rank ?></span></td>
            <td><span class="profile-chip"><?= computeInitials($s['name']) ?></span> <?= htmlspecialchars($s['name']) ?></td>
            <td><?= $s['total'] ?></td>
            <td><?= $s['percentage'] ?></td>
            <td><?= $s['grade'] ?></td>
          </tr>
        <?php $rank++; endforeach; if (empty($topFive)): ?>
          <tr><td colspan="5">No data yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-panel">
      <div class="panel-title"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><circle cx="12" cy="12" r="5"></circle><circle cx="12" cy="12" r="2"></circle></svg></span> Subject Toppers</div>
      <table>
        <thead>
          <tr><th>Subject</th><th>Name</th><th>Marks</th><th>Class Avg</th></tr>
        </thead>
        <tbody>
        <?php 
          // Show who topped each subject, include class averages for context
          foreach ($subjectToppers as $t): 
          $rowClass = 'subject-row subject-' . strtolower($t['subject']);
          $avgCol = 0;
          if ($t['subject'] === 'Math') $avgCol = $avg['math'];
          elseif ($t['subject'] === 'Science') $avgCol = $avg['science'];
          elseif ($t['subject'] === 'English') $avgCol = $avg['english'];
          elseif ($t['subject'] === 'Computer') $avgCol = $avg['computer'];
        ?>
          <tr class="<?= $rowClass ?>">
            <td><span class="subject-icon"><?= $subjectIcons[$t['subject']] ?></span> <?= $t['subject'] ?></td>
            <td><?= htmlspecialchars($t['name']) ?></td>
            <td><?= $t['marks'] ?></td>
            <td><?= $avgCol ?>%</td>
          </tr>
        <?php endforeach; if (empty($subjectToppers)): ?>
          <tr><td colspan="4">No data yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Recent Additions / Activity -->
  <section class="data-grid">
    <div class="card-panel">
      <div class="panel-title"><span class="title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="3"></rect><path d="M12 8v8M8 12h8"></path></svg></span> Recent Additions</div>
      <table class="activity-table">
        <thead>
          <tr><th>Name</th><th>Total Marks</th><th>%</th><th>Grade</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recentAdditions as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['name']) ?></td>
              <td><?= $r['total'] ?></td>
              <td><?= $r['percentage'] ?>%</td>
              <td><?= $r['grade'] ?></td>
            </tr>
          <?php endforeach; if (empty($recentAdditions)): ?>
            <tr><td colspan="4">No recent additions.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <footer class="app-footer">© 2025 iFiCode Inclusive Pvt. Ltd.</footer>

  <script>
    // Register DataLabels plugin so we can show % inside the doughnut
    if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') {
      Chart.register(ChartDataLabels);
    }
    const avgCtx = document.getElementById('avgChart');
    // Build a simple bar chart to show subject averages
    new Chart(avgCtx, {
      type: 'bar',
      data: {
        labels: ['Math','Science','English','Computer'],
        datasets: [{
          label: 'Average',
          data: [<?= $avg['math'] ?>, <?= $avg['science'] ?>, <?= $avg['english'] ?>, <?= $avg['computer'] ?>],
          backgroundColor: ['#2DB3A3','#36CFC9','#4FD1C5','#6EE7D2'],
          borderRadius: 8
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true, max: 100 } },
        plugins: { legend: { display: false } }
      }
    });

    const gradeCtx = document.getElementById('gradeChart');
    // Doughnut chart for grade distribution (A–F)
    new Chart(gradeCtx, {
      type: 'doughnut',
      data: {
        labels: ['A (80+)','B (70–79)','C (60–69)','D (50–59)','F (<50)'],
        datasets: [{
          data: [<?= $g['A_cnt'] ?>, <?= $g['B_cnt'] ?>, <?= $g['C_cnt'] ?>, <?= $g['D_cnt'] ?>, <?= $g['F_cnt'] ?>],
          backgroundColor: ['#2DB3A3','#5BC0EB','#FFE66D','#F59E0B','#EF4444']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        animation: { duration: 800, easing: 'easeOutQuart' },
        plugins: {
          legend: { position: 'right', labels: { usePointStyle: true } },
          datalabels: {
            formatter: (value, ctx) => {
              const sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
              const pct = sum ? (value / sum) * 100 : 0;
              return pct.toFixed(1) + '%';
            },
            color: '#ffffff',
            font: { weight: '600' }
          }
        }
      }
    });

    const trendCtx = document.getElementById('trendChart');
    // Line chart to visualize trend across subjects
    new Chart(trendCtx, {
      type: 'line',
      data: {
        labels: ['Math','Science','English','Computer'],
        datasets: [{
          label: 'Avg Trend',
          data: [<?= $avg['math'] ?>, <?= $avg['science'] ?>, <?= $avg['english'] ?>, <?= $avg['computer'] ?>],
          borderColor: '#2DB3A3',
          backgroundColor: 'rgba(45, 179, 163, 0.15)',
          tension: 0.3,
          pointRadius: 4,
          pointBackgroundColor: '#2DB3A3',
          fill: true
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true, max: 100 } },
        plugins: { legend: { display: false } },
        animation: { duration: 900, easing: 'easeOutQuart' }
      }
    });
  </script>
</body>
</html>
