# TiredOfDoinTM - Complete Setup Checklist

## Your VPS Details
- **IP Address:** 164.92.99.233
- **SSH Login:** `ssh root@164.92.99.233` (password: your password)
- **Website URL (after setup):** https://tiredofdointm.com
- **Admin Panel:** https://tiredofdointm.com/dashboard/server

---

## PART 1: Gather These Credentials First (Do This Before SSH)

### 1.1 PostgreSQL Database (DigitalOcean)
Go to: https://cloud.digitalocean.com/databases
- Create a new PostgreSQL database (or use existing)
- Get these values:
  - [ ] Host: `your-db-xxxxx.db.ondigitalocean.com`
  - [ ] Port: `25060`
  - [ ] Database: `defaultdb`
  - [ ] Username: `doadmin`
  - [ ] Password: `your-db-password`

### 1.2 Cloudflare R2 Storage
Go to: https://dash.cloudflare.com → R2
- Create bucket named `tiredproduction`
- Create R2 API Token with read/write access
- Get these values:
  - [ ] Account ID: (from URL or dashboard)
  - [ ] Access Key ID: 
  - [ ] Secret Access Key:
  - [ ] Bucket: `tiredproduction`
  - [ ] Public URL (optional): `https://images.tiredofdointm.com`

### 1.3 Stripe
Go to: https://dashboard.stripe.com/apikeys
- [ ] Publishable Key: `pk_live_xxxxx` (or pk_test for testing)
- [ ] Secret Key: `sk_live_xxxxx` (or sk_test for testing)
- [ ] Webhook Secret: (create after site is live)

### 1.4 Google OAuth (Optional - for Google login)
Go to: https://console.cloud.google.com/apis/credentials
- Create OAuth 2.0 Client ID
- Add redirect URI: `https://tiredofdointm.com/auth/google/callback`
- [ ] Client ID: `xxxxx.apps.googleusercontent.com`
- [ ] Client Secret: `GOCSPX-xxxxx`

### 1.5 Gmail SMTP (for sending emails)
Go to: https://myaccount.google.com/apppasswords
- Generate an App Password for "Mail"
- [ ] Email: `contact@tiredofdointm.com` (or your Gmail)
- [ ] App Password: `xxxx xxxx xxxx xxxx`

### 1.6 Generate Random Secrets
Go to: https://randomkeygen.com/ or generate yourself
- [ ] GATE_SECRET: (64 random characters)
- [ ] ENCRYPTION_KEY: (32 random characters)

---

## PART 2: SSH Commands (Run In Order)

Open Command Prompt and run:
```
ssh root@164.92.99.233
```
Enter password: `19892010Lana`

### 2.1 Edit Configuration File
```bash
nano /home/deploy/tiredprod/website/config/.env
```

Replace the placeholder values with your real credentials from Part 1:

```
APP_NAME=TiredOfDoinTM
APP_URL=https://tiredofdointm.com
APP_ENV=production
APP_DEBUG=false

GATE_PASSWORD=67
GATE_SECRET=YOUR_64_CHAR_RANDOM_STRING_HERE

DB_CONNECTION=pgsql
DB_HOST=your-db-host.db.ondigitalocean.com
DB_PORT=25060
DB_NAME=defaultdb
DB_USER=doadmin
DB_PASS=your-database-password
DB_SSL=require

R2_ACCOUNT_ID=your-cloudflare-account-id
R2_ACCESS_KEY=your-r2-access-key
R2_SECRET_KEY=your-r2-secret-key
R2_BUCKET=tiredproduction
R2_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
R2_PUBLIC_URL=https://images.tiredofdointm.com

GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret

STRIPE_PUBLISHABLE_KEY=pk_test_xxxxx
STRIPE_SECRET_KEY=sk_test_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx

SMTP_HOST=smtp.gmail.com
SMTP_USER=contact@tiredofdointm.com
SMTP_PASS=your-gmail-app-password
SMTP_PORT=587
SMTP_FROM_NAME=TiredOfDoinTM

ADMIN_EMAILS=your-email@gmail.com

ENCRYPTION_KEY=YOUR_32_CHAR_RANDOM_STRING_HERE
```

