<?php
// Load DB and helper functions
include('includes/db_connect.php');
include('includes/functions.php');
include('includes/header.php');

// Read optional inputs: student id or subject filter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$subjectParam = isset($_GET['subject']) ? trim($_GET['subject']) : '';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$perPage = 8;
$totalRows = 0;
$totalPages = 1;
$studentName = '';
$recommendedSubject = '';
$recommendationExplanation = '';
$allowedSubjects = ['Math','Science','English','Computer'];

// Decide which subject to show recommendations for
$selectedSubject = '';
if ($id > 0) {
  // If an ID is provided, find the student's recommended subject using weighted scoring
  $res = $conn->query("SELECT * FROM students WHERE id={$id}");
  if ($res && $res->num_rows > 0) {
    $st = $res->fetch_assoc();
    $studentName = isset($st['name']) ? $st['name'] : '';

    // Prepare marks and weights
    $marks = [
      'Math' => (float)$st['math'],
      'Science' => (float)$st['science'],
      'English' => (float)$st['english'],
      'Computer' => (float)$st['computer']
    ];
    // Default weights; overlay with DB-configured values if available
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
    foreach ($marks as $subject => $mark) {
      $weightedScores[$subject] = $mark * $weights[$subject];
    }
    // Determine recommended subject via weighted max
    $recommendedSubject = array_keys($weightedScores, max($weightedScores))[0];

    // Human-style explanation
    $avgMark = array_sum($marks) / max(1, count($marks));
    $remark = '';
    if ($marks[$recommendedSubject] >= 85) {
      $remark = 'an exceptional strength';
    } elseif ($marks[$recommendedSubject] >= 70) {
      $remark = 'a strong interest and skill';
    } elseif ($marks[$recommendedSubject] >= 55) {
      $remark = 'good potential';
    } else {
      $remark = 'developing skill';
    }
    $recommendationExplanation = "You have shown {$remark} in <strong>{$recommendedSubject}</strong>, suggesting your aptitude aligns with careers in that field.";
  }
}

// Subject override via dropdown or direct link always takes precedence if valid
if ($subjectParam !== '') {
  $subjectParam = ucfirst(strtolower($subjectParam));
  if (in_array($subjectParam, $allowedSubjects, true)) {
    $selectedSubject = $subjectParam;
  }
}
// If no manual subject chosen, fall back to recommended
if ($selectedSubject === '' && $recommendedSubject !== '') {
  $selectedSubject = $recommendedSubject;
}

// Fetch career suggestions for the chosen subject
$careers = null;
if ($selectedSubject !== '') {
  $subjectEsc = $conn->real_escape_string($selectedSubject);
  $where = "main_subject='{$subjectEsc}'";
  if ($q !== '') {
    $qEsc = $conn->real_escape_string($q);
    $where .= " AND (career_field LIKE '%{$qEsc}%' OR description LIKE '%{$qEsc}%')";
  }
  $countSql = "SELECT COUNT(*) AS cnt FROM career_database WHERE {$where}";
  $countRes = $conn->query($countSql);
  if ($countRes && $countRes->num_rows > 0) {
    $totalRows = (int)$countRes->fetch_assoc()['cnt'];
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    if ($page > $totalPages) { $page = $totalPages; }
  }
  $offset = ($page - 1) * $perPage;
  $sql = "SELECT career_field, description FROM career_database WHERE {$where} ORDER BY career_field LIMIT {$perPage} OFFSET {$offset}";
  $careers = $conn->query($sql);
}
?>

