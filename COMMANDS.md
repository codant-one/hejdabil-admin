# 🎯 COMMANDS TO EXECUTE - COPY AND PASTE

## ✅ STEP 1: Open Correct Terminal

**In Laragon:**
1. Open Laragon (the one you use normally)
2. Right-click on Laragon icon in system tray
3. Click on "Terminal" 
4. A black window opens (Laragon PowerShell)

---

## ✅ STEP 2: Execute These Commands

**Copy and paste these commands ONE BY ONE** in Laragon terminal:

### Command 1: Go to backend folder
```bash
cd C:\laragon\www\hejdabil-admin\backend
```
Press Enter ↵

---

### Command 2: Clear configuration
```bash
php artisan config:clear
```
Press Enter ↵

---

### Command 3: Clear cache
```bash
php artisan cache:clear
```
Press Enter ↵

---

### Command 4: Run migrations (only job_batches and indexes pending)
```bash
php artisan migrate
```
Press Enter ↵

**Note:** Only 2 new migrations will run:
- `2026_02_09_000001_create_job_batches_table` (batch job support)
- `2026_02_09_000002_add_performance_indexes` (indexes for faster queries)

Other tables (jobs, failed_jobs) already exist ✅

---

### Command 5: Verify everything works
```bash
php artisan monitor:performance
```
Press Enter ↵

---

## ✅ STEP 3: Start Redis

**In Laragon:**
1. Right-click on Laragon icon
2. Menu → Redis
3. Click on "Start Redis"

**Verify Redis works:**
```bash
redis-cli ping
```

Should respond: `PONG`

---

## ✅ STEP 4: Start Worker (New Terminal)

**Open a SECOND Laragon terminal** (Ctrl + Alt + T) and run:

```bash
cd C:\laragon\www\hejdabil-admin\backend
```
Press Enter ↵

Then:
```bash
php artisan queue:work redis --queue=emails,default --tries=3 --timeout=120
```
Press Enter ↵

**⚠️ IMPORTANT: DO NOT close this terminal** - it must stay open showing messages.

---

## ✅ DONE!

You can now use your application normally.

The optimizations are active:
- ✅ Catalogs cached in Redis
- ✅ Emails processed asynchronously
- ✅ Responses compressed
- ✅ 5,000 requests/minute supported

---

## 🔍 Verify It's Working

### Create a supplier and check the queue worker terminal:
```
[2026-02-09 12:00:00][1] Processing: App\Jobs\SendEmailJob
[2026-02-09 12:00:01][1] Processed:  App\Jobs\SendEmailJob
```

### Check system status:
```bash
php artisan monitor:performance
```

---

**Need help?** See [LOCAL_SETUP.md](LOCAL_SETUP.md) for detailed explanations.
