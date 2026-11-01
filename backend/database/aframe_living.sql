-- =====================================================================
-- A-Frame Living – MySQL database with sample data (Milestone 2)
--
-- Import (creates the database `aframe_living` and all tables):
--   phpMyAdmin: Import -> choose this file -> Go
--   or command line: mysql -u root < backend/database/aframe_living.sql
--
-- Importing again resets all tables to this sample data.
-- The design is documented in docs/database-plan.md.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS aframe_living CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aframe_living;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS inquiries;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS model_images;
DROP TABLE IF EXISTS model_features;
DROP TABLE IF EXISTS features;
DROP TABLE IF EXISTS model_prices;
DROP TABLE IF EXISTS packages;
DROP TABLE IF EXISTS house_models;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Tables
-- ---------------------------------------------------------------------

-- Accounts for customers and administrators
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name    VARCHAR(60)  NOT NULL,
  last_name     VARCHAR(60)  NOT NULL,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,              -- password_hash() result, never the plain password
  phone         VARCHAR(20)  NULL,
  role          ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The A-frame house models
CREATE TABLE house_models (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug               VARCHAR(60)  NOT NULL,          -- used in URLs, e.g. "norway"
  name               VARCHAR(100) NOT NULL,
  summary            TEXT         NULL,
  area_m2            DECIMAL(6,2) NOT NULL,
  bedrooms           TINYINT UNSIGNED NOT NULL,
  bathrooms          TINYINT UNSIGNED NOT NULL,
  footprint_width_m  DECIMAL(6,2) NOT NULL,
  footprint_length_m DECIMAL(6,2) NOT NULL,
  side_room_width_m  DECIMAL(6,2) NULL,              -- NULL when the model has no side room
  side_room_length_m DECIMAL(6,2) NULL,
  height_m           DECIMAL(6,2) NOT NULL,
  is_active          TINYINT(1)   NOT NULL DEFAULT 1, -- 0 = hidden from the public site
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_house_models_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The packages every model is sold in (ROH BAU, Turnkey)
CREATE TABLE packages (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code        VARCHAR(30)  NOT NULL,                 -- e.g. "roh_bau"
  name        VARCHAR(60)  NOT NULL,
  description TEXT         NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_packages_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Price of a model in a package (many-to-many link with data)
CREATE TABLE model_prices (
  model_id   INT UNSIGNED NOT NULL,
  package_id INT UNSIGNED NOT NULL,
  price_eur  DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (model_id, package_id),
  CONSTRAINT fk_model_prices_model   FOREIGN KEY (model_id)   REFERENCES house_models (id) ON DELETE CASCADE,
  CONSTRAINT fk_model_prices_package FOREIGN KEY (package_id) REFERENCES packages (id)     ON DELETE CASCADE,
  CONSTRAINT chk_model_prices_positive CHECK (price_eur >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rooms and features, e.g. "Kitchen", "Fireplace"
CREATE TABLE features (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name     VARCHAR(100) NOT NULL,
  category ENUM('room', 'construction', 'equipment') NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_features_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which model has which feature (many-to-many link)
CREATE TABLE model_features (
  model_id   INT UNSIGNED NOT NULL,
  feature_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (model_id, feature_id),
  CONSTRAINT fk_model_features_model   FOREIGN KEY (model_id)   REFERENCES house_models (id) ON DELETE CASCADE,
  CONSTRAINT fk_model_features_feature FOREIGN KEY (feature_id) REFERENCES features (id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Photos of a model; sort_order 0 is the main photo
CREATE TABLE model_images (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  model_id   INT UNSIGNED NOT NULL,
  file_path  VARCHAR(255) NOT NULL,                  -- relative to frontend/, e.g. assets/images/norway/norway1.JPG
  alt_text   VARCHAR(255) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_model_images_model (model_id, sort_order),
  CONSTRAINT fk_model_images_model FOREIGN KEY (model_id) REFERENCES house_models (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Places where houses have been built; coordinates come from OpenStreetMap Nominatim
CREATE TABLE locations (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  city        VARCHAR(100) NOT NULL,
  region      VARCHAR(100) NULL,
  country     VARCHAR(100) NOT NULL,
  latitude    DECIMAL(9,6) NULL,
  longitude   DECIMAL(9,6) NULL,
  geocoded_at DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_locations_city_country (city, country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Completed builds: one model at one location
CREATE TABLE projects (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  model_id     INT UNSIGNED NOT NULL,
  location_id  INT UNSIGNED NOT NULL,
  setting      VARCHAR(60)  NULL,                    -- e.g. "Mountain", "River valley"
  completed_on DATE         NULL,
  PRIMARY KEY (id),
  -- A model or location that still has projects cannot be deleted.
  CONSTRAINT fk_projects_model    FOREIGN KEY (model_id)    REFERENCES house_models (id) ON DELETE RESTRICT,
  CONSTRAINT fk_projects_location FOREIGN KEY (location_id) REFERENCES locations (id)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages sent through the contact form
CREATE TABLE inquiries (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NULL,                      -- NULL = sent by a guest
  model_id   INT UNSIGNED NULL,                      -- NULL = not about a specific model
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  subject    VARCHAR(150) NOT NULL,
  message    TEXT         NOT NULL,
  status     ENUM('new', 'in_progress', 'answered', 'closed') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_inquiries_status (status),
  -- Deleting a user or model keeps the inquiry; the link is set to NULL.
  CONSTRAINT fk_inquiries_user  FOREIGN KEY (user_id)  REFERENCES users (id)        ON DELETE SET NULL,
  CONSTRAINT fk_inquiries_model FOREIGN KEY (model_id) REFERENCES house_models (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Sample data
-- ---------------------------------------------------------------------

-- Users (test accounts: admin = Admin123!, customers = Customer123!)
INSERT INTO users (id, first_name, last_name, email, password_hash, phone, role, created_at) VALUES
  (1, 'Admin', 'User', 'admin@aframe-living.example', '$2y$12$jaTgiqgX53grSWDqNSkKQO8m73k9itPHsn9vATFjel1ZUgfRTDcqe', NULL, 'admin', '2026-09-01 10:00:00'),
  (2, 'Ana', 'Kovač', 'ana.kovac@example.com', '$2y$12$gHi1xwIq0Cx1lXS8jh8JkOPv2rSiSJDm1D3vOPMGWbRfbNgXWK7Y.', '+387 61 000 001', 'customer', '2026-10-02 10:00:00'),
  (3, 'Emir', 'Hadžić', 'emir.hadzic@example.com', '$2y$12$gHi1xwIq0Cx1lXS8jh8JkOPv2rSiSJDm1D3vOPMGWbRfbNgXWK7Y.', NULL, 'customer', '2026-10-11 10:00:00'),
  (4, 'Lena', 'Marić', 'lena.maric@example.com', '$2y$12$gHi1xwIq0Cx1lXS8jh8JkOPv2rSiSJDm1D3vOPMGWbRfbNgXWK7Y.', '+387 62 000 002', 'customer', '2026-10-19 10:00:00');

-- House models (facts provided by the owner; heights and some side-room sizes written for the assignment)
INSERT INTO house_models (id, slug, name, summary, area_m2, bedrooms, bathrooms, footprint_width_m, footprint_length_m, side_room_width_m, side_room_length_m, height_m, is_active) VALUES
  (1, 'atlantic', 'Atlantic', 'Our most compact model: 46 m² with one bedroom and a gallery level under the roof.', 46, 1, 1, 5, 7, NULL, NULL, 5.8, 1),
  (2, 'norway', 'Norway', 'Two bedrooms on 56 m² – the model we have built most often.', 56, 2, 1, 5, 7, 2.5, 4, 5.8, 1),
  (3, 'scandinavia', 'Scandinavia', '105 m² with three bedrooms, two bathrooms and laminated timber.', 105, 3, 2, 9, 7, 3.6, 4.8, 7.9, 1),
  (4, 'continental', 'Continental', 'Our largest model: 124 m² with laminated timber, aluminium windows and doors, and a fireplace.', 124, 3, 2, 14, 8, NULL, NULL, 7.5, 1);

-- Packages
INSERT INTO packages (id, code, name, description) VALUES
  (1, 'roh_bau', 'ROH BAU', 'The house is completely finished on the outside. The interior is left for you to design and finish yourself.'),
  (2, 'turnkey', 'Turnkey', 'Everything is done except the furniture – including a finished kitchen and bathroom. You just move in with your own furniture.');

-- Price of every model in every package (EUR)
INSERT INTO model_prices (model_id, package_id, price_eur) VALUES
  (1, 1, 16000),
  (1, 2, 37000),
  (2, 1, 22000),
  (2, 2, 48000),
  (3, 1, 40000),
  (3, 2, 80000),
  (4, 1, 45000),
  (4, 2, 90000);

-- Features (rooms every model has, plus extra features)
INSERT INTO features (id, name, category) VALUES
  (1, 'Living room', 'room'),
  (2, 'Kitchen', 'room'),
  (3, 'Gallery as a second floor', 'room'),
  (4, 'Laminated timber', 'construction'),
  (5, 'Aluminium windows and doors', 'construction'),
  (6, 'Fireplace', 'equipment');

-- Which model has which feature
INSERT INTO model_features (model_id, feature_id) VALUES
  (1, 1),
  (1, 2),
  (1, 3),
  (2, 1),
  (2, 2),
  (2, 3),
  (3, 1),
  (3, 2),
  (3, 3),
  (3, 4),
  (4, 1),
  (4, 2),
  (4, 3),
  (4, 4),
  (4, 5),
  (4, 6);

-- Photos (sort_order 0 = main photo); paths are relative to frontend/
INSERT INTO model_images (model_id, file_path, alt_text, sort_order) VALUES
  (1, 'assets/images/atlantic/atlantic.jfif', 'Atlantic A-frame house with a dark metal roof, a glazed timber front and a wooden deck, set among pine trees', 0),
  (1, 'assets/images/atlantic/atlantic1.JPEG', 'Atlantic exterior: glazed front and dark metal roof at sunset', 1),
  (1, 'assets/images/atlantic/atlantic2.JPEG', 'Atlantic exterior in falling snow', 2),
  (1, 'assets/images/atlantic/atlantic22.JPG', 'Atlantic with its wooden deck in a snowy mountain landscape', 3),
  (1, 'assets/images/atlantic/atlantic3.png', 'Atlantic glazed front lit up at night', 4),
  (1, 'assets/images/atlantic/atlantic4.jfif', 'Atlantic interior: living area with the staircase to the gallery', 5),
  (1, 'assets/images/atlantic/atlantic5.png', 'Atlantic interior: view from the gallery towards the tall front window', 6),
  (2, 'assets/images/norway/norway.jfif', 'Norway A-frame house with a dark roof, a tall glazed timber gable and a low side extension, in a garden at dusk', 0),
  (2, 'assets/images/norway/norway1.JPG', 'Norway exterior at dusk with lit roof edges and a timber side extension', 1),
  (2, 'assets/images/norway/norway2.JPG', 'Norway exterior with its timber-clad side extension on a sunny day', 2),
  (2, 'assets/images/norway/norway3.JPG', 'Norway living room with a sofa and round coffee table in front of the glazed gable', 3),
  (2, 'assets/images/norway/norway4.JPG', 'Norway living area with the staircase up to the gallery', 4),
  (2, 'assets/images/norway/norway5.JPG', 'Norway living room next to the staircase, facing the glazed gable', 5),
  (2, 'assets/images/norway/norway6.JPG', 'Norway hallway leading to the kitchen', 6),
  (2, 'assets/images/norway/norway7.JPG', 'Norway living room with an armchair, a sofa and a round coffee table', 7),
  (2, 'assets/images/norway/norway8.JPG', 'Norway bedroom with a large window', 8),
  (2, 'assets/images/norway/norway9.JPG', 'Norway bedroom under the sloping timber roof', 9),
  (3, 'assets/images/scandinavia/scandinavia.jfif', 'Scandinavia A-frame house with a dark roof, a large glazed timber gable and a timber-clad side wing, on a lawn', 0),
  (3, 'assets/images/scandinavia/scandinavia1.jpeg', 'Scandinavia exterior: glazed gable and side wing in daylight', 1),
  (3, 'assets/images/scandinavia/scandinavia2.jpeg', 'Scandinavia seen from the back, with dark red cladding', 2),
  (3, 'assets/images/scandinavia/scandinavia3.jpg', 'Scandinavia kitchen and dining area with a breakfast bar and the gallery above', 3),
  (3, 'assets/images/scandinavia/scandinavia4.jpg', 'Scandinavia living room with a corner sofa and the staircase to the gallery', 4),
  (3, 'assets/images/scandinavia/scandinavia5.jpg', 'Scandinavia dining table in front of the kitchen', 5),
  (3, 'assets/images/scandinavia/scandinavia6.jpg', 'Scandinavia galley kitchen', 6),
  (3, 'assets/images/scandinavia/scandinavia7.jpg', 'Scandinavia hallway with built-in storage, looking towards the living room', 7),
  (3, 'assets/images/scandinavia/scandinavia8.jpg', 'Scandinavia bedroom with a tall mirror and wall art', 8),
  (3, 'assets/images/scandinavia/scandinavia9.jpg', 'Scandinavia bathroom with a vessel sink', 9),
  (3, 'assets/images/scandinavia/scandinavia10.jpg', 'Scandinavia upper floor with doors to two rooms under the roof', 10),
  (3, 'assets/images/scandinavia/scandinavia11.jpg', 'Scandinavia bedroom with a built-in wardrobe under the roof', 11),
  (4, 'assets/images/continental/continental.jfif', 'Continental house with a steep dark roof, a glazed front, a stone chimney wall and a covered timber porch, in a forest', 0),
  (4, 'assets/images/continental/continental1.JPG', 'Continental exterior: glazed gable and stone chimney wall on a sunny day', 1),
  (4, 'assets/images/continental/continental2.JPG', 'Continental side view with dark cladding', 2),
  (4, 'assets/images/continental/continental3.JPG', 'Continental kitchen island with the living area behind', 3),
  (4, 'assets/images/continental/continental4.JPG', 'Continental open-plan living, dining and kitchen area with the fireplace', 4),
  (4, 'assets/images/continental/continental5.JPG', 'Continental living room with the stone fireplace and tall windows', 5),
  (4, 'assets/images/continental/continental6.JPG', 'Continental kitchen island facing the dining area', 6),
  (4, 'assets/images/continental/continental7.JPG', 'Continental fireplace and dining area seen from the hallway', 7),
  (4, 'assets/images/continental/continental8.JPG', 'Continental dining area and kitchen seen from the gallery', 8),
  (4, 'assets/images/continental/continental9.JPG', 'Continental living and dining area seen from the gallery', 9),
  (4, 'assets/images/continental/continental10.JPG', 'Continental bedroom on the gallery level with a roof window', 10),
  (4, 'assets/images/continental/continental11.JPG', 'Continental gallery bedroom with a glass balustrade', 11),
  (4, 'assets/images/continental/continental12.JPG', 'Continental kitchen seen from the gallery level', 12),
  (4, 'assets/images/continental/continental13.JPG', 'Continental gallery bedroom facing the stone chimney and the glazed gable', 13);

-- Locations of completed projects (coordinates are filled in later by the Nominatim integration)
INSERT INTO locations (id, city, region, country) VALUES
  (1, 'Velika Kladuša', 'Una-Sana', 'Bosnia and Herzegovina'),
  (2, 'Bihać', 'Una-Sana', 'Bosnia and Herzegovina'),
  (3, 'Banja Luka', 'Bosanska Krajina', 'Bosnia and Herzegovina'),
  (4, 'Šipovo', 'Pliva valley', 'Bosnia and Herzegovina'),
  (5, 'Kupres', 'Kupres plateau', 'Bosnia and Herzegovina'),
  (6, 'Blidinje', 'Blidinje Nature Park', 'Bosnia and Herzegovina'),
  (7, 'Rostovo', 'Central Bosnia', 'Bosnia and Herzegovina'),
  (8, 'Sarajevo', 'Sarajevo area', 'Bosnia and Herzegovina'),
  (9, 'Jahorina', 'Sarajevo area', 'Bosnia and Herzegovina'),
  (10, 'Konjic', 'Neretva valley', 'Bosnia and Herzegovina'),
  (11, 'Ivanica', 'Eastern Herzegovina', 'Bosnia and Herzegovina'),
  (12, 'Brčko', 'Brčko District', 'Bosnia and Herzegovina'),
  (13, 'Visoko', 'Central Bosnia', 'Bosnia and Herzegovina'),
  (14, 'Bjelašnica', 'Sarajevo area', 'Bosnia and Herzegovina');

-- Completed projects: which model was built where (setting written for the assignment)
INSERT INTO projects (id, model_id, location_id, setting) VALUES
  (1, 2, 1, 'Countryside'),
  (2, 2, 2, 'River valley'),
  (3, 2, 3, 'Town outskirts'),
  (4, 2, 4, 'River valley'),
  (5, 2, 5, 'Mountain'),
  (6, 2, 6, 'Mountain'),
  (7, 3, 7, 'Mountain'),
  (8, 4, 8, 'Town outskirts'),
  (9, 2, 9, 'Mountain'),
  (10, 2, 10, 'River valley'),
  (11, 2, 11, 'Countryside'),
  (12, 2, 12, 'Countryside'),
  (13, 2, 13, 'Countryside'),
  (14, 1, 14, 'Mountain');

-- Example inquiries (example data written for the assignment)
INSERT INTO inquiries (id, user_id, model_id, name, email, subject, message, status, created_at) VALUES
  (1, 2, 2, 'Ana Kovač', 'ana.kovac@example.com', 'Norway on Kupres', 'We have a plot on Kupres and would like to know more about the Norway model and the turnkey package.', 'answered', '2026-10-03 09:30:00'),
  (2, 2, 2, 'Ana Kovač', 'ana.kovac@example.com', 'Turnkey timeline question', 'How long does it usually take from signing the contract to handover for a turnkey Norway?', 'in_progress', '2026-10-20 09:30:00'),
  (3, 3, 4, 'Emir Hadžić', 'emir.hadzic@example.com', 'Inquiry about the Continental model', 'I am interested in the Continental model. Could you send me more information about the fireplace and the windows?', 'new', '2026-10-22 09:30:00'),
  (4, NULL, NULL, 'Marko Petrović', 'marko.p@example.com', 'Do you build near Mostar?', 'Do you also build in the Mostar area? We are thinking about a small house for weekends.', 'new', '2026-10-24 09:30:00'),
  (5, 4, 1, 'Lena Marić', 'lena.maric@example.com', 'Atlantic as a weekend house', 'We would like an Atlantic as a weekend house. Thank you for the information.', 'closed', '2026-10-05 09:30:00');
