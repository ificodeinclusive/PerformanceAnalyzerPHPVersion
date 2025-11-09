<?php // Load DB and helper functions used for calculations and sorting
include('includes/db_connect.php'); include('includes/functions.php'); ?>
<?php
// Get all students from the database
$result = $conn->query("SELECT * FROM students");
$students = [];
while($row = $result->fetch_assoc()) {
  // Calculate total marks, percentage, and grade for this student
  $marks = [$row['math'], $row['science'], $row['english'], $row['computer']];
  $total = calculateTotal($marks);
  $percentage = calculatePercentage($marks);
  $grade = assignGrade($percentage);
  $row['total'] = $total;
  $row['percentage'] = $percentage;
  $row['grade'] = $grade;
  $students[] = $row;
}
// Sort students by performance for ranking and pick the topper
$sorted = sortStudents($students);
$topper = !empty($sorted) ? findTopper($sorted) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analyze · Smart Student Analyzer</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/analyze.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
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
      <h1 class="page-title"><span class="page-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="12" width="3" height="8" rx="1"></rect><rect x="10" y="8" width="3" height="12" rx="1"></rect><rect x="16" y="4" width="3" height="16" rx="1"></rect></svg></span> Performance Analysis</h1>
      <?php if ($topper): ?>
        <p class="page-sub">Topper: <strong><?= htmlspecialchars($topper['name']) ?></strong> with <strong><?= $topper['total'] ?></strong> marks.</p>
      <?php else: ?>
        <p class="page-sub">No students yet. Add students to begin analysis.</p>
      <?php endif; ?>
    </div>

    <?php
      // Aggregated metrics for charts (same logic as index.php)
      $avg = $conn->query("SELECT ROUND(AVG(math),2) AS math, ROUND(AVG(science),2) AS science, ROUND(AVG(english),2) AS english, ROUND(AVG(computer),2) AS computer FROM students")->fetch_assoc();
      $g = $conn->query("SELECT
        SUM(CASE WHEN ((math+science+english+computer)/4) >= 80 THEN 1 ELSE 0 END) AS A_cnt,
        SUM(CASE WHEN ((math+science+english+computer)/4) BETWEEN 70 AND 79.999 THEN 1 ELSE 0 END) AS B_cnt,
        SUM(CASE WHEN ((math+science+english+computer)/4) BETWEEN 60 AND 69.999 THEN 1 ELSE 0 END) AS C_cnt,
        SUM(CASE WHEN ((math+science+english+computer)/4) BETWEEN 50 AND 59.999 THEN 1 ELSE 0 END) AS D_cnt,
        SUM(CASE WHEN ((math+science+english+computer)/4) < 50 THEN 1 ELSE 0 END) AS F_cnt
      FROM students")->fetch_assoc();
    ?>

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
      <div class="chart-card chart-card--split">
        <div class="chart-title"><span class="title-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="14" rx="2"></rect><path d="M7 8h6M7 12h10"></path>
          </svg>
        </span> Student Subject Breakdown</div>
        <div class="student-controls" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
          <label for="studentSelect" style="font-weight:600;color:#1f3c35;">Select Student</label>
          <select id="studentSelect" style="padding:8px 10px;border:1px solid #e7f3f0;border-radius:10px;background:#f9fefe;">
            <?php foreach($sorted as $s): $optId = isset($s['id']) ? $s['id'] : (isset($s['student_id']) ? $s['student_id'] : (isset($s['sid']) ? $s['sid'] : (isset($s['roll']) ? $s['roll'] : $s['name']))); ?>
              <option value="<?= htmlspecialchars($optId) ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="student-charts" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-start;">
          <div style="flex:1;min-width:280px;">
            <canvas id="studentBarChart"></canvas>
          </div>
          <div style="flex:1;min-width:280px;">
            <canvas id="studentPieChart"></canvas>
          </div>
        </div>
      </div>
    </section>

    <div class="analysis-card">
      <div class="analysis-toolbar">
        <div class="toolbar-left">Class Rankings</div>
        <div class="toolbar-right">
          <a href="index.php" class="btn btn-outline">Back to Dashboard</a>
        </div>
      </div>
      <table class="analysis-table">
        <thead>
          <tr>
            <th>Rank</th>
            <th>Name</th>
            <th>Math</th>
            <th>Science</th>
            <th>English</th>
            <th>Computer</th>
            <th>Total</th>
            <th>%</th>
            <th>Grade</th>
            <th>Download</th>
          </tr>
        </thead>
        <tbody>
          <?php $rank = 1; foreach($sorted as $s): ?>
            <tr>
              <td><?= $rank ?></td>
              <td><?= htmlspecialchars($s['name']) ?></td>
              <td><?= $s['math'] ?></td>
              <td><?= $s['science'] ?></td>
              <td><?= $s['english'] ?></td>
              <td><?= $s['computer'] ?></td>
              <td><?= $s['total'] ?></td>
              <td><?= $s['percentage'] ?></td>
          <td>
            <?php
              // Decide badge color class based on the grade
              $gradeClass = strtolower($s['grade']);
              $gradeClass = $gradeClass === 'a' ? 'grade-a' : ($gradeClass === 'b' ? 'grade-b' : ($gradeClass === 'c' ? 'grade-c' : 'grade-f'));
            ?>
            <span class="grade-badge <?= $gradeClass ?>"><?= $s['grade'] ?></span>
          </td>
              <td>
                <div class="download-actions">
                  <a class="action-link" href="report.php?id=<?= $s['id'] ?>&format=xls" title="Download Excel" aria-label="Download Excel">
                    <span class="action-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="M8 8h8M8 12h8M8 16h5"></path></svg></span> XLS
                  </a>
                  <a class="action-link" href="report.php?id=<?= $s['id'] ?>&format=pdf" target="_blank" title="Download PDF" aria-label="Download PDF">
                    <span class="action-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M9 7h6M9 11h6M9 15h4"></path></svg></span> PDF
                  </a>
                </div>
              </td>
            </tr>
          <?php $rank++; endforeach; if (empty($sorted)): ?>
            <tr><td colspan="10">No data yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <footer class="app-footer">© 2025 iFiCode Inclusive Pvt. Ltd.</footer>

  <script>
    // Register DataLabels plugin when available
    if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') {
      Chart.register(ChartDataLabels);
    }

    // Subject Averages — Bar Chart
    const avgCtx = document.getElementById('avgChart');
    if (avgCtx) {
      new Chart(avgCtx, {
        type: 'bar',
        data: {
          labels: ['Math','Science','English','Computer'],
          datasets: [{
            label: 'Average',
            data: [<?= $avg['math'] ?? 0 ?>, <?= $avg['science'] ?? 0 ?>, <?= $avg['english'] ?? 0 ?>, <?= $avg['computer'] ?? 0 ?>],
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
    }

    // Performance Trend — Line Chart
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
      new Chart(trendCtx, {
        type: 'line',
        data: {
          labels: ['Math','Science','English','Computer'],
          datasets: [{
            label: 'Avg Trend',
            data: [<?= $avg['math'] ?? 0 ?>, <?= $avg['science'] ?? 0 ?>, <?= $avg['english'] ?? 0 ?>, <?= $avg['computer'] ?? 0 ?>],
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
    }

    // Grade Distribution — Doughnut Chart
    const gradeCtx = document.getElementById('gradeChart');
    if (gradeCtx) {
      new Chart(gradeCtx, {
        type: 'doughnut',
        data: {
          labels: ['A (80+)','B (70–79)','C (60–69)','D (50–59)','F (<50)'],
          datasets: [{
            data: [<?= $g['A_cnt'] ?? 0 ?>, <?= $g['B_cnt'] ?? 0 ?>, <?= $g['C_cnt'] ?? 0 ?>, <?= $g['D_cnt'] ?? 0 ?>, <?= $g['F_cnt'] ?? 0 ?>],
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
    }

    // Student Subject Breakdown — Interactive Bar & Pie Charts
    const studentSelect = document.getElementById('studentSelect');
    const sbCtx = document.getElementById('studentBarChart');
    const spCtx = document.getElementById('studentPieChart');
    const studentData = <?= json_encode(array_map(function($s){
      $id = $s['id'] ?? ($s['student_id'] ?? ($s['sid'] ?? ($s['roll'] ?? $s['name'])));
      return [
        'id' => $id,
        'name' => $s['name'],
        'math' => (float)($s['math'] ?? 0),
        'science' => (float)($s['science'] ?? 0),
        'english' => (float)($s['english'] ?? 0),
        'computer' => (float)($s['computer'] ?? 0)
      ];
    }, $sorted), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

    function getStudentById(id) {
      return studentData.find(function(s){ return String(s.id) === String(id); }) || null;
    }
    const subLabels = ['Math','Science','English','Computer'];
    function marksArray(s) { return [s.math, s.science, s.english, s.computer]; }

    let studentBar = null;
    let studentPie = null;
    function renderStudentCharts(stu) {
      if (!stu || !sbCtx || !spCtx) return;
      const marks = marksArray(stu);
      if (!studentBar) {
        studentBar = new Chart(sbCtx, {
          type: 'bar',
          data: { labels: subLabels, datasets: [{ label: stu.name + ' — Marks', data: marks, backgroundColor: ['#2DB3A3','#36CFC9','#4FD1C5','#6EE7D2'], borderRadius: 8 }] },
          options: { responsive: true, scales: { y: { beginAtZero: true, max: 100 } }, plugins: { legend: { display: false } } }
        });
      } else {
        studentBar.data.datasets[0].label = stu.name + ' — Marks';
        studentBar.data.datasets[0].data = marks;
        studentBar.update();
      }

      if (!studentPie) {
        studentPie = new Chart(spCtx, {
          type: 'pie',
          data: { labels: subLabels, datasets: [{ data: marks, backgroundColor: ['#2DB3A3','#5BC0EB','#FFE66D','#F59E0B'] }] },
          options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
      } else {
        studentPie.data.datasets[0].data = marks;
        studentPie.update();
      }
    }

    // Initialize with first student (or selected value)
    (function initStudentCharts(){
      const initialId = studentSelect && studentSelect.value ? studentSelect.value : (studentData.length ? studentData[0].id : null);
      const initialStu = initialId ? getStudentById(initialId) : null;
      if (initialStu) renderStudentCharts(initialStu);
    })();

    if (studentSelect) {
      studentSelect.addEventListener('change', function(){
        const stu = getStudentById(this.value);
        if (stu) renderStudentCharts(stu);
      });
    }
  </script>
  </body>
  </html>
