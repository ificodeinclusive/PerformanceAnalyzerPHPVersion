-- Subject weights configuration table
-- Import this into your MySQL database to enable DB-driven weights

CREATE TABLE IF NOT EXISTS `subject_weights` (
  `subject_name` VARCHAR(50) NOT NULL PRIMARY KEY,
  `weight` DECIMAL(4,2) NOT NULL
);

INSERT INTO `subject_weights` (`subject_name`, `weight`) VALUES
  ('Math', 0.25),
  ('Science', 0.30),
  ('English', 0.20),
  ('Computer', 0.25);