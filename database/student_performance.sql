CREATE DATABASE IF NOT EXISTS student_performance;
USE student_performance;


CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  math INT,
  science INT,
  english INT,
  computer INT
);

INSERT INTO students (name, math, science, english, computer) VALUES
('Aviral Adhikari', 87, 90, 88, 95),
('Sobisha Agrawal', 84, 87, 85, 98),
('Pearl Shrestha', 78, 82, 75, 80),
('Aakash Gautam', 59, 68, 75, 81),
('Punam Magar', 92, 91, 96, 93), 
('Reshma Basnet', 41, 32, 56, 30),
('Aviral Adhikari', 30, 30, 30, 30),
('Aawan Shrestha', 30, 30, 88, 30),
('Kritika Rai', 72, 79, 84, 77),
('Bibek Thapa', 89, 84, 82, 87),
('Ritesh Karki', 65, 71, 69, 75),
('Sandhya Gurung', 88, 90, 91, 92),
('Milan Tamang', 54, 59, 61, 68),
('Priyanka Shakya', 91, 90, 94, 89),
('Sujan Shrestha', 76, 81, 78, 84),
('Ayushman Khadka', 68, 72, 74, 79),
('Nikita Lama', 83, 87, 89, 86),
('Prakash Thapa', 58, 64, 70, 66),
('Manish Adhikari', 89, 88, 90, 91),
('Sneha Sharma', 80, 85, 88, 85);
