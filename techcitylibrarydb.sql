CREATE DATABASE techcitylibrarydb;

CREATE TABLE Category (category_id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(100) NOT NULL);

CREATE TABLE Author (author_id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(200) NOT NULL, biography TEXT, nationality VARCHAR(100), birth_date DATE, primary_genre VARCHAR(100));

CREATE TABLE Book (isbn VARCHAR(13) PRIMARY KEY, title VARCHAR(500) NOT NULL, publication_year INT, available_copies INT DEFAULT 0, status VARCHAR(50) DEFAULT 'Available', category_id INT, FOREIGN KEY (category_id) REFERENCES Category(category_id));

CREATE TABLE Book_Author (book_isbn VARCHAR(13), author_id INT, PRIMARY KEY (book_isbn, author_id), FOREIGN KEY (book_isbn) REFERENCES Book(isbn), FOREIGN KEY (author_id) REFERENCES Author(author_id));

CREATE TABLE Library_Branch (branch_id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(200) NOT NULL, location VARCHAR(500), contact_number VARCHAR(20));

CREATE TABLE Branch_Inventory (branch_id INT, book_isbn VARCHAR(13), copies INT DEFAULT 0, PRIMARY KEY (branch_id, book_isbn), FOREIGN KEY (branch_id) REFERENCES Library_Branch(branch_id), FOREIGN KEY (book_isbn) REFERENCES Book(isbn));

CREATE TABLE Member (member_id INT PRIMARY KEY AUTO_INCREMENT, member_type VARCHAR(20) NOT NULL, full_name VARCHAR(200) NOT NULL, email VARCHAR(200) UNIQUE NOT NULL, phone_number VARCHAR(20), membership_end_date DATE, total_borrowed_books INT DEFAULT 0);

CREATE TABLE Student_Member (member_id INT PRIMARY KEY, student_id VARCHAR(50), FOREIGN KEY (member_id) REFERENCES Member(member_id));

CREATE TABLE Faculty_Member (member_id INT PRIMARY KEY, employee_id VARCHAR(50), department VARCHAR(200), FOREIGN KEY (member_id) REFERENCES Member(member_id));

CREATE TABLE Borrow_Transaction (transaction_id INT PRIMARY KEY AUTO_INCREMENT, member_id INT, book_isbn VARCHAR(13), branch_id INT, borrow_date DATE, due_date DATE, return_date DATE, late_fee DECIMAL(10,2) DEFAULT 0, FOREIGN KEY (member_id) REFERENCES Member(member_id), FOREIGN KEY (book_isbn) REFERENCES Book(isbn), FOREIGN KEY (branch_id) REFERENCES Library_Branch(branch_id));

CREATE TABLE Reservation (reservation_id INT PRIMARY KEY AUTO_INCREMENT, member_id INT, book_isbn VARCHAR(13), branch_id INT, reservation_date DATE, expiry_date DATE, status VARCHAR(50) DEFAULT 'PENDING', FOREIGN KEY (member_id) REFERENCES Member(member_id), FOREIGN KEY (book_isbn) REFERENCES Book(isbn), FOREIGN KEY (branch_id) REFERENCES Library_Branch(branch_id));

CREATE TABLE Payment (payment_id INT PRIMARY KEY AUTO_INCREMENT, member_id INT, amount DECIMAL(10,2), payment_date DATE, payment_method VARCHAR(50), FOREIGN KEY (member_id) REFERENCES Member(member_id));




INSERT INTO Category (name) VALUES 
('Computer Science'), ('Literature'), ('Science');

INSERT INTO Author (name, nationality, birth_date, primary_genre) VALUES
('Robert Martin', 'American', '1952-12-05', 'Computer Science'),
('J.K. Rowling', 'British', '1965-07-31', 'Literature');

INSERT INTO Book (isbn, title, publication_year, available_copies, category_id) VALUES
('9780132350884', 'Clean Code', 2008, 5, 1),
('9780747532699', 'Harry Potter', 1997, 8, 2);

INSERT INTO Book_Author (book_isbn, author_id) VALUES
('9780132350884', 1),
('9780747532699', 2);

INSERT INTO Library_Branch (name, location, contact_number) VALUES
('Main Campus', '123 University Ave', '555-0101'),
('Engineering', '456 Tech Building', '555-0102');

INSERT INTO Branch_Inventory (branch_id, book_isbn, copies) VALUES
(1, '9780132350884', 3),
(1, '9780747532699', 5),
(2, '9780132350884', 2);

INSERT INTO Member (member_type, full_name, email, membership_end_date) VALUES
('STUDENT', 'Alice Johnson', 'alice@techcity.edu', '2025-12-31'),
('FACULTY', 'Dr. Carol Williams', 'carol@techcity.edu', '2027-01-01');

INSERT INTO Student_Member (member_id, student_id) VALUES (1, 'STU001');
INSERT INTO Faculty_Member (member_id, employee_id, department) VALUES (2, 'FAC001', 'CS');

INSERT INTO Borrow_Transaction (member_id, book_isbn, branch_id, borrow_date, due_date) VALUES
(1, '9780132350884', 1, '2026-01-05', '2026-01-19');

INSERT INTO Reservation (member_id, book_isbn, branch_id, reservation_date, expiry_date) VALUES
(1, '9780747532699', 1, '2026-01-15', '2026-01-17');

INSERT INTO Payment (member_id, amount, payment_date, payment_method) VALUES
(1, 5.00, '2026-01-10', 'CASH');