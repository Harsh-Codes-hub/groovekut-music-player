# GrooveKut — Phase 1 Setup

## XAMPP Setup (Phase 0)

1. Start **Apache** + **MySQL** in XAMPP Control Panel
2. Open phpMyAdmin → `http://localhost/phpmyadmin`
3. Create database: `groovekut_db` (utf8mb4_unicode_ci)
4. Import `groovekut_schema.sql` via phpMyAdmin → Import tab
5. Confirm 6 tables exist: `users`, `songs`, `play_history`, `liked_songs`, `playlists`, `mood_log`
6. Place this `/groovekut/` folder inside `C:\xampp\htdocs\`
7. Visit `http://localhost/groovekut/` → redirects to login (Phase 2)

---

## File Naming Convention

**Format:** `{id}-{artist-slug}-{title-slug}.ext`

**Rules:**
- Lowercase only, hyphens only — no spaces, no underscores
- Artist first, then title
- 3-digit leading ID matching DB row (`001`, `002`, `003`...)
- Strip all special chars: `'` `,` `&` `(` `)` `.`
- Cut long titles at 4–5 words

**Example:**
```
006-dua-lipa-dont-start-now.mp3   → uploads/songs/
006-dua-lipa-dont-start-now.jpg   → uploads/covers/
```

Same name, different extension, different folder. Always.

---

## Folder Structure

```
/groovekut/
  index.php                     ✅ Phase 1 — entry + redirect
  dashboard.php                    Phase 2
  player.php                       Phase 3
  recommendations.php              Phase 4
  search.php                       Phase 5
  library.php                      Phase 5
  profile.php                      Phase 5
  groovekut_schema.sql          ✅ Phase 1 — run once in phpMyAdmin

  auth/
    login.php                      Phase 2
    register.php                   Phase 2
    logout.php                     Phase 2

  api/
    log_play.php                   Phase 3
    session_check.php              Phase 3
    mood.php                       Phase 4
    rec_engine.php                 Phase 4
    like.php                       Phase 5
    onboarding_save.php            Phase 2

  uploads/
    songs/        ← drop .mp3 files here  (001-artist-title.mp3)
    covers/       ← drop .jpg files here  (001-artist-title.jpg)

  assets/
    css/
      style.css                    Phase 6
    js/
      player.js                    Phase 3
      gate.js                      Phase 3
      recs.js                      Phase 4

  includes/
    db_connect.php              ✅ Phase 1 — DB connection
    session_helper.php          ✅ Phase 1 — auth helpers + onboarding check
    header.php                     Phase 2
    footer.php                     Phase 2

  admin/
    index.php                      Phase 5 — admin login
    dashboard.php                  Phase 5 — song list + stats
    add_song.php                   Phase 5 — upload form
```

---

## What's built (Phase 1)

| File | What it does |
|---|---|
| `groovekut_schema.sql` | All 6 tables + seed songs. `users` has `preferred_genres` (JSON) and `onboarding_done` (tinyint) columns |
| `includes/db_connect.php` | MySQLi connection, utf8mb4 |
| `includes/session_helper.php` | `is_logged_in()`, `require_login()`, `current_user_id()`, `current_mood()`, `needs_onboarding()`, cookie auto-login |
| `index.php` | Cookie check → session restore → redirect to dashboard or login |

---

## Phase Checklist

- [x] Phase 0 — XAMPP setup
- [x] Phase 1 — Folder structure + DB schema + db_connect + index.php
- [ ] Phase 2 — Auth (login, register, logout, cookie, onboarding modal)
- [ ] Phase 3 — Song library + HTML5 player + play logging
- [ ] Phase 4 — Recommendation engine + mood picker
- [ ] Phase 5 — Likes, auto-playlists, profile, admin panel
- [ ] Phase 6 — UI polish (dark theme, #378ADD accents)

---

## Future Development (report section)

1. Custom playlist management with drag-and-drop
2. Social features — follow users, shared playlists
3. Lyrics via Genius API, synced to playback
4. 5-band equalizer via Web Audio API
5. Artist profile pages with discography
6. User music uploads
7. Browser push notifications
8. PWA + offline mode with service worker
9. Cloud deployment + cross-device sync
10. React Native mobile app (GrooveKut v2)
  