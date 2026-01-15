CREATE DATABASE techcitylibrarydb;

CREATE TABLE Category (category_id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(100) NOT NULL);

CREATE TABLE Author (author_id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(200) NOT NULL, biography TEXT, nationality VARCHAR(100), birth_date DATE, primary_genre VARCHAR(100));

CREATE TABLE Book (isbn VARCHAR(13) PRIMARY KEY, title VARCHAR(500) NOT NULL, publication_year INT, available_copies INT DEFAULT 0, status VARCHAR(50) DEFAULT 'Available', category_id INT, FOREIGN KEY (category_id) REFERENCES Category(category_id));

CREATE TABLE Book_Author (book_isbn VARCHAR(13), author_id INT, PRIMARY KEY (book_isbn, author_id), FOREIGN KEY (book_isbn) REFERENCES Book(isbn), FOREIGN KEY (author_id) REFERENCES Author(author_id));

CREATE TABLE Library_Branch (branch_id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(200) NOT NULL, location VARCHAR(500), contact_number VARCHAR(20));


CREATE TABLE Branch_Inventory (branch_id INT, book_isbn VARCHAR(13), copies INT DEFAULT 0, PRIMARY KEY (branch_id, book_isbn), FOREIGN KEY (branch_id) REFERENCES Library_Branch(branch_id), FOREIGN KEY (book_isbn) REFERENCES Book(isbn));
