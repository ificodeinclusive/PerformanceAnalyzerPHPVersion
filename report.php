<?php
// Load database and helper functions for calculations and formatting
include('includes/db_connect.php');
include('includes/functions.php');

// Read which student to show and whether to export
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$format = isset($_GET['format']) ? strtolower($_GET['format']) : '';

// Fetch the student record; if missing, return 404
$res = $conn->query("SELECT * FROM students WHERE id={$id}");
if (!$res || $res->num_rows === 0) {
  http_response_code(404);
  echo "Student not found";
  exit;
}
$s = $res->fetch_assoc();
// Calculate totals and grade to show on the report
$marks = [$s['math'], $s['science'], $s['english'], $s['computer']];
$total = calculateTotal($marks);
$percentage = calculatePercentage($marks);
$grade = assignGrade($percentage);

// Smart Career Recommendation: weighted dominant subject and explanation
$recommendedSubject = '';
$recommendationExplanation = '';
$marksAssoc = [
  'Math' => (float)$s['math'],
  'Science' => (float)$s['science'],
  'English' => (float)$s['english'],
  'Computer' => (float)$s['computer']
];
$weights = [
  'Math' => 0.25,
  'Science' => 0.30,
  'English' => 0.20,
  'Computer' => 0.25
];
$wRes = $conn->query("SELECT subject_name, weight FROM subject_weights");
if ($wRes && $wRes->num_rows > 0) {
  while ($row = $wRes->fetch_assoc()) {
    $sub = isset($row['subject_name']) ? $row['subject_name'] : '';
    $val = isset($row['weight']) ? (float)$row['weight'] : null;
    if ($sub !== '' && $val !== null) {
      $weights[$sub] = $val;
    }
  }
}
$weightedScores = [];
foreach ($marksAssoc as $subject => $mark) {
  $weightedScores[$subject] = $mark * $weights[$subject];
}
if (!empty($weightedScores)) {
  $recommendedSubject = array_keys($weightedScores, max($weightedScores))[0];
  $rsMark = $marksAssoc[$recommendedSubject];
  if ($rsMark >= 85) {
    $remark = 'an exceptional strength';
  } elseif ($rsMark >= 70) {
    $remark = 'a strong interest and skill';
  } elseif ($rsMark >= 55) {
    $remark = 'good potential';
  } else {
    $remark = 'developing skill';
  }
  $recommendationExplanation = "You have shown {$remark} in <strong>{$recommendedSubject}</strong>, suggesting your aptitude aligns with careers in that field.";
}

// Smart fallback: if weighted scores are close (within 5), suggest the next subject
$smartSuggested = null;
if (!empty($weightedScores)) {
  $sorted = $weightedScores; arsort($sorted);
  $subjectsSorted = array_keys($sorted);
  if (count($subjectsSorted) > 1) {
    $diff = abs($sorted[$subjectsSorted[0]] - $sorted[$subjectsSorted[1]]);
    if ($diff <= 5) { $smartSuggested = $subjectsSorted[1]; }
  }
}
// Final subject used for career query
$subjectToUse = $smartSuggested ?? $recommendedSubject;

// Determine the student's strongest subject by marks (for context),
// but query careers using the final subject selection
$markTopSubject = dominantSubject($s['math'], $s['science'], $s['english'], $s['computer']);
$subjectToUse = $subjectToUse ?: $markTopSubject;
$finalSubjectEsc = $conn->real_escape_string($subjectToUse);
$careerRes = $conn->query("SELECT DISTINCT career_field, description FROM career_database WHERE main_subject='{$finalSubjectEsc}' ORDER BY career_field LIMIT 5");

