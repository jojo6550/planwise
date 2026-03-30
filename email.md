  To configure email, open .env and replace the Mailtrap placeholder values with your actual credentials. Options:
  - Mailtrap (easiest for dev, free): sign up at mailtrap.io → go to your inbox → SMTP settings → copy host/port/username/password   
  - Gmail: use smtp.gmail.com, port 587, your Gmail, and a https://myaccount.google.com/apppasswords (2FA required)

 ---
  Technical Manual: Deploying PlanWise on Render

  Important Note — MySQL on Render

  Render's managed database service only supports PostgreSQL. For MySQL you need an external provider. This guide uses
  FreeSQLDatabase.com — completely free, no credit card, works immediately.

  ▎ Free tier warning: Render's free web service spins down after 15 minutes of inactivity and takes ~30 seconds to wake up on the   
  next request. For a live presentation, open your site 2–3 minutes before presenting to wake it up.

  ---
  Prerequisites

  - GitHub account with your PlanWise repo pushed
  - Render account — sign up at render.com (use GitHub login)

  ---
  Part 1 — Set Up the MySQL Database

  Step 1.1 — Create a free MySQL database

  1. Go to freesqldatabase.com
  2. Click Sign Up for Free
  3. Fill in the form (use your email)
  4. Check your email and click the confirmation link
  5. You'll receive a second email with your database credentials — save these, you'll need them:

  Host:     sql.freedb.tech
  Port:     3306
  Database: freedb_YOUR_USERNAME
  Username: freedb_YOUR_USERNAME
  Password: (in the email)

  Step 1.2 — Connect with a GUI (TablePlus or DBeaver)

  Open TablePlus (or DBeaver) and create a new MySQL connection:

  ┌──────────┬─────────────────┐
  │  Field   │      Value      │
  ├──────────┼─────────────────┤
  │ Host     │ sql.freedb.tech │
  ├──────────┼─────────────────┤
  │ Port     │ 3306            │
  ├──────────┼─────────────────┤
  │ Database │ from your email │
  ├──────────┼─────────────────┤
  │ User     │ from your email │
  ├──────────┼─────────────────┤
  │ Password │ from your email │
  └──────────┴─────────────────┘

  Click Test — it should say Connected.

  Step 1.3 — Import your schema

  Before importing, open database/schema.sql and remove the INSERT INTO rows for these tables (they contain personal data and Windows
   paths):

  - qr_codes — stores Windows absolute paths, will break on Linux
  - activity_logs — contains your real login history and email
  - password_resets — contains real tokens

  Keep the INSERT INTO roles rows (required) and optionally keep lesson_plans for demo data.

  In TablePlus:
  1. Select your database
  2. File → Import → From SQL File
  3. Select database/schema.sql
  4. Click Import

  Verify all 8 tables were created: activity_logs, files, lesson_plans, lesson_sections, password_resets, qr_codes, roles, users.    

  Step 1.4 — Create your admin account

  Run this SQL to create your login (replace with your details):

  INSERT INTO `users` (`first_name`, `last_name`, `email`, `password_hash`, `role_id`, `status`)
  VALUES (
      'Your',
      'Name',
      'your@email.com',
      '$2y$10$iZYvlSig9fVl1N2FU.KFMu7SRZTY8HyI66fzdv1zBTpOKEABCVaUi',
      1,
      'active'
  );

  ▎ The password hash above matches your existing password from the original schema. To set a new password, generate a bcrypt hash at
   bcrypt-generator.com (cost factor 10).

  ---
  Part 2 — Create the Render Web Service

  Step 2.1 — New Web Service

  1. Log in to dashboard.render.com
  2. Click New + → Web Service
  3. Select Build and deploy from a Git repository
  4. Click Connect next to your GitHub account if not already connected
  5. Find your planwise repository and click Connect

  Step 2.2 — Configure the service

  Fill in the settings:

  ┌───────────────┬─────────────────────────────────────────────┐
  │     Field     │                    Value                    │
  ├───────────────┼─────────────────────────────────────────────┤
  │ Name          │ planwise                                    │
  ├───────────────┼─────────────────────────────────────────────┤
  │ Region        │ Choose closest to your location             │
  ├───────────────┼─────────────────────────────────────────────┤
  │ Branch        │ main                                        │
  ├───────────────┼─────────────────────────────────────────────┤
  │ Runtime       │ Docker (auto-detected from your Dockerfile) │
  ├───────────────┼─────────────────────────────────────────────┤
  │ Instance Type │ Free                                        │
  └───────────────┴─────────────────────────────────────────────┘

  Leave all other fields at their defaults.

  Step 2.3 — Set environment variables

  Scroll down to Environment Variables and add each one:

  ┌─────────────────┬─────────────────────────────────────────────────────┐
  │       Key       │                        Value                        │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ APP_URL         │ Leave blank for now — you'll fill this after deploy │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ DB_HOST         │ sql.freedb.tech                                     │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ DB_PORT         │ 3306                                                │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ DB_NAME         │ your database name from Step 1.1                    │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ DB_USER         │ your database username from Step 1.1                │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ DB_PASS         │ your database password from Step 1.1                │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ MAIL_DRIVER     │ smtp                                                │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ MAIL_HOST       │ smtp.gmail.com                                      │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ MAIL_PORT       │ 587                                                 │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ MAIL_ENCRYPTION │ tls                                                 │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ MAIL_FROM_NAME  │ PlanWise                                            │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ MAIL_USERNAME   │ your Gmail address                                  │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ MAIL_PASSWORD   │ your Gmail App Password                             │
  ├─────────────────┼─────────────────────────────────────────────────────┤
  │ MAIL_FROM_EMAIL │ your Gmail address                                  │
  └─────────────────┴─────────────────────────────────────────────────────┘

  ▎ Gmail App Password: Go to myaccount.google.com → Security → 2-Step Verification → App Passwords → generate one for "Mail".       

  Click Create Web Service. Render starts building immediately — the first build takes 4–6 minutes.

  ---
  Part 3 — Post-Deploy Configuration

  Step 3.1 — Get your live URL

  Once the build succeeds and the health check passes, your service page shows a URL at the top:

  https://planwise.onrender.com

  (Render may add a suffix like planwise-xxxx if the name is taken.)

  Step 3.2 — Update APP_URL

  1. In your Render service → Environment tab
  2. Find APP_URL and set it to your live URL with a trailing slash:
  https://planwise.onrender.com/
  3. Click Save Changes — Render redeploys automatically

  Step 3.3 — Verify the deployment

  ┌────────────────────┬──────────────────────────────────────────────────┬─────────────────────────┐
  │        Test        │                       How                        │        Expected         │
  ├────────────────────┼──────────────────────────────────────────────────┼─────────────────────────┤
  │ Landing page       │ Visit your Render URL                            │ New landing page loads  │
  ├────────────────────┼──────────────────────────────────────────────────┼─────────────────────────┤
  │ Login              │ Click Login → use your credentials from Step 1.4 │ Teacher/Admin dashboard │
  ├────────────────────┼──────────────────────────────────────────────────┼─────────────────────────┤
  │ Create lesson plan │ Teacher dashboard → New Plan                     │ Saved successfully      │
  ├────────────────────┼──────────────────────────────────────────────────┼─────────────────────────┤
  │ Export PDF         │ View a lesson plan → Export PDF                  │ PDF downloads           │
  ├────────────────────┼──────────────────────────────────────────────────┼─────────────────────────┤
  │ Generate QR        │ View a lesson plan → Generate QR                 │ QR image appears        │
  ├────────────────────┼──────────────────────────────────────────────────┼─────────────────────────┤
  │ Scan QR            │ Scan QR with phone                               │ PDF opens in browser    │
  └────────────────────┴──────────────────────────────────────────────────┴─────────────────────────┘

  ---
  Part 4 — Ongoing Deployments

  Every git push to main triggers an automatic redeploy on Render:

  git add .
  git commit -m "Your change"
  git push

  You can monitor the build live in Render dashboard → your service → Events tab.

  ---
  Troubleshooting

  "Database connection failed" on first visit
  - Check your FreeSQLDatabase credentials are entered correctly in Render's environment variables
  - Make sure the schema was imported (connect via TablePlus and verify the tables exist)
  - FreeSQLDatabase may take 60 seconds to accept connections on a cold start

  Build fails with Composer error
  - Check the build logs in Render → Events tab → click the failed deploy
  - Most common cause: composer.lock out of sync — run composer update --ignore-platform-reqs locally and push the updated lock file 

  Health check fails (service unavailable)
  - Check Render logs → Logs tab for PHP errors
  - Verify all environment variables are set (a missing DB_HOST will cause a 500 error)

  Site loads but login fails
  - Re-check that the users table has your account (connect to FreeSQLDatabase via TablePlus)
  - Make sure the password hash in Step 1.4 matches your intended password

  QR codes work locally but not on Render
  - Ensure APP_URL is set to your Render URL (not localhost)
  - Regenerate QR codes after updating APP_URL — old ones still encode localhost

  Free tier cold start during presentation
  - Open your site URL 2–3 minutes before presenting
  - Render wakes the service on the first request; it takes ~30 seconds
  - Once awake it stays awake as long as there's traffic

✻ Baked for 2m 0s

──────────────────────────────────────────────────────────────────────────────────────────────────────── fix-qr-codes-landing-page ──
❯  
─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
  ⏵⏵ accept edits on (shift+tab to cycle)                                Update available! Run: winget upgrade Anthropic.ClaudeCode  
