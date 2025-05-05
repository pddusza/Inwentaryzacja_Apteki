-- schema.sql

-- Utwórz bazę danych, jeśli nie istnieje, i użyj jej
CREATE DATABASE IF NOT EXISTS apteczka;
USE apteczka;

-- Tabela użytkowników
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Tabela apteczek
CREATE TABLE cabinets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Tabela łącząca użytkowników z apteczkami wraz z uprawnieniami
CREATE TABLE cabinet_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT NOT NULL,
    user_id INT NOT NULL,
    -- Flagi uprawnień: 1 = ma dostęp, 0 = brak dostępu
    can_add_med TINYINT(1) NOT NULL DEFAULT 0,
    can_usage TINYINT(1) NOT NULL DEFAULT 0,
    can_reports TINYINT(1) NOT NULL DEFAULT 0,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabela leków (słownik)
CREATE TABLE medications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Tabela leków przypisanych do apteczki
CREATE TABLE cabinet_medications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT NOT NULL,
    medication_id INT NOT NULL,
    quantity INT NOT NULL,
    expiration_date DATE,
    purchase_date DATE,
    price DECIMAL(10,2),
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id),
    FOREIGN KEY (medication_id) REFERENCES medications(id)
);

-- Tabela ruchów magazynowych
CREATE TABLE movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_medication_id INT NOT NULL,
    movement_type ENUM('przychod', 'rozchod', 'utylizacja') NOT NULL,
    quantity INT NOT NULL,
    movement_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    action_user_id INT,
    FOREIGN KEY (cabinet_medication_id) REFERENCES cabinet_medications(id),
    FOREIGN KEY (action_user_id) REFERENCES users(id)
);

-- Tabela żądań dołączenia do apteczki
CREATE TABLE cabinet_join_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cabinet_id INT NOT NULL,
  requester_user_id INT NOT NULL,
  status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
  request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cabinet_id) REFERENCES cabinets(id),
  FOREIGN KEY (requester_user_id) REFERENCES users(id)
);
