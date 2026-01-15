CREATE DATABASE techcitylibrarydb;

CREATE TABLE Category (category_id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(100) NOT NULL);

CREATE TABLE Author (author_id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(200) NOT NULL, biography TEXT, nationality VARCHAR(100), birth_date DATE, primary_genre VARCHAR(100));

CREATE TABLE Book (isbn VARCHAR(13) PRIMARY KEY, title VARCHAR(500) NOT NULL, publication_year INT, available_copies INT DEFAULT 0, status VARCHAR(50) DEFAULT 'Available', category_id INT, FOREIGN KEY (category_id) REFERENCES Category(category_id));

