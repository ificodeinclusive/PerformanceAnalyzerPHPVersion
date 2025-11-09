-- Career Recommendations Table and Seeds
-- Import this file into the same database that contains your `students` table.
-- If needed, uncomment the next two lines and replace DB name.
-- CREATE DATABASE IF NOT EXISTS student_performance;
-- USE student_performance;

-- Schema: maps strongest subject to suggested careers
CREATE TABLE IF NOT EXISTS career_database (
  career_id INT AUTO_INCREMENT PRIMARY KEY,
  main_subject VARCHAR(50) NOT NULL,
  career_field VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  INDEX idx_main_subject (main_subject)
);

-- Seed data: same as bundled in student_performance.sql
INSERT INTO career_database (main_subject, career_field, description) VALUES
('Math', 'Data Science', 'Ideal for analytical thinkers with strong mathematical skills.'),
('Math', 'Engineering', 'Suitable for students excelling in problem-solving and technical subjects.'),
('Science', 'Medical', 'Recommended for students strong in science and biology.'),
('Science', 'Environmental Science', 'Focuses on nature, research, and sustainability.'),
('Computer', 'Software Development', 'For logical and programming-oriented students.'),
('Computer', 'Cybersecurity', 'Focused on protecting digital systems and networks.'),
('English', 'Communication & Media', 'For creative minds good in writing, speaking, or media.'),
('English', 'Teaching / Literature', 'Ideal for linguistically strong and expressive students.');

-- Additional career paths
INSERT INTO career_database (main_subject, career_field, description) VALUES 
('Math', 'Accounting & Finance', 'Ideal for students interested in numbers, business, and financial management.'), 
('Math', 'Architecture', 'Combines math and creativity to design buildings and structures.'), 
('Math', 'Statistics & Actuarial Science', 'For those who enjoy analyzing data and calculating risk.'), 
('Science', 'Pharmacy', 'Focuses on medicines, their effects, and patient care.'), 
('Science', 'Biotechnology', 'For innovative students interested in genetics and research.'), 
('Science', 'Veterinary Science', 'Perfect for students who love animals and biological sciences.'), 
('Science', 'Forensic Science', 'Applies scientific principles to crime investigation and evidence analysis.'), 
('Computer', 'Artificial Intelligence & Machine Learning', 'For students interested in intelligent systems and automation.'), 
('Computer', 'Web Development', 'Ideal for creative students who enjoy designing and building websites.'), 
('Computer', 'Game Development', 'Perfect for those passionate about programming and gaming design.'), 
('Computer', 'Data Analytics', 'Focuses on analyzing data to support business decisions.'), 
('Computer', 'IT Management', 'Combines technical skills with organizational leadership.'), 
('English', 'Public Relations', 'For students skilled in communication, brand image, and media relations.'), 
('English', 'Content Writing & Copywriting', 'Ideal for creative thinkers who love writing persuasive or informative content.'), 
('English', 'Journalism', 'For those passionate about reporting, writing, and storytelling.'), 
('English', 'Law', 'Requires strong reasoning, reading, and speaking skills.'), 
('Math', 'Economics', 'For students who like analyzing markets, trends, and data.'), 
('Science', 'Nutrition & Dietetics', 'Focuses on health, food, and human biology.'), 
('Computer', 'Cloud Computing', 'For tech-savvy students interested in managing online data systems.'), 
('Science', 'Astronomy', 'Perfect for curious minds fascinated by space and physics.');