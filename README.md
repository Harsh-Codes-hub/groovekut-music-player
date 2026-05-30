# GrooveKut 🎵

A smart music recommendation player built for a college client. Discover music, get personalised recommendations, and vibe — the more you listen, the smarter it gets.

---

## Stack

| Layer | Tech |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP (no frameworks) |
| Database | MySQL via phpMyAdmin |
| Local Server | XAMPP (Apache + MySQL) |
| Icons | Remix Icons CDN |
| Fonts | Inter (body) + Space Grotesk (display) |

---

## Setup

1. Start **Apache** + **MySQL** in XAMPP Control Panel
2. Open `http://localhost/phpmyadmin`
3. Create database: `groovekut_db` (utf8mb4_unicode_ci)
4. Import `groovekut_schema.sql` via Import tab
5. Place `/groovekut/` folder inside `C:\xampp\htdocs\`
6. Visit `http://localhost/groovekut/`

---

## File Naming Convention

Songs and covers follow a strict slug format:

```
{id}-{artist-slug}-{title-slug}.mp3   → uploads/songs/
{id}-{artist-slug}-{title-slug}.jpg   → uploads/covers/
```

**Rules:** lowercase only, hyphens only, first/main artist, 3-digit ID matching DB row, strip all special chars.

**Example:**
```
006-dua-lipa-dont-start-now.mp3
006-dua-lipa-dont-start-now.jpg
```

---

## Folder Structure

```
/groovekut/
  index.php                     ✅ Entry point — session check + redirect
  dashboard.php                 ✅ Home — two states (new user / returning)
  player.php                    ✅ Full page player — YouTube Music style
  recommendations.php              Phase 4
  search.php                       Phase 5
  library.php                      Phase 5
  profile.php                      Phase 5
  groovekut_schema.sql          ✅ Run once in phpMyAdmin

  auth/
    login.php                   ✅ Login — username or email, remember-me cookie
    register.php                ✅ Register — validation, auto-login after
    logout.php                  ✅ Logout — clears session + cookie + DB token

  api/
    onboarding_save.php         ✅ Saves genre picks, sets onboarding_done = 1
    log_play.php                ✅ Logs play after 10s, increments play_count
    like.php                    ✅ Heart toggle — inserts/deletes liked_songs
    session_check.php           ✅ Guest gate — 1 free play then login wall
    get_queue.php               ✅ Builds queue — smart (genre+mood) or all songs
    mood.php                       Phase 4
    rec_engine.php                 Phase 4

  uploads/
    songs/                      ← drop .mp3 files here
    covers/                     ← drop .jpg files here

  assets/
    css/
      style.css                 ✅ Full dark theme, CSS variables, all components
    js/
      player.js                 ✅ Full player logic — controls, queue, visualizer

  includes/
    db_connect.php              ✅ MySQLi connection, utf8mb4
    session_helper.php          ✅ Auth helpers + cookie auto-login
    header.php                  ✅ Sticky navbar, Remix Icons, active states
    footer.php                  ✅ Shared close tags

  admin/
    index.php                      Phase 5 — admin login
    dashboard.php                  Phase 5 — song list + play stats
    add_song.php                   Phase 5 — upload form, auto-rename
```

---

## Features Built

### Auth
- Register + login (username or email)
- bcrypt password hashing
- Remember me cookie (30 days)
- Auto-login from cookie on return visit
- First-login onboarding modal (genre picker — up to 3 genres)

### Dashboard
- Two states — new user (browse + starter songs) vs returning user (mood rows + recently played + recs banner)
- Starter songs stable per user — seeded by user ID or genre preference
- Browse by genre cards
- Mood badge in hero

### Player
- Full page YouTube Music style
- Cover art → Canvas color extraction → per-song radial gradient background
- Web Audio API visualizer — frequency bars behind UI
- Mood fallback gradients (happy=amber, chill=blue, hype=red, sad=purple, focus=teal)
- Controls: play/pause, prev/next, seek bar, volume, mute
- Loop: Off → All → One
- Shuffle — current song pinned to position 0
- Autoplay toggle
- Smart Queue toggle — genre+mood filtered (101 songs) vs full library random
- Queue panel — scrollable, active song highlighted, auto-scrolls to current
- Play logged to DB after 10 seconds
- Like/unlike heart toggle
- Guest gate modal after 1 free play
- Navbar now-playing indicator — pulsing dot + song title, clickable
- Keyboard shortcuts: Space (play/pause), ←→ (seek ±10s), ↑↓ (volume), N (next), P (prev)

---

## Database — 6 Tables

| Table | Purpose |
|---|---|
| `users` | Accounts — username, email, bcrypt hash, preferred_genres, onboarding_done, remember_token |
| `songs` | Master library — title, artist, genre, mood_tag, is_explicit, file_path, cover_path, duration |
| `play_history` | Every play logged — user_id, song_id, play_count, played_at |
| `liked_songs` | Heart clicks — user_id, song_id, liked_at |
| `playlists` | Auto-generated playlists — mood, genre, liked types |
| `mood_log` | Every mood pick — user_id, mood, logged_at |

---

## Design System

| Token | Value |
|---|---|
| Background | `#0e0e0e` |
| Surface | `#161616` / `#1f1f1f` / `#2a2a2a` |
| Accent | `#378ADD` |
| Danger | `#e05c5c` |
| Success | `#4caf82` |
| Body font | Inter |
| Display font | Space Grotesk |
| Icons | Remix Icons (`ri-*`) |

---

## Phase Checklist

- [x] Phase 0 — XAMPP setup
- [x] Phase 1 — DB schema + folder structure + db_connect + index.php
- [x] Phase 2 — Auth system + onboarding + dashboard + CSS design system
- [x] Phase 3 — Full player + queue engine + play logging + like system
- [ ] Phase 4 — Recommendation engine + mood picker
- [ ] Phase 5 — Library + profile + search + admin panel
- [ ] Phase 6 — UI polish pass

---

## Future Development

1. Custom playlist management with drag-and-drop reordering
2. Social features — follow users, shared playlists
3. Lyrics sync via LRCLib API
4. 5-band equalizer via Web Audio API
5. Artist profile pages with bio, discography, cover art
6. Normalized artist management — dedicated `artists` table, many-to-many with songs
7. Auto-generated playlist covers — 4 song covers stitched 2×2 (Canvas API)
8. User music uploads
9. Browser push notifications — "New songs in your favourite genre"
10. PWA + offline mode with service worker
11. Cloud deployment + cross-device sync
12. React Native mobile app (GrooveKut v2)
13. Persistent bottom bar player (Spotify-style)
14. Crossfade + gapless playback
15. Sleep timer + playback speed control

---

## Author

**Frag the Architect**
Solo indie developer — Rajasthan, India
Built for a college client. Phase 3 approved by client. ❤️‍🔥
