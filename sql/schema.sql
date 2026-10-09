-- Training Enrollment System: schema + sample data
CREATE DATABASE IF NOT EXISTS training_db CHARACTER SET utf8mb4;
USE training_db;

DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS students;

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(100),
    phone      VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE courses (
    course_id   INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

CREATE TABLE classes (
    class_id   INT AUTO_INCREMENT PRIMARY KEY,
    course_id  INT NOT NULL,
    class_code VARCHAR(20) NOT NULL,
    schedule   VARCHAR(100),
    instructor VARCHAR(100),
    slots      INT NOT NULL DEFAULT 0,   -- REMAINING slots
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
) ENGINE=InnoDB;

CREATE TABLE enrollments (
    enrollment_id   INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT NOT NULL,
    class_id        INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status          VARCHAR(20) DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (class_id)   REFERENCES classes(class_id)
) ENGINE=InnoDB;

-- Sample data (slots already reflect the enrollments below)
INSERT INTO courses (course_code, course_name, description) VALUES
 ('WEB101', 'Web Development Fundamentals', 'HTML, CSS, JavaScript and PHP basics.'),
 ('DB201',  'Database Design with MySQL',   'Normalization, SQL joins and transactions.'),
 ('NET110', 'Networking Essentials',        'LAN/WAN concepts and basic configuration.');

INSERT INTO classes (course_id, class_code, schedule, instructor, slots) VALUES
 (1, 'WEB101-A', 'Mon/Wed 9:00-11:00 AM',  'Mr. Santos',  5),
 (1, 'WEB101-B', 'Tue/Thu 1:00-3:00 PM',   'Ms. Reyes',   3),
 (2, 'DB201-A',  'Sat 8:00 AM-12:00 NN',   'Mr. Cruz',    2),
 (3, 'NET110-A', 'Fri 1:00-5:00 PM',       'Ms. Garcia',  0);  -- FULL: use for the rollback screenshot

INSERT INTO students (full_name, email, phone) VALUES
 ('Ana Dela Cruz', 'ana@example.com',   '0917-111-2222'),
 ('Ben Ramos',     'ben@example.com',   '0918-333-4444'),
 ('Carla Lim',     'carla@example.com', '0919-555-6666'),
 ('Dino Torres',   'dino@example.com',  '0920-777-8888');

INSERT INTO enrollments (student_id, class_id, status) VALUES
 (1, 1, 'active'),
 (2, 3, 'active'),
 (3, 4, 'active');
