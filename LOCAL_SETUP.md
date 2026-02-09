# 🚀 RUN OPTIMIZATIONS - QUICK STEPS

## ✅ Your .env is already corrected

Duplicate variables have been removed and configurations updated correctly.

---

## 📝 NEXT STEPS (Execute in order)

### **STEP 1: Open Laragon Terminal**

1. Open **Laragon**
2. Right-click on "Menu"
3. Click on **"Terminal"** (or press `Ctrl + Alt + T`)
4. This opens a terminal with PHP already configured

---

### **STEP 2: Run configuration commands**

Copy and paste each command in the Laragon terminal:

```bash
cd C:\laragon\www\hejdabil-admin\backend

# Clear configurations
php artisan config:clear
php artisan cache:clear

# Run migrations
php artisan migrate

# Verify system status
php artisan monitor:performance
```

---

### **STEP 3: Start Redis**

Inside Laragon:
1. Right-click on Laragon icon (system tray)
2. Menu → **Redis** → **Start Redis**

**Verify Redis is running:**
```bash
# In Laragon terminal:
redis-cli ping
```

Should respond: `PONG` ✅

---

### **STEP 4: Start Queue Worker**

**Open a SECOND Laragon terminal** (Ctrl + Alt + T again) and run:

```bash
cd C:\laragon\www\hejdabil-admin\backend
php artisan queue:work redis --queue=emails,default --tries=3 --timeout=120
```

**⚠️ IMPORTANT:** 
- **DO NOT close this terminal** while working
- It processes emails in the background
- You'll see messages when jobs are processed

---

### **STEP 5: Verify everything works**

In the **first terminal**, run:

```bash
php artisan monitor:performance
```

You should see:
```
📊 REDIS STATUS:
  ✓ Connected: YES
  • Memory Used: X MB
  • Connected Clients: 2

💾 DATABASE STATUS:
  ✓ Connected: YES
  • Driver: mysql
  • Database: hejdabil_admin

⚙️  QUEUE STATUS:
  • Pending Jobs: 0
  • Failed Jobs: 0

🗄️  CACHE STATUS:
  • Driver: redis
  • Cached Catalogs: 0/4
```

---

## ✅ DONE!

Your application is now optimized. When you create a supplier:
- ✅ Email will be queued (no blocking)
- ✅ Catalog queries will be cached
- ✅ Responses will be compressed
- ✅ No 429 errors

---

## 🎯 Test the Optimizations

### Test 1: Create a Supplier
1. Go to your app and create a new supplier
2. In the queue worker terminal, you should see:
```
[2026-02-09 12:00:00][1] Processing: App\Jobs\SendEmailJob
[2026-02-09 12:00:01][1] Processed:  App\Jobs\SendEmailJob
```

### Test 2: Verify Cache
```bash
# In tinker
php artisan tinker
>>> app('App\Services\CacheService')->getBrands();
>>> exit

# Check monitor again
php artisan monitor:performance
# Should show "Cached Catalogs: 1/4"
```

### Test 3: Check Queue Status
```bash
php artisan queue:monitor
# Should show 0 pending, 0 failed
```

---

## 📚 Additional Documentation

- [README_OPTIMIZATIONS.md](README_OPTIMIZATIONS.md) - Overview and benefits
- [OPTIMIZATIONS.md](OPTIMIZATIONS.md) - Complete technical details
- [PRODUCTION.md](PRODUCTION.md) - Production deployment guide
- [USEFUL_COMMANDS.md](USEFUL_COMMANDS.md) - Command reference

---

## 🐛 Common Issues

### Queue worker not processing emails
**Solution:** Make sure you're listening to the `emails` queue:
```bash
php artisan queue:work redis --queue=emails,default --tries=3 --timeout=120
```

### Redis connection error
**Solution:** Check Redis is running:
```bash
# Start in Laragon
Laragon Menu → Redis → Start Redis

# Or check status
redis-cli ping
```

### Cache not working
**Solution:** Clear and rebuild:
```bash
php artisan cache:clear
php artisan config:clear
php artisan optimize
```

---

**Last updated:** February 9, 2026