Press **Ctrl+O**, **Enter**, **Ctrl+X** to save and exit.

### 2.2 Run Database Migrations
```bash
cd /home/deploy/tiredprod && php website/migrate.php
```

### 2.3 Test Site Works
Open browser: **http://164.92.99.233**
- You should see the TiredOfDoinTM homepage
- If error, check: `cat /var/log/nginx/error.log`

---

## PART 3: Domain & SSL Setup

### 3.1 Point Domain to VPS
Go to your domain registrar (Cloudflare, Namecheap, etc.):
- Add **A Record**: `@` → `164.92.99.233`
- Add **A Record**: `www` → `164.92.99.233`
- Wait 5-10 minutes for DNS propagation

### 3.2 Enable SSL (Run on VPS)
```bash
certbot --nginx -d tiredofdointm.com -d www.tiredofdointm.com
```
- Enter your email when asked
- Agree to terms (Y)
- Choose to redirect HTTP to HTTPS (option 2)

---

## PART 4: GitHub Webhook (Auto-Deploy)

### 4.1 Get Webhook Secret
The webhook secret is the first 32 characters of your ENCRYPTION_KEY.
Example: If ENCRYPTION_KEY is `abcd1234efgh5678ijkl9012mnop3456`, webhook secret is same.

### 4.2 Add Webhook to GitHub
Go to: https://github.com/Saulgoodmantm/tired/settings/hooks

Click **"Add webhook"**:
- **Payload URL:** `https://tiredofdointm.com/webhook/github`
- **Content type:** `application/json`
- **Secret:** Your webhook secret from 4.1
- **Events:** Just the push event
- Click **"Add webhook"**

---

## PART 5: Stripe Webhook (For Payments)

### 5.1 Add Stripe Webhook
Go to: https://dashboard.stripe.com/webhooks

Click **"Add endpoint"**:
- **Endpoint URL:** `https://tiredofdointm.com/webhook/stripe`
- **Events:** Select these:
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
  - `checkout.session.completed`
  - `charge.refunded`
- Click **"Add endpoint"**
- Copy the **Signing secret** (starts with `whsec_`)

### 5.2 Update .env with Webhook Secret
```bash
nano /home/deploy/tiredprod/website/config/.env
```
Update the line:
```
STRIPE_WEBHOOK_SECRET=whsec_your_signing_secret_here
```
Save and exit.

---

## PART 6: Final Verification

### 6.1 Test These URLs
- [ ] Homepage: https://tiredofdointm.com
- [ ] Gallery: https://tiredofdointm.com/gallery
- [ ] Booking: https://tiredofdointm.com/booking
- [ ] Login: https://tiredofdointm.com/login
- [ ] Admin Panel: https://tiredofdointm.com/dashboard (login required)
- [ ] Server Management: https://tiredofdointm.com/dashboard/server

### 6.2 Test Auto-Deploy
1. Make a small change to any file in your local repo
2. Commit and push to GitHub
3. Check if VPS automatically updates (view deploy logs in panel)

---

## Quick Reference Commands

**Deploy manually (on VPS):**
```bash
sudo -u deploy /home/deploy/deploy.sh main
```

**View error logs:**
```bash
tail -50 /var/log/nginx/error.log
```

**Restart services:**
```bash
systemctl restart php8.2-fpm nginx
```

**Check service status:**
```bash
systemctl status nginx php8.2-fpm
```

---

## Support

If something doesn't work:
1. Check nginx errors: `tail -50 /var/log/nginx/error.log`
2. Check PHP errors: `tail -50 /var/log/php8.2-fpm.log`
3. Check deploy logs: `cat /home/deploy/tiredprod/website/storage/logs/deploy.log`
