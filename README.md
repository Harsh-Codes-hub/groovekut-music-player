# GrooveKut — Setup Guide 🎵

> First time setting this up? This guide gets you from zero to running in about 10 minutes.

---

## What You Need

- A Windows PC (this runs on XAMPP)
- The GrooveKut project folder (this repo)
- That's it

---

## Step 1 — Install XAMPP

If you don't have XAMPP yet:

1. Go to `https://www.apachefriends.org` and download XAMPP for Windows
2. Run the installer, keep all defaults, install it
3. Open **XAMPP Control Panel** (it's in your start menu or `C:\xampp\xampp-control.exe`)
4. Click **Start** next to **Apache** and **MySQL** — both should turn green

> If Apache won't start, something else is using port 80 (usually Skype or IIS). Fix: in XAMPP Control Panel → Apache → Config → httpd.conf → change `Listen 80` to `Listen 8080`. Then visit `http://localhost:8080/groovekut/` instead.

---

## Step 2 — Put the Project in the Right Place

Take the `groovekut` folder and drop it here:

```
C:\xampp\htdocs\groovekut\
```

So the structure looks like:

```
C:\xampp\htdocs\groovekut\
  index.php
  dashboard.php
  player.php
  ...
```

---

## Step 3 — Set Up the Database

1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar
3. Name it `groovekut_db`
4. Set collation to `utf8mb4_unicode_ci`
5. Click **Create**
6. Now click on `groovekut_db` in the left sidebar to select it
7. Click the **Import** tab at the top
8. Click **Choose File** → find `groovekut_schema.sql` inside the project folder
9. Click **Go** at the bottom

You should see a success message. The 6 tables are now created.

---

## Step 4 — Add Songs and Covers

The project needs actual `.mp3` and `.jpg` files to work. Drop them here:

```
groovekut/uploads/songs/    ← .mp3 files
groovekut/uploads/covers/   ← .jpg files
```

**File naming is strict** — every file must follow this format:

```
{id}-{artist-slug}-{title-slug}.mp3
{id}-{artist-slug}-{title-slug}.jpg
```

Example:
```
006-dua-lipa-dont-start-now.mp3
006-dua-lipa-dont-start-now.jpg
```

Rules:
- Lowercase only
- Hyphens instead of spaces
- 3-digit ID that matches the song's row ID in the database
- First/main artist only
- No special characters

> The easiest way to add songs is through the admin panel (Step 6) — it handles the renaming automatically.

---

## Step 5 — Open the Site

Go to `http://localhost/groovekut/` in your browser.

You should see the GrooveKut home page. If you see a blank page or error, check:

- Apache and MySQL are both green in XAMPP
- The folder is named exactly `groovekut` (not `groovekut-main` or `GrooveKut`)
- The database was imported successfully in Step 3

---

## Step 6 — Add Songs via Admin Panel

Instead of manually naming and dropping files, use the built-in admin panel:

1. Go to `http://localhost/groovekut/admin/`
2. Log in with the admin credentials
3. Click **Add Song**
4. Fill in the song details and upload the `.mp3` and cover `.jpg`
5. The panel auto-renames the files to the correct slug format

> First time? The admin account details are set directly in the database. Open phpMyAdmin → `groovekut_db` → `admins` table → insert a row with your username and a bcrypt-hashed password.

---

## Step 7 — Create a User Account

1. Go to `http://localhost/groovekut/auth/register.php`
2. Sign up with a username, email, and password
3. On first login, pick up to 3 genres you like
4. Start listening

---

## Common Issues

**Page shows "Connection failed"**
→ MySQL isn't running. Open XAMPP Control Panel and start it.

**Images not loading**
→ Check that cover files are in `uploads/covers/` and named correctly.

**Audio not playing**
→ Check that mp3 files are in `uploads/songs/` and named correctly. Also check browser console for the file path.

**Recommendations page is empty**
→ Normal for new accounts. Play at least 5 songs first — the engine needs data.

**Admin panel login not working**
→ The admin account needs to be created manually in phpMyAdmin. See Step 6 above.

**Fonts or icons look broken**
→ Make sure `assets/fonts/` contains `Sora-Variable.woff2`, `Inter-Variable.woff2`, `remixicon.woff2`, and `remixicon.css`. These are local files, not CDN — they must be present.

---

## Quick Checklist

- [ ] XAMPP installed, Apache + MySQL running
- [ ] `groovekut` folder in `C:\xampp\htdocs\`
- [ ] `groovekut_db` database created with `utf8mb4_unicode_ci`
- [ ] `groovekut_schema.sql` imported successfully
- [ ] Font files present in `assets/fonts/`
- [ ] At least a few songs + covers in `uploads/`
- [ ] Site opens at `http://localhost/groovekut/`

If all boxes are checked, you're good. Enjoy the music. 🎧