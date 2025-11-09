<?php
  $isAdmin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
  $basePath = $isAdmin ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Student Performance Analyzer</title>
<link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
<?php if (strpos($_SERVER['PHP_SELF'], 'career.php') !== false): ?>
<link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/career.css">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
</head>
<body>
<header>
  <div class="navbar">
    <h1>🎓 Smart Student Performance Analyzer</h1>
    <nav>
      <a href="<?php echo $basePath; ?>index.php">Home</a>
      <a href="<?php echo $basePath; ?>add_student.php">Add Student</a>
      <a href="<?php echo $basePath; ?>analyze.php">Analyze</a>
      <a href="<?php echo $basePath; ?>search.php">Search</a>
      <a href="<?php echo $basePath; ?>career.php">Career</a>
      <a href="<?php echo $basePath; ?>admin/dashboard.php">Admin</a>
      <a href="<?php echo $basePath; ?>logout.php">Logout</a>
    </nav>
  </div>
</header>