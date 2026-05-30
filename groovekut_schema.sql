-- ============================================================
-- GrooveKut — MySQL Schema (Updated)
-- Database: groovekut_db
-- Run this ONCE in phpMyAdmin → Import tab
-- ============================================================

CREATE DATABASE IF NOT EXISTS groovekut_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE groovekut_db;

-- ------------------------------------------------------------
-- TABLE: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    username          VARCHAR(50)  NOT NULL UNIQUE,
    email             VARCHAR(100) NOT NULL UNIQUE,
    password_hash     VARCHAR(255) NOT NULL,
    preferred_mood    ENUM('happy','chill','focus','sad','hype') DEFAULT NULL,
    preferred_genres  JSON         DEFAULT NULL,   -- e.g. ["Pop","Hip-Hop","Lo-fi"]
    onboarding_done   TINYINT(1)   DEFAULT 0,      -- 0 = show onboarding, 1 = already done
    remember_token    VARCHAR(64)  DEFAULT NULL,   -- 30-day cookie auth
    created_at        DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: songs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS songs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(150) NOT NULL,
    artist      VARCHAR(100) NOT NULL,
    genre       VARCHAR(50)  NOT NULL,
    mood_tag    ENUM('happy','chill','focus','sad','hype') NOT NULL,
    is_explicit TINYINT(1)   DEFAULT 0,      -- 0 = clean, 1 = explicit,
    manual_tags VARCHAR(255) DEFAULT NULL,       -- comma-separated e.g. "party,workout,lofi"
    file_path   VARCHAR(255) NOT NULL,           -- e.g. uploads/songs/001-artist-title.mp3
    cover_path  VARCHAR(255) DEFAULT NULL,       -- e.g. uploads/covers/001-artist-title.jpg
    duration    INT          DEFAULT NULL        -- seconds
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: play_history
-- play_count increments each replay — one row per user+song pair
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS play_history (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT      NOT NULL,
    song_id     INT      NOT NULL,
    played_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    play_count  INT      DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_song (user_id, song_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: liked_songs
-- Heart button clicks — strongest rec signal (×3)
-- Also powers the auto "Liked Songs" playlist
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS liked_songs (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id  INT      NOT NULL,
    song_id  INT      NOT NULL,
    liked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, song_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: playlists
-- Auto-generated only (mood + genre). No user-created playlists.
-- type: 'mood' | 'genre' | 'liked'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS playlists (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    name       VARCHAR(100) NOT NULL,
    type       ENUM('mood','genre','liked') DEFAULT 'genre',
    song_ids   JSON         DEFAULT NULL,  -- e.g. [1, 5, 12, 7]
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE: mood_log
-- Every mood pick logged — powers trending + rec engine
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mood_log (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    mood      ENUM('happy','chill','focus','sad','hype') NOT NULL,
    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Seed data — replace with your real songs after naming them
-- File naming convention: {id}-{artist-slug}-{title-slug}.mp3
-- ============================================================
INSERT INTO songs (title, artist, genre, mood_tag, manual_tags, file_path, cover_path, duration) VALUES
('Sample Track 1', 'Artist One',   'Pop',        'happy', 'upbeat,summer',    'uploads/songs/001-artist-one-sample-track-1.mp3',  'uploads/covers/001-artist-one-sample-track-1.jpg',  210),
('Sample Track 2', 'Artist Two',   'Lo-fi',      'chill', 'study,background', 'uploads/songs/002-artist-two-sample-track-2.mp3',  'uploads/covers/002-artist-two-sample-track-2.jpg',  185),
('Sample Track 3', 'Artist Three', 'Hip-Hop',    'hype',  'workout,energy',   'uploads/songs/003-artist-three-sample-track-3.mp3','uploads/covers/003-artist-three-sample-track-3.jpg',195),
('Sample Track 4', 'Artist Four',  'Indie',      'sad',   'rain,feels',       'uploads/songs/004-artist-four-sample-track-4.mp3', 'uploads/covers/004-artist-four-sample-track-4.jpg', 230),
('Sample Track 5', 'Artist Five',  'Electronic', 'focus', 'coding,flow',      'uploads/songs/005-artist-five-sample-track-5.mp3', 'uploads/covers/005-artist-five-sample-track-5.jpg', 200);
