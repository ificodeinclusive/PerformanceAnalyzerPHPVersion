CREATE DATABASE IF NOT EXISTS student_performance;
USE student_performance;


CREATE TABLE IF NOT EXISTS admin (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  department VARCHAR(100),
  email VARCHAR(100),
  phone_number VARCHAR(15),
  address VARCHAR(100),
  role VARCHAR(100),
  username VARCHAR(100),
  password VARCHAR(100)
);

INSERT INTO admin (name, department, email, phone_number, address, role, username, password) VALUES
('Punam Thapa', 'Information Technology', 'punam.thapa@ificode.com', '9800000000', 'Kathmandu', 'admin', 'punam', '123456'),
('Sobisha Agrawal', 'Information Technology', 'sobisha.agrawal@ificode.com', '9800000000', 'Kathmandu', 'admin', 'sobisha', '123456');
