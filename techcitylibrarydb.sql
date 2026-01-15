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