if ($format === 'xls') {
  // Send a simple spreadsheet-friendly HTML table as an Excel file
  header('Content-Type: application/vnd.ms-excel');
  header('Content-Disposition: attachment; filename="report_card_'.preg_replace('/[^A-Za-z0-9_-]/','_', $s['name']).'.xls"');
  $issued = date('Y-m-d');
  // Choose a short remark based on percentage threshold
  $remark = $percentage >= 80 ? 'Excellent Performance' : ($percentage >= 60 ? 'Good Performance' : ($percentage >= 40 ? 'Average Performance' : 'Needs Improvement'));
  echo "<table border='1' cellspacing='0' cellpadding='6'>";
  echo "<tr><th colspan='4' style='font-size:16px;'>Academic Report Card</th></tr>";
  echo "<tr><td><strong>Name</strong></td><td>".htmlspecialchars($s['name'])."</td><td><strong>ID</strong></td><td>".$s['id']."</td></tr>";
  echo "<tr><td><strong>Issued On</strong></td><td colspan='3'>".$issued."</td></tr>";
  echo "<tr><th>Subject</th><th>Marks</th><th></th><th></th></tr>";
  echo "<tr><td>Math</td><td>{$s['math']}</td><td></td><td></td></tr>";
  echo "<tr><td>Science</td><td>{$s['science']}</td><td></td><td></td></tr>";
  echo "<tr><td>English</td><td>{$s['english']}</td><td></td><td></td></tr>";
  echo "<tr><td>Computer</td><td>{$s['computer']}</td><td></td><td></td></tr>";
  echo "<tr><td><strong>Total</strong></td><td><strong>{$total}</strong></td><td><strong>Percentage</strong></td><td><strong>{$percentage}%</strong></td></tr>";
  echo "<tr><td><strong>Grade</strong></td><td><strong>{$grade}</strong></td><td><strong>Remarks</strong></td><td><strong>{$remark}</strong></td></tr>";
  // Include dominant subject and career recommendations for the spreadsheet view
  echo "<tr><td><strong>Top Subject</strong></td><td colspan='3'><strong>".htmlspecialchars($recommendedSubject)."</strong></td></tr>";
  if ($careerRes && $careerRes->num_rows > 0) {
    echo "<tr><th colspan='4'>Career Recommendations</th></tr>";
    while($c = $careerRes->fetch_assoc()) {
      echo "<tr><td colspan='1'>".htmlspecialchars($c['career_field'])."</td><td colspan='3'>".htmlspecialchars($c['description'])."</td></tr>";
    }
  }
  echo "<tr><td colspan='2' style='padding-top:20px;'><em>Class Teacher</em></td><td colspan='2' style='padding-top:20px;'><em>Principal</em></td></tr>";
  echo "</table>";
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Report · <?= htmlspecialchars($s['name']) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/analyze.css">
  <link rel="stylesheet" href="assets/css/report.css">
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
    <div class="report-header">
      <h1 class="report-title"><span class="report-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h12v3a5 5 0 01-5 5h-2a5 5 0 01-5-5V4z"></path><path d="M8 20h8"></path><path d="M10 16h4"></path></svg></span> Academic Report Card</h1>
      <div class="student-meta">
        <div class="meta-item"><div class="meta-label">Student Name</div><div class="meta-value"><?= htmlspecialchars($s['name']) ?></div></div>
        <div class="meta-item"><div class="meta-label">Student ID</div><div class="meta-value">#<?= $s['id'] ?></div></div>
        <div class="meta-item"><div class="meta-label">Issued On</div><div class="meta-value"><?= date('Y-m-d') ?></div></div>
      </div>
    </div>

    <?php if ($recommendationExplanation !== ''): ?>
      <div class="recommendation-box">
        <div class="rec-title">Smart Recommendation</div>
        <p class="rec-text"><?= $recommendationExplanation ?></p>
      </div>
    <?php endif; ?>

    <div class="report-card">
      <table class="subjects-table">
        <thead><tr><th>Subject</th><th>Marks</th></tr></thead>
        <tbody>
          <tr><td>Math</td><td><?= $s['math'] ?></td></tr>
          <tr><td>Science</td><td><?= $s['science'] ?></td></tr>
          <tr><td>English</td><td><?= $s['english'] ?></td></tr>
          <tr><td>Computer</td><td><?= $s['computer'] ?></td></tr>
        </tbody>
      </table>

      <div class="summary-grid">
        <div class="summary-item"><div class="label">Total</div><div class="value"><?= $total ?></div></div>
        <div class="summary-item"><div class="label">Percentage</div><div class="value"><?= $percentage ?>%</div></div>
        <div class="summary-item"><div class="label">Grade</div><div class="value"><?= $grade ?></div></div>
        <?php 
          // Pick a remark and style class based on percentage
          $remarkClass = $percentage >= 80 ? 'remark-good' : ($percentage >= 60 ? 'remark-good' : ($percentage >= 40 ? 'remark-warn' : 'remark-bad'));
          $remarkText = $percentage >= 80 ? 'Excellent Performance' : ($percentage >= 60 ? 'Good Performance' : ($percentage >= 40 ? 'Average Performance' : 'Needs Improvement'));
        ?>
        <div class="summary-item"><div class="label">Remarks</div><div class="value <?= $remarkClass ?>"><?= $remarkText ?></div></div>
      </div>

      <!-- Career recommendations based on strongest subject -->
      <div class="career-section">
        <h2 class="career-title">Career Recommendations</h2>
        <p class="career-sub">Based on strongest subject: <strong><?= htmlspecialchars($subjectToUse ?: $markTopSubject) ?></strong><?php if ($smartSuggested): ?> · Smart suggested: <strong><?= htmlspecialchars($smartSuggested) ?></strong><?php endif; ?></p>
        <?php if ($careerRes && $careerRes->num_rows > 0): ?>
          <ul class="career-list">
            <?php $seen = []; while($c = $careerRes->fetch_assoc()): ?>
              <?php if (in_array($c['career_field'], $seen)) { continue; } $seen[] = $c['career_field']; ?>
              <li><strong><?= htmlspecialchars($c['career_field']) ?></strong> — <?= htmlspecialchars($c['description']) ?></li>
            <?php endwhile; ?>
          </ul>
          <p><em>These recommendations are generated using weighted analysis and similarity-based logic to match your strongest academic area with the most relevant career paths.</em></p>
        <?php else: ?>
          <p>No career suggestions available for <?= htmlspecialchars($subjectToUse) ?>.</p>
        <?php endif; ?>
      </div>

      <div class="signature-row">
        <!-- Placeholder signature boxes for printing -->
        <div class="signature-box">Class Teacher</div>
        <div class="signature-box">Principal</div>
      </div>

      <div class="report-actions">
        <div class="toolbar-left">Prepared by Smart Student Analyzer</div>
        <div class="toolbar-right">
          <a href="report.php?id=<?= $s['id'] ?>&format=xls" class="btn">Download Excel</a>
          <button class="btn btn-outline" onclick="window.print()">Print as PDF</button>
        </div>
      </div>
    </div>
  </section>

  <footer class="app-footer">© 2025 iFiCode Inclusive Pvt. Ltd.</footer>
</body>
</html>