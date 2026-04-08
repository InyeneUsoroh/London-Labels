# 🚀 London Labels Deployment Checklist (Railway)

This checklist covers everything you need to do from your local machine to the official live domain. I've prepared all the local configuration files to make this as smooth as possible.

## 📦 Phase 1: Local Preparation (Done!)
- [x] Hardcoded credentials moved to `.env` dependencies (`config.php` updated).
- [x] Test SQL database (`data.db`) removed from repository to prevent wipes.
- [x] Comprehensive `.gitignore` ignores sensitive keys and image uploads.
- [x] A `railway.toml` was added to automate the Railway persistent volume mount (`/var/www/html/Uploads`) and Docker environment builder.
- [x] `.env.example` created.
- [x] Security `.htaccess` added to `Uploads/` to neutralize PHP upload attacks.

## ☁️ Phase 2: Deploy to Railway

1. **Commit to GitHub**
   Open your terminal and push the latest secure changes to your repo:
   ```bash
   git add .
   git commit -m "chore: railway production preparation"
   git push origin main
   ```

2. **Connect Railway**
   - Go to [Railway.app](https://railway.app/) and log in (using GitHub is easiest).
   - Click **New Project** → **Deploy from GitHub repo**.
   - Select the `InyeneUsoroh/London-Labels` repository.
   - Wait for the initial deployment (it will launch but won't fully work until the DB is connected).

3. **Add the MySQL Database**
   - In your Railway project view, click the **+ Create** button (or "New" button).
   - Select **Database** → **Add MySQL**.
   - Wait 30 seconds for the database to provision.

4. **Connect App to Database**
   - Click on your new MySQL instance and go to the **Connect** tab. You'll see `MYSQL_HOST`, `MYSQL_USER`, `MYSQL_PASSWORD`, and `MYSQL_DATABASE`.
   - Click on your **London Labels Code App** block.
   - Go to the **Variables** tab.
   - Click **New Variable** and add these (mapping to our `config.php` setup):
     - `DB_HOST` = (Copy the Railway MYSQL_HOST string, e.g. `mysql.railway.internal`)
     - `DB_USER` = (Copy the Railway MYSQL_USER)
     - `DB_PASS` = (Copy the Railway MYSQL_PASSWORD)
     - `DB_NAME` = (Copy the Railway MYSQL_DATABASE)
   - Add your secret keys as well:
     - `PAYSTACK_SECRET_KEY`
     - `PAYSTACK_PUBLIC_KEY`
     - `GOOGLE_CLIENT_ID`
     - `GOOGLE_CLIENT_SECRET`

5. **Load Your Database Tables**
   - Your SQL structure is in `schema.sql`. 
   - While still viewing the MySQL block in Railway, go to the **Data** tab.
   - You should see an option to **Run Query** or **Import**. If there is an SQL runner, simply copy the entire contents of your local `schema.sql` file and paste/run it there to generate all your tables (`admin_users`, `products`, `orders`, etc.).
   - *(Alternative: Use a tool like TablePlus or MySQL Workbench locally, and connect to the Railway remote database using the public URL provided in their dashboard to run your `schema.sql`).*

## 🖼️ Phase 3: Domain & Final Checks

1. **Verify Image Uploads**
   - Open your generated Railway link (e.g. `london-labels.up.railway.app`).
   - Log into your admin panel.
   - Upload a test product image to verify it successfully writes to the volume.

2. **Connect Custom Domain**
   - Go to your Railway app block → **Settings** → **Networking**.
   - Under **Custom Domains**, type your real domain (e.g., `londonlabels.com`).
   - The dashboard will give you a `CNAME` or `A` record.
   - Go to your domain registrar (Namecheap, GoDaddy, etc.) and add that DNS record.

🎉 **You're LIVE!**