<section class="page-wrap">
  <div class="career-hero">
    <div class="career-hero-content">
      <div class="career-eyebrow">Career Guidance</div>
      <h1 class="career-title">Personalized Career Recommendations</h1>
      <p class="career-subtitle">Discover future paths aligned with your strongest subject.</p>

      <div class="career-badges">
        <?php if ($selectedSubject !== ''): ?>
          <span class="chip chip-primary">Focus: <?php echo htmlspecialchars($selectedSubject); ?></span>
        <?php else: ?>
          <span class="chip">Choose a subject to explore careers</span>
        <?php endif; ?>
        <?php if ($studentName !== ''): ?>
          <span class="chip chip-primary">Student: <?php echo htmlspecialchars($studentName); ?></span>
        <?php endif; ?>
        <?php if ($id > 0): ?>
          <span class="chip chip-muted">Student ID: #<?php echo $id; ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="career-hero-visual">
      <div class="career-blob"></div>
      <div class="career-ring"></div>
      <div class="career-card-mini">
        <div class="mini-title">Subject Insight</div>
        <div class="mini-sub">Recommended: <?php echo $recommendedSubject ? htmlspecialchars($recommendedSubject) : '—'; ?></div>
      </div>
    </div>
  </div>

  <?php if ($recommendationExplanation !== ''): ?>
    <div class="recommendation-box">
      <div class="rec-title">Smart Recommendation</div>
      <p class="rec-text"><?php echo $recommendationExplanation; ?></p>
    </div>
  <?php endif; ?>

  <!-- Toolbar: subject dropdown and search filter -->
  <div class="career-toolbar">
    <form method="get" class="toolbar-form" action="career.php">
      <?php if ($id > 0): ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
      <?php endif; ?>
      <label>
        <span class="toolbar-label">Subject</span>
        <select name="subject" aria-label="Subject">
          <?php foreach ($allowedSubjects as $sub): ?>
            <option value="<?php echo $sub; ?>" <?php echo ($selectedSubject === $sub) ? 'selected' : ''; ?>><?php echo $sub; ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        <span class="toolbar-label">Filter</span>
        <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search careers" aria-label="Search careers">
      </label>
      <button type="submit" class="btn btn-outline">Apply</button>
    </form>
  </div>

  <?php if ($selectedSubject !== ''): ?>
    <div class="career-grid">
      <?php if ($careers && $careers->num_rows > 0): ?>
        <?php while ($c = $careers->fetch_assoc()): ?>
          <div class="career-card">
            <div class="card-head">
              <span class="career-icon">
                <?php
                  $icon = '';
                  switch ($selectedSubject) {
                    case 'Math':
                      $icon = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M8 8h8M8 12h8M8 16h5"></path></svg>';
                      break;
                    case 'Science':
                      $icon = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v4l-5 8a5 5 0 0 0 4 8h6a5 5 0 0 0 4-8l-5-8V2"></path></svg>';
                      break;
                    case 'English':
                      $icon = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h12v16H4z"></path><path d="M16 6l4 2v12h-4z"></path><path d="M6 8h8M6 12h8M6 16h6"></path></svg>';
                      break;
                    case 'Computer':
                      $icon = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="2"></rect><path d="M7 20h10"></path></svg>';
                      break;
                  }
                  echo $icon;
                ?>
              </span>
              <div class="career-field"><?php echo htmlspecialchars($c['career_field']); ?></div>
            </div>
            <p class="career-desc"><?php echo htmlspecialchars($c['description']); ?></p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="no-results">No career suggestions available for <?php echo htmlspecialchars($selectedSubject); ?>.</div>
      <?php endif; ?>
    </div>
    <?php if ($totalPages > 1): ?>
      <?php
        $params = ['subject' => $selectedSubject];
        if ($id > 0) { $params['id'] = $id; }
        if ($q !== '') { $params['q'] = $q; }
        $prevParams = $params; $prevParams['page'] = max(1, $page - 1);
        $nextParams = $params; $nextParams['page'] = min($totalPages, $page + 1);
      ?>
      <div class="pagination">
        <div class="page-count">Page <?php echo $page; ?> of <?php echo $totalPages; ?></div>
        <div class="pager-actions">
          <?php if ($page > 1): ?>
            <a class="pager-link" href="career.php?<?php echo http_build_query($prevParams); ?>">« Prev</a>
          <?php else: ?>
            <span class="pager-link disabled">« Prev</span>
          <?php endif; ?>
          <?php if ($page < $totalPages): ?>
            <a class="pager-link" href="career.php?<?php echo http_build_query($nextParams); ?>">Next »</a>
          <?php else: ?>
            <span class="pager-link disabled">Next »</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="subject-selector">
      <a class="subject-tile subject-math" href="career.php?subject=Math">
        <span class="tile-title">Math</span>
        <span class="tile-sub">Quantitative, analytical careers</span>
      </a>
      <a class="subject-tile subject-science" href="career.php?subject=Science">
        <span class="tile-title">Science</span>
        <span class="tile-sub">Research and applied sciences</span>
      </a>
      <a class="subject-tile subject-english" href="career.php?subject=English">
        <span class="tile-title">English</span>
        <span class="tile-sub">Media, writing, communication</span>
      </a>
      <a class="subject-tile subject-computer" href="career.php?subject=Computer">
        <span class="tile-title">Computer</span>
        <span class="tile-sub">Technology and software</span>
      </a>
    </div>
    <div class="tip-banner">Tip: Open <code>career.php?id=&lt;student_id&gt;</code> to see personalized suggestions.</div>
  <?php endif; ?>
</section>

<?php include('includes/footer.php'); ?>