CREATE DATABASE techcitylibrarydb;

CREATE TABLE Category (category_id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(100) NOT NULL);

CREATE TABLE Author (author_id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(200) NOT NULL, biography TEXT, nationality VARCHAR(100), birth_date DATE, primary_genre VARCHAR(100));
