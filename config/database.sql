-- Créer la base de données
CREATE DATABASE IF NOT EXISTS carrent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE carrent;

-- Table des utilisateurs

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Identifiant unique de l''utilisateur',
    first_name VARCHAR(100) NOT NULL COMMENT 'Prénom',
    last_name VARCHAR(100) NOT NULL COMMENT 'Nom',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Statut du compte',
    role ENUM('admin', 'user', 'manager') DEFAULT 'user' COMMENT 'Rôle de l''utilisateur',
    email VARCHAR(150) UNIQUE NOT NULL COMMENT 'Adresse email (unique)',
    password VARCHAR(255) NOT NULL COMMENT 'Mot de passe (haché de préférence)',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création automatique'
) COMMENT 'Table des utilisateurs du système';


-- Table des catégories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des voitures
CREATE TABLE cars (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'unavailable') DEFAULT 'available',
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

