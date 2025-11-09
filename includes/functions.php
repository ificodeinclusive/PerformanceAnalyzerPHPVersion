<?php
// 🔹 Calculate total & percentage
function calculateTotal($marks) {
  return array_sum($marks);
}
function calculatePercentage($marks) {
  return round(array_sum($marks) / count($marks), 2);
}

// 🔹 Assign grade
function assignGrade($percentage) {
  if ($percentage >= 80) return "A";
  if ($percentage >= 60) return "B";
  if ($percentage >= 45) return "C";
  return "F";
}

// 🔹 Sorting: highest total first
function sortStudents($students) {
  usort($students, function($a, $b){
    return $b['total'] <=> $a['total'];
  });
  return $students;
}

// 🔹 Search student by name or ID
function searchStudent($conn, $keyword) {
  $keyword = $conn->real_escape_string($keyword);
  $sql = "SELECT * FROM students WHERE id='$keyword' OR name LIKE '%$keyword%'";
  return $conn->query($sql);
}

// 🔹 Find topper (highest total)
function findTopper($students) {
  $topper = $students[0];
  foreach($students as $st) {
    if ($st['total'] > $topper['total']) $topper = $st;
  }
  return $topper;
}

// 🔹 Dominant subject by highest marks (Math/Science/English/Computer)
// Returns the subject name with the highest score
function dominantSubject($math, $science, $english, $computer) {
  $subjects = [
    'Math' => (int)$math,
    'Science' => (int)$science,
    'English' => (int)$english,
    'Computer' => (int)$computer,
  ];
  $top = null; $best = -1;
  foreach ($subjects as $name => $score) {
    if ($score > $best) { $best = $score; $top = $name; }
  }
  return $top ?? 'Math';
}
?>
