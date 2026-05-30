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
USE groovekut_db;

INSERT INTO songs (title, artist, genre, mood_tag, is_explicit, manual_tags, file_path, cover_path, duration) VALUES
  ('Crank That (Soulja Boy)', 'Soulja Boy Tell''em', 'Hip-Hop', 'hype', 0, 'dance,party,throwback', 'uploads/songs/001-soulja-boy-tellem-crank-that-soulja-boy.mp3', 'uploads/covers/001-soulja-boy-tellem-crank-that-soulja-boy.jpg', 222),
  ('redrum', '21 Savage', 'Hip-Hop', 'hype', 1, 'drill,dark,trap', 'uploads/songs/002-21-savage-redrum.mp3', 'uploads/covers/002-21-savage-redrum.jpg', 271),
  ('Where is the Bread', 'Khantrast', 'Hip-Hop', 'hype', 1, 'rap,energy,bars', 'uploads/songs/003-khantrast-where-is-the-bread.mp3', 'uploads/covers/003-khantrast-where-is-the-bread.jpg', 143),
  ('No Pole', 'Don Toliver', 'Hip-Hop', 'hype', 1, 'trap,dark,energy', 'uploads/songs/004-don-toliver-no-pole.mp3', 'uploads/covers/004-don-toliver-no-pole.jpg', 188),
  ('Get Jiggy', 'B Jack$, ZEDDY WILL', 'Hip-Hop', 'hype', 1, 'trap,party,energy', 'uploads/songs/005-b-jack-get-jiggy.mp3', 'uploads/covers/005-b-jack-get-jiggy.jpg', 119),
  ('All That Matters #jerseyclub [Prod. Salako] (Audio)', 'Salako', 'Electronic', 'hype', 0, 'jerseyclub,dance,party', 'uploads/songs/006-salako-all-that-matters-jerseyclub-prod.mp3', 'uploads/covers/006-salako-all-that-matters-jerseyclub-prod.jpg', 136),
  ('Boom Boom Pow', 'The Black Eyed Peas', 'Pop', 'happy', 0, 'dance,party,throwback', 'uploads/songs/007-the-black-eyed-peas-boom-boom-pow.mp3', 'uploads/covers/007-the-black-eyed-peas-boom-boom-pow.jpg', 252),
  ('GO DO IT #jerseyclub', 'Ziahfyah, sjmadeit', 'Electronic', 'hype', 1, 'jerseyclub,dance,party', 'uploads/songs/008-ziahfyah-go-do-it-jerseyclub.mp3', 'uploads/covers/008-ziahfyah-go-do-it-jerseyclub.jpg', 138),
  ('SIMPLY THE BEST', 'Black Eyed Peas, Anitta, El Alfa', 'Pop', 'happy', 1, 'dance,party,latin', 'uploads/songs/009-black-eyed-peas-simply-the-best.mp3', 'uploads/covers/009-black-eyed-peas-simply-the-best.jpg', 237),
  ('Surround Sound (feat. 21 Savage & Baby Tate)', 'JID', 'Hip-Hop', 'hype', 1, 'rap,bars,energy', 'uploads/songs/010-jid-surround-sound-feat-21-savage.mp3', 'uploads/covers/010-jid-surround-sound-feat-21-savage.jpg', 230),
  ('Vibe (If I Back It Up)', 'Cookiee Kawaii, Tyga', 'Electronic', 'happy', 1, 'dance,party,twerk', 'uploads/songs/011-cookiee-kawaii-vibe-if-i-back-it.mp3', 'uploads/covers/011-cookiee-kawaii-vibe-if-i-back-it.jpg', 145),
  ('Vibe (If I Back It Up)', 'Cookiee Kawaii', 'Electronic', 'happy', 1, 'dance,party,twerk', 'uploads/songs/012-cookiee-kawaii-vibe-if-i-back-it.mp3', 'uploads/covers/012-cookiee-kawaii-vibe-if-i-back-it.jpg', 84),
  ('the kill 2', 'Lex Amarni, 2muchmotion', 'Hip-Hop', 'hype', 1, 'dark,drill,energy', 'uploads/songs/013-lex-amarni-the-kill-2.mp3', 'uploads/covers/013-lex-amarni-the-kill-2.jpg', 122),
  ('I just get so nervy', 'H11T', 'Hip-Hop', 'hype', 0, 'uk,bars,energy', 'uploads/songs/014-h11t-i-just-get-so-nervy.mp3', 'uploads/covers/014-h11t-i-just-get-so-nervy.jpg', 70),
  ('Papi Chulo', 'El Chombo', 'Pop', 'happy', 1, 'latin,dance,party', 'uploads/songs/015-el-chombo-papi-chulo.mp3', 'uploads/covers/015-el-chombo-papi-chulo.jpg', 176),
  ('southbound', 'Artemas', 'Indie', 'sad', 1, 'feels,heartbreak,late_night', 'uploads/songs/016-artemas-southbound.mp3', 'uploads/covers/016-artemas-southbound.jpg', 138),
  ('Yrn', 'Hector AC', 'Pop', 'happy', 0, 'upbeat,dance,party', 'uploads/songs/017-hector-ac-yrn.mp3', 'uploads/covers/017-hector-ac-yrn.jpg', 100),
  ('worry (ultra slowed)', 'LONOWN, riserayss', 'Electronic', 'chill', 0, 'slowed,dreamy,late_night', 'uploads/songs/018-lonown-worry-ultra-slowed.mp3', 'uploads/covers/018-lonown-worry-ultra-slowed.jpg', 256),
  ('Resident Evil 4 Remake - The Drive Extended Theme (Shooting Range Bonus Mix)', 'MusNik', 'Electronic', 'focus', 0, 'gaming,ambient,cinematic', 'uploads/songs/019-musnik-resident-evil-4-remake.mp3', 'uploads/covers/019-musnik-resident-evil-4-remake.jpg', 251),
  ('Flashing Lights', 'Kanye West', 'Hip-Hop', 'chill', 0, 'kanye,smooth,late_night', 'uploads/songs/020-kanye-west-flashing-lights.mp3', 'uploads/covers/020-kanye-west-flashing-lights.jpg', 238),
  ('八方來財(Stacks from All Sides)', '攬佬SKAI ISYOURGOD', 'Hip-Hop', 'happy', 0, 'hype,flex,party', 'uploads/songs/021-skai-isyourgod-stacks-from-all-sides.mp3', 'uploads/covers/021-skai-isyourgod-stacks-from-all-sides.jpg', 174),
  ('AKAI (Slowed)', 'UdieNnx, HXVSAGE, Mc Luizinho', 'Electronic', 'chill', 0, 'slowed,phonk,late_night', 'uploads/songs/022-udiennx-akai-slowed.mp3', 'uploads/covers/022-udiennx-akai-slowed.jpg', 97),
  ('Move Bitch', 'Philthy Fingers', 'Hip-Hop', 'hype', 1, 'trap,energy,bars', 'uploads/songs/023-philthy-fingers-move-bitch.mp3', 'uploads/covers/023-philthy-fingers-move-bitch.jpg', 299),
  ('TENTACIO!', 'BlaxcK, MXKINI', 'Pop', 'happy', 1, 'dance,latin,party', 'uploads/songs/024-blaxck-tentacio.mp3', 'uploads/covers/024-blaxck-tentacio.jpg', 66),
  ('Love Me', 'JMSN', 'R&B', 'chill', 0, 'smooth,love,vibes', 'uploads/songs/025-jmsn-love-me.mp3', 'uploads/covers/025-jmsn-love-me.jpg', 274),
  ('COOOK PARDON', 'Lvbel C5, AKDO', 'Electronic', 'happy', 1, 'latin,dance,party', 'uploads/songs/026-lvbel-c5-coook-pardon.mp3', 'uploads/covers/026-lvbel-c5-coook-pardon.jpg', 93),
  ('In the End', 'Linkin Park', 'Rock', 'sad', 0, 'emo,feels,throwback', 'uploads/songs/027-linkin-park-in-the-end.mp3', 'uploads/covers/027-linkin-park-in-the-end.jpg', 217),
  ('MONTAGEM FEARLESS (Ultra Slowed)', 'lirvie, DJ eu4oria, DJ FZ DA ZN, Matra!', 'Electronic', 'chill', 0, 'phonk,slowed,late_night', 'uploads/songs/028-lirvie-montagem-fearless-ultra-slowed.mp3', 'uploads/covers/028-lirvie-montagem-fearless-ultra-slowed.jpg', 153),
  ('VIDA LOCA', 'Black Eyed Peas, Nicky Jam', 'Pop', 'happy', 1, 'latin,dance,party', 'uploads/songs/029-black-eyed-peas-vida-loca.mp3', 'uploads/covers/029-black-eyed-peas-vida-loca.jpg', 235),
  ('MAMA MA (INSTRUMENTAL Slowed)', 'SXLLX', 'Electronic', 'chill', 0, 'phonk,slowed,ambient', 'uploads/songs/030-sxllx-mama-ma-instrumental-slowed.mp3', 'uploads/covers/030-sxllx-mama-ma-instrumental-slowed.jpg', 90),
  ('MAMA MA (INSTRUMENTAL)', 'SXLLX', 'Electronic', 'focus', 0, 'ambient,cinematic,instrumental', 'uploads/songs/031-sxllx-mama-ma-instrumental.mp3', 'uploads/covers/031-sxllx-mama-ma-instrumental.jpg', 74),
  ('MAMA MA (Slowed)', 'SXLLX', 'Electronic', 'chill', 0, 'phonk,slowed,late_night', 'uploads/songs/032-sxllx-mama-ma-slowed.mp3', 'uploads/covers/032-sxllx-mama-ma-slowed.jpg', 91),
  ('MAMA MA', 'SXLLX', 'Pop', 'happy', 0, 'dance,upbeat,party', 'uploads/songs/033-sxllx-mama-ma.mp3', 'uploads/covers/033-sxllx-mama-ma.jpg', 74),
  ('La Bomba', 'Rak-Su', 'Pop', 'happy', 0, 'dance,latin,party', 'uploads/songs/034-rak-su-la-bomba.mp3', 'uploads/covers/034-rak-su-la-bomba.jpg', 175),
  ('Fireball (feat. John Ryan)', 'Pitbull', 'Pop', 'happy', 0, 'dance,upbeat,party', 'uploads/songs/035-pitbull-fireball-feat-john-ryan.mp3', 'uploads/covers/035-pitbull-fireball-feat-john-ryan.jpg', 236),
  ('Love Me Back (Fayahh Beat)', 'Trinidad Cardona, Robinson', 'Pop', 'happy', 0, 'love,upbeat,summer', 'uploads/songs/036-trinidad-cardona-love-me-back-fayahh-beat.mp3', 'uploads/covers/036-trinidad-cardona-love-me-back-fayahh-beat.jpg', 194),
  ('If We Being Rëal', 'Yeat', 'Hip-Hop', 'hype', 1, 'yeat,trap,energy', 'uploads/songs/037-yeat-if-we-being-ral.mp3', 'uploads/covers/037-yeat-if-we-being-ral.jpg', 173),
  ('HAZA (Slowed)', 'UdieNnx', 'Electronic', 'chill', 0, 'phonk,slowed,late_night', 'uploads/songs/038-udiennx-haza-slowed.mp3', 'uploads/covers/038-udiennx-haza-slowed.jpg', 145),
  ('wutiwant (SLOWD)', 'saraunh0ly', 'Pop', 'hype', 1, 'party,energy,dance', 'uploads/songs/039-saraunh0ly-wutiwant-slowd.mp3', 'uploads/covers/039-saraunh0ly-wutiwant-slowd.jpg', 107),
  ('NITE - SLOWED', 'ELVEN DIOR', 'Electronic', 'chill', 0, 'slowed,dreamy,late_night', 'uploads/songs/040-elven-dior-nite-slowed.mp3', 'uploads/covers/040-elven-dior-nite-slowed.jpg', 121),
  ('archangel (Slowed)', 'DJ Anemia, Crier, sixnite', 'Electronic', 'chill', 0, 'slowed,dark,late_night', 'uploads/songs/041-dj-anemia-archangel-slowed.mp3', 'uploads/covers/041-dj-anemia-archangel-slowed.jpg', 186),
  ('KRUSHKRUSH!', 'Hussain Ali', 'Pop', 'happy', 0, 'dance,upbeat,fun', 'uploads/songs/042-hussain-ali-krushkrush.mp3', 'uploads/covers/042-hussain-ali-krushkrush.jpg', 67),
  ('Megalomaniac (slowed)', 'FRQNCY', 'Electronic', 'chill', 0, 'slowed,dark,late_night', 'uploads/songs/043-frqncy-megalomaniac-slowed.mp3', 'uploads/covers/043-frqncy-megalomaniac-slowed.jpg', 135),
  ('Megalomaniac', 'FRQNCY', 'Electronic', 'hype', 0, 'dance,energy,party', 'uploads/songs/044-frqncy-megalomaniac.mp3', 'uploads/covers/044-frqncy-megalomaniac.jpg', 108),
  ('Mmm Yeah', 'Austin Mahone, Pitbull', 'Pop', 'happy', 0, 'dance,throwback,party', 'uploads/songs/045-austin-mahone-mmm-yeah.mp3', 'uploads/covers/045-austin-mahone-mmm-yeah.jpg', 232),
  ('Love You Like A Love Song', 'Selena Gomez & The Scene', 'Pop', 'happy', 0, 'love,upbeat,summer', 'uploads/songs/046-selena-gomez-love-you-like-a-love.mp3', 'uploads/covers/046-selena-gomez-love-you-like-a-love.jpg', 189),
  ('Sexy Mama (Radio Edit)', 'Bombay Rockers', 'Punjabi', 'happy', 0, 'bhangra,dance,party', 'uploads/songs/047-bombay-rockers-sexy-mama-radio-edit.mp3', 'uploads/covers/047-bombay-rockers-sexy-mama-radio-edit.jpg', 217),
  ('DJ Got Us Fallin'' In Love (feat. Pitbull)', 'Usher', 'R&B', 'happy', 0, 'dance,throwback,party', 'uploads/songs/048-usher-dj-got-us-fallin-in.mp3', 'uploads/covers/048-usher-dj-got-us-fallin-in.jpg', 221),
  ('Lonely', 'Akon', 'R&B', 'sad', 1, 'feels,heartbreak,late_night', 'uploads/songs/049-akon-lonely.mp3', 'uploads/covers/049-akon-lonely.jpg', 236),
  ('Dangerous (feat. Akon)', 'Kardinal Offishall', 'Hip-Hop', 'hype', 1, 'rap,energy,bars', 'uploads/songs/050-kardinal-offishall-dangerous-feat-akon.mp3', 'uploads/covers/050-kardinal-offishall-dangerous-feat-akon.jpg', 247),
  ('Dance With You (feat. Jay Sean & Juggy D)', 'Rishi Rich', 'Punjabi', 'happy', 1, 'bhangra,dance,party', 'uploads/songs/051-rishi-rich-dance-with-you-feat-jay.mp3', 'uploads/covers/051-rishi-rich-dance-with-you-feat-jay.jpg', 177),
  ('Don''t Be Shy', 'Rouge, Dr Zeus', 'Punjabi', 'happy', 0, 'bhangra,dance,party', 'uploads/songs/052-rouge-dont-be-shy.mp3', 'uploads/covers/052-rouge-dont-be-shy.jpg', 191),
  ('The West Side Freestyle', 'DJ Max Star', 'Hip-Hop', 'happy', 0, 'old_school,dance,throwback', 'uploads/songs/053-dj-max-star-the-west-side-freestyle.mp3', 'uploads/covers/053-dj-max-star-the-west-side-freestyle.jpg', 192),
  ('Smack That (feat. Eminem)', 'Akon', 'Hip-Hop', 'hype', 1, 'rap,energy,bars', 'uploads/songs/054-akon-smack-that-feat-eminem.mp3', 'uploads/covers/054-akon-smack-that-feat-eminem.jpg', 213),
  ('Back That Up To The Beat (Demo Version)', 'Madonna', 'Pop', 'happy', 0, 'dance,upbeat,throwback', 'uploads/songs/055-madonna-back-that-up-to-the.mp3', 'uploads/covers/055-madonna-back-that-up-to-the.jpg', 212),
  ('Move Ya Body (feat. Jabba)', 'Nina Sky', 'Pop', 'happy', 0, 'dance,upbeat,summer', 'uploads/songs/056-nina-sky-move-ya-body-feat-jabba.mp3', 'uploads/covers/056-nina-sky-move-ya-body-feat-jabba.jpg', 232),
  ('Decline', 'plaguedoll, kellv', 'Indie', 'sad', 0, 'dark,feels,late_night', 'uploads/songs/057-plaguedoll-decline.mp3', 'uploads/covers/057-plaguedoll-decline.jpg', 161),
  ('Kings & Queens', 'Ava Max', 'Pop', 'happy', 0, 'upbeat,empowering,dance', 'uploads/songs/058-ava-max-kings-queens.mp3', 'uploads/covers/058-ava-max-kings-queens.jpg', 163),
  ('Big And Chunky', 'will.i.am', 'Pop', 'happy', 0, 'fun,dance,throwback', 'uploads/songs/059-william-big-and-chunky.mp3', 'uploads/covers/059-william-big-and-chunky.jpg', 202),
  ('I''ll Do It (feat. Pitbull)', 'Heidi Montag', 'Pop', 'happy', 0, 'dance,upbeat,party', 'uploads/songs/060-heidi-montag-ill-do-it-feat-pitbull.mp3', 'uploads/covers/060-heidi-montag-ill-do-it-feat-pitbull.jpg', 205),
  ('the perfect pair', 'beabadoobee', 'Indie', 'chill', 0, 'love,dreamy,soft', 'uploads/songs/061-beabadoobee-the-perfect-pair.mp3', 'uploads/covers/061-beabadoobee-the-perfect-pair.jpg', 178),
  ('The Perfect Pair (Jersey Club) (Slowed)', 'ProdByCohen', 'Electronic', 'chill', 0, 'jerseyclub,slowed,dreamy', 'uploads/songs/062-prodbycohen-the-perfect-pair-jersey-club.mp3', 'uploads/covers/062-prodbycohen-the-perfect-pair-jersey-club.jpg', 185),
  ('Princess Bubblegum (Remix)', 'ilyTOMMY', 'Pop', 'happy', 1, 'fun,upbeat,party', 'uploads/songs/063-ilytommy-princess-bubblegum-remix.mp3', 'uploads/covers/063-ilytommy-princess-bubblegum-remix.jpg', 167),
  ('WET DREAMS - LUV N LUST SLOWED', 'ODECORE, Odetari', 'Electronic', 'chill', 0, 'slowed,phonk,late_night', 'uploads/songs/064-odecore-wet-dreams-luv-n.mp3', 'uploads/covers/064-odecore-wet-dreams-luv-n.jpg', 147),
  ('All Star', 'Smash Mouth', 'Rock', 'happy', 0, 'throwback,meme,fun', 'uploads/songs/065-smash-mouth-all-star.mp3', 'uploads/covers/065-smash-mouth-all-star.jpg', 201),
  ('HUMBLE.', 'Kendrick Lamar', 'Hip-Hop', 'hype', 1, 'kendrick,bars,energy', 'uploads/songs/066-kendrick-lamar-humble.mp3', 'uploads/covers/066-kendrick-lamar-humble.jpg', 177),
  ('icybaelie (Slowed)', 'LONOWN', 'Electronic', 'chill', 0, 'slowed,dreamy,late_night', 'uploads/songs/067-lonown-icybaelie-slowed.mp3', 'uploads/covers/067-lonown-icybaelie-slowed.jpg', 195),
  ('Nasty Jamz', 'Coolzone', 'Pop', 'happy', 0, 'dance,upbeat,fun', 'uploads/songs/068-coolzone-nasty-jamz.mp3', 'uploads/covers/068-coolzone-nasty-jamz.jpg', 239),
  ('habibi', 'Rakhim, ayv1o', 'Pop', 'happy', 0, 'love,upbeat,summer', 'uploads/songs/069-rakhim-habibi.mp3', 'uploads/covers/069-rakhim-habibi.jpg', 93),
  ('PUNK TACTICS', 'Joey Valence & Brae', 'Hip-Hop', 'hype', 0, 'punk,energy,bars', 'uploads/songs/070-joey-valence-punk-tactics.mp3', 'uploads/covers/070-joey-valence-punk-tactics.jpg', 144),
  ('Baby By Me (feat. Ne-Yo)', '50 Cent', 'Hip-Hop', 'hype', 1, 'rap,trap,energy', 'uploads/songs/071-50-cent-baby-by-me-feat-ne-yo.mp3', 'uploads/covers/071-50-cent-baby-by-me-feat-ne-yo.jpg', 214),
  ('Yo Bunny', 'prodbycpkshawn, Ugly Andz', 'Pop', 'happy', 1, 'dance,latin,party', 'uploads/songs/072-prodbycpkshawn-yo-bunny.mp3', 'uploads/covers/072-prodbycpkshawn-yo-bunny.jpg', 111),
  ('Runway Walk (Bonus) (feat. Brevi)', 'Demrick', 'Pop', 'happy', 1, 'upbeat,fun,party', 'uploads/songs/073-demrick-runway-walk-bonus-feat-brevi.mp3', 'uploads/covers/073-demrick-runway-walk-bonus-feat-brevi.jpg', 191),
  ('Harder, Better, Faster, Stronger', 'Daft Punk', 'Electronic', 'hype', 0, 'daftpunk,dance,classic', 'uploads/songs/074-daft-punk-harder-better-faster-stronger.mp3', 'uploads/covers/074-daft-punk-harder-better-faster-stronger.jpg', 227),
  ('Music Sounds Better With You', 'Stardust', 'Electronic', 'happy', 0, 'dance,classic,house', 'uploads/songs/075-stardust-music-sounds-better-with-you.mp3', 'uploads/covers/075-stardust-music-sounds-better-with-you.jpg', 404),
  ('Kilby Girl', 'The Backseat Lovers', 'Rock', 'happy', 0, 'indie_rock,upbeat,energy', 'uploads/songs/076-the-backseat-lovers-kilby-girl.mp3', 'uploads/covers/076-the-backseat-lovers-kilby-girl.jpg', 283),
  ('X Gon'' Give It To Ya', 'DMX', 'Hip-Hop', 'hype', 1, 'rap,dark,energy', 'uploads/songs/077-dmx-x-gon-give-it-to.mp3', 'uploads/covers/077-dmx-x-gon-give-it-to.jpg', 220),
  ('REC2 FUNK', 'NostalgicRedux', 'Electronic', 'hype', 0, 'phonk,dance,energy', 'uploads/songs/078-nostalgicredux-rec2-funk.mp3', 'uploads/covers/078-nostalgicredux-rec2-funk.jpg', 157),
  ('REC1 FUNK', 'Djx33', 'Electronic', 'hype', 0, 'phonk,dance,energy', 'uploads/songs/079-djx33-rec1-funk.mp3', 'uploads/covers/079-djx33-rec1-funk.jpg', 111),
  ('MONTAGEM SANTA FE 2', 'qaraqshy', 'Electronic', 'happy', 0, 'funk,dance,party', 'uploads/songs/080-qaraqshy-montagem-santa-fe-2.mp3', 'uploads/covers/080-qaraqshy-montagem-santa-fe-2.jpg', 106),
  ('Army Dreamers', 'Kate Bush', 'Indie', 'chill', 0, 'folk,soft,classic', 'uploads/songs/081-kate-bush-army-dreamers.mp3', 'uploads/covers/081-kate-bush-army-dreamers.jpg', 179),
  ('Stronger', 'Kanye West', 'Hip-Hop', 'hype', 1, 'kanye,classic,energy', 'uploads/songs/082-kanye-west-stronger.mp3', 'uploads/covers/082-kanye-west-stronger.jpg', 312),
  ('INSONAMIA (Slowed)', 'AlanAli', 'Electronic', 'chill', 0, 'phonk,slowed,late_night', 'uploads/songs/083-alanali-insonamia-slowed.mp3', 'uploads/covers/083-alanali-insonamia-slowed.jpg', 204),
  ('Sexyback', 'POODEE', 'Pop', 'happy', 0, 'dance,fun,upbeat', 'uploads/songs/084-poodee-sexyback.mp3', 'uploads/covers/084-poodee-sexyback.jpg', 176),
  ('Push The Feeling On (Slowed+Reverb)', 'Zarazzie', 'Electronic', 'chill', 0, 'slowed,reverb,dreamy', 'uploads/songs/085-zarazzie-push-the-feeling-on-slowedreverb.mp3', 'uploads/covers/085-zarazzie-push-the-feeling-on-slowedreverb.jpg', 275),
  ('Love Story', 'Indila', 'Pop', 'happy', 0, 'love,upbeat,french', 'uploads/songs/086-indila-love-story.mp3', 'uploads/covers/086-indila-love-story.jpg', 317),
  ('Born For Greatness', 'Papa Roach', 'Rock', 'hype', 0, 'motivational,energy,anthem', 'uploads/songs/087-papa-roach-born-for-greatness.mp3', 'uploads/covers/087-papa-roach-born-for-greatness.jpg', 228),
  ('FALL FROM THE SKY PT. 2 (SLOWED)', 'RomancePlanet, STAKILLAZ', 'Electronic', 'chill', 1, 'phonk,slowed,dark', 'uploads/songs/088-romanceplanet-fall-from-the-sky-pt.mp3', 'uploads/covers/088-romanceplanet-fall-from-the-sky-pt.jpg', 130),
  ('Mundian To Bach Ke', 'Panjabi MC', 'Punjabi', 'hype', 0, 'bhangra,classic,energy', 'uploads/songs/089-panjabi-mc-mundian-to-bach-ke.mp3', 'uploads/covers/089-panjabi-mc-mundian-to-bach-ke.jpg', 246),
  ('Hulara', 'J Star', 'Punjabi', 'hype', 0, 'bhangra,dance,energy', 'uploads/songs/090-j-star-hulara.mp3', 'uploads/covers/090-j-star-hulara.jpg', 214),
  ('Angelina', 'Raghav', 'Punjabi', 'happy', 0, 'bhangra,dance,throwback', 'uploads/songs/091-raghav-angelina.mp3', 'uploads/covers/091-raghav-angelina.jpg', 291),
  ('Angreji Beat', 'Gippy Grewal, Yo Yo Honey Singh', 'Punjabi', 'hype', 0, 'bhangra,dance,party', 'uploads/songs/092-gippy-grewal-angreji-beat.mp3', 'uploads/covers/092-gippy-grewal-angreji-beat.jpg', 256),
  ('ExtraL', 'JENNIE, Doechii', 'Pop', 'happy', 1, 'kpop,dance,party', 'uploads/songs/093-jennie-extral.mp3', 'uploads/covers/093-jennie-extral.jpg', 168),
  ('MEIA NOITE - Slowed', 'OTVphonk, ZERTAL', 'Electronic', 'chill', 1, 'phonk,slowed,late_night', 'uploads/songs/094-otvphonk-meia-noite-slowed.mp3', 'uploads/covers/094-otvphonk-meia-noite-slowed.jpg', 71),
  ('Heartbeat', 'Childish Gambino', 'R&B', 'happy', 1, 'love,smooth,upbeat', 'uploads/songs/095-childish-gambino-heartbeat.mp3', 'uploads/covers/095-childish-gambino-heartbeat.jpg', 270),
  ('My Old Ways', 'Tame Impala', 'Indie', 'chill', 1, 'psychedelic,dreamy,soft', 'uploads/songs/096-tame-impala-my-old-ways.mp3', 'uploads/covers/096-tame-impala-my-old-ways.jpg', 299),
  ('if god didnt want us to snort worms he wouldn''t have made them cylindrical!', 'MIMIDEATH', 'Electronic', 'happy', 0, 'weird,fun,meme', 'uploads/songs/097-mimideath-if-god-didnt-want-us.mp3', 'uploads/covers/097-mimideath-if-god-didnt-want-us.jpg', 59),
  ('I WANNA BE YOUR SLAVE', 'Måneskin', 'Rock', 'hype', 1, 'italian_rock,energy,anthem', 'uploads/songs/098-mneskin-i-wanna-be-your-slave.mp3', 'uploads/covers/098-mneskin-i-wanna-be-your-slave.jpg', 174),
  ('SEQUÊNCIA DO HOMEM-ARANHA', 'ÉoDan, Cavalheiro', 'Electronic', 'happy', 1, 'funk,dance,party', 'uploads/songs/099-odan-sequncia-do-homem-aranha.mp3', 'uploads/covers/099-odan-sequncia-do-homem-aranha.jpg', 169),
  ('Hear My Hope (feat. Alex Newell)', 'Hazbin Hotel, Shoba Narayan, Erika Henningsen, Keith David', 'Pop', 'happy', 1, 'musical,upbeat,feel_good', 'uploads/songs/100-hazbin-hotel-hear-my-hope-feat-alex.mp3', 'uploads/covers/100-hazbin-hotel-hear-my-hope-feat-alex.jpg', 310),
  ('XLOWLY (SLOWED)', 'NY8', 'Electronic', 'chill', 0, 'phonk,slowed,late_night', 'uploads/songs/101-ny8-xlowly-slowed.mp3', 'uploads/covers/101-ny8-xlowly-slowed.jpg', 93);