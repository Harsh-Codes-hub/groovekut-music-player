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
| Icons | Remix Icons (local) |
| Fonts | Inter (body) + Sora (display) — local via woff2 |

---

## Quick Setup

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
  dashboard.php                 ✅ Home — guest/new user/returning user states
  player.php                    ✅ Full page player — YouTube Music style
  recommendations.php           ✅ Personalised rec page with mood switcher
  search.php                    ✅ Search + genre/mood filters — guest accessible
  library.php                   ✅ Liked songs, genre playlists, most played
  profile.php                   ✅ Stats, history, badges

  auth/
    login.php                   ✅ Login — username or email, remember-me cookie
    register.php                ✅ Register — validation, auto-login after
    logout.php                  ✅ Logout — clears session + cookie + DB token

  api/
    onboarding_save.php         ✅ Saves genre picks, sets onboarding_done = 1
    log_play.php                ✅ Logs play after 10s, increments play_count
    like.php                    ✅ Heart toggle — inserts/deletes liked_songs
    session_check.php           ✅ Guest gate — play wall for non-logged users
    get_queue.php               ✅ Queue builder — locked playlist / smart / random
    mood.php                    ✅ Mood API — GET current, POST to set + log
    rec_engine.php              ✅ Scoring engine — play×1, liked×3, mood×2, genre×1.5

  admin/
    index.php                   ✅ Admin login
    dashboard.php               ✅ Song list + play stats
    add_song.php                ✅ Upload form — auto-rename to slug format
    logout.php                  ✅ Admin logout

  uploads/
    songs/                      ← drop .mp3 files here
    covers/                     ← drop .jpg files here

  assets/
    css/
      style.css                 ✅ Full dark theme, CSS variables, all components
    js/
      player.js                 ✅ Full player logic — controls, queue, visualizer
    fonts/
      Sora-Variable.woff2       ✅ Display font — local
      Inter-Variable.woff2      ✅ Body font — local
      remixicon.woff2           ✅ Icons — local
      remixicon.css             ✅ Icon styles — local

  includes/
    db_connect.php              ✅ MySQLi connection, utf8mb4
    session_helper.php          ✅ Auth helpers + cookie auto-login
    header.php                  ✅ Sticky navbar, local fonts/icons, active states
    footer.php                  ✅ Shared close tags
```

---

## Features

### Auth + Onboarding
- Register + login (username or email)
- bcrypt password hashing
- Remember me cookie (30 days)
- Auto-login from cookie on return visit
- First-login onboarding modal — genre picker up to 3 genres

### Dashboard
- Three states — guest (browse + starter songs), new user, returning user (mood rows + recently played + recs banner)
- Starter songs seeded by user ID or genre preference
- Mood badge in hero with dropdown picker

### Player
- Full page YouTube Music style layout
- Canvas color extraction → per-song radial gradient background
- Web Audio API visualizer — frequency bars
- Controls: play/pause, prev/next, seek, volume, mute
- Loop: Off → All → One
- Shuffle — current song pinned to front
- Autoplay — advances through queue, stops at end (no wrap)
- Smart Queue — genre+mood filtered vs full library random
- Locked Playlist mode — from library, queue stays locked to that playlist
- Queue panel — scrollable, active song highlighted
- Play logged after 10 seconds
- Like/unlike heart toggle
- Guest gate modal on play attempt
- Navbar now-playing indicator — pulsing dot, clickable
- Keyboard shortcuts: Space, ←→ (seek ±10s), ↑↓ (volume), N, P

### Library
- Liked Songs playlist — locked queue
- Top 3 genre playlists by play count — locked queue
- Most Played top 20 — locked queue

### Search
- Text search by title or artist
- Genre + mood filter pills
- Guest accessible — no login required to browse

### Recommendations
- Weighted scoring: play_count ×1, liked ×3, mood match ×2, genre match ×1.5
- Top 20 scored songs with rank badges + signal indicators (♥ ◉ ▣)
- Score bar per card
- Skeleton loading state
- Mood switcher — switch mood, recs refresh instantly
- Guest state — sign up prompt instead of redirect

### Profile
- Stats: total plays, songs played, liked count, top genre
- Earned badges — genre fan, mood listener, 100+ plays
- Recently played history (last 20)
- Preferred genres from onboarding

### Admin Panel
- Separate login (not linked to user accounts)
- Song list with play stats
- Upload form — auto-renames files to slug format

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
| Body font | Inter (variable) |
| Display font | Sora (variable) |
| Icons | Remix Icons (`ri-*`) — local |

---

## Phase Checklist

- [x] Phase 0 — XAMPP setup
- [x] Phase 1 — DB schema + folder structure + db_connect + index.php
- [x] Phase 2 — Auth system + onboarding + dashboard + CSS design system
- [x] Phase 3 — Full player + queue engine + play logging + like system
- [x] Phase 4 — Recommendation engine + mood picker
- [x] Phase 5 — Library + profile + search + admin panel
- [x] Phase 6 — UI polish, offline fonts/icons, mobile responsive, guest access

---

## Future Development

1. Custom playlist management with drag-and-drop reordering
2. Social features — follow users, shared playlists
3. Lyrics sync via LRCLib API
4. 5-band equalizer via Web Audio API
5. Artist profile pages with bio, discography
6. Normalized artist management — dedicated `artists` table
7. Auto-generated playlist covers — 4 song covers stitched 2×2 (Canvas API)
8. User music uploads
9. Browser push notifications — new songs in favourite genre
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
Built for a college client. All 6 phases complete. ❤️‍🔥