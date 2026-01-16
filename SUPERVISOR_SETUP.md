# Supervisor Setup Guide for Laravel Queue Workers

## Overview

Supervisor is a process control system that keeps Laravel queue workers running continuously. Without Supervisor, queue workers stop when the terminal session ends or the process crashes. Supervisor ensures queue workers automatically restart and run in the background.

## Why Use Supervisor?

- **Automatic Restart**: Workers restart automatically if they crash
- **Background Processing**: Workers run in the background, independent of terminal sessions
- **Multiple Workers**: Run multiple worker processes for better throughput
- **Logging**: Automatic logging of worker output
- **Production Ready**: Industry standard for Laravel queue processing

## Installation

### Step 1: Install Supervisor

```bash
sudo apt update
sudo apt install -y supervisor
```

### Step 2: Verify Installation

```bash
sudo systemctl status supervisor
```

You should see `active (running)` status.

## Configuration

### Step 3: Create Queue Worker Configuration

Create the Supervisor configuration file:

```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

### Step 4: Add Configuration Content

Copy and paste this configuration:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/laravel/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=60 --max-jobs=1000 --memory=128
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/laravel/storage/logs/worker.log
stopwaitsecs=3600
```

### Configuration Explanation

- **`process_name`**: Unique name for each worker process
- **`command`**: The queue:work command with options:
  - `database`: Queue connection name (database driver)
  - `--sleep=3`: Wait 3 seconds between jobs when queue is empty
  - `--tries=3`: Retry failed jobs 3 times before marking as failed
  - `--max-time=3600`: Restart worker after 1 hour (prevents memory leaks)
  - `--timeout=60`: Timeout for each job (60 seconds)
  - `--max-jobs=1000`: Restart worker after processing 1000 jobs (prevents memory leaks)
  - `--memory=128`: Restart worker if memory usage exceeds 128MB
- **`autostart=true`**: Start workers automatically when Supervisor starts
- **`autorestart=true`**: Restart workers if they crash
- **`stopasgroup=true`**: Stop all processes in the group together (graceful shutdown)
- **`killasgroup=true`**: Kill all processes in the group together
- **`user=www-data`**: Run as www-data user (matches PHP-FPM)
- **`numprocs=2`**: Run 2 worker processes (increase for higher load)
- **`stdout_logfile`**: Log file location
- **`stopwaitsecs=3600`**: Wait up to 1 hour for graceful shutdown (allows jobs to finish)

### Step 5: Create Log Directory (if needed)

```bash
sudo mkdir -p /var/www/laravel/storage/logs
sudo chown -R www-data:www-data /var/www/laravel/storage/logs
sudo chmod -R 775 /var/www/laravel/storage/logs
```

## Starting Queue Workers

### Step 6: Reload Supervisor Configuration

```bash
sudo supervisorctl reread
```

Expected output:
```
laravel-worker: available
```

### Step 7: Update Supervisor

```bash
sudo supervisorctl update
```

Expected output:
```
laravel-worker:laravel-worker_00: added process group
laravel-worker:laravel-worker_01: added process group
```

### Step 8: Start Queue Workers

```bash
sudo supervisorctl start laravel-worker:*
```

Expected output:
```
laravel-worker:laravel-worker_00: started
laravel-worker:laravel-worker_01: started
```

## Managing Queue Workers

### Check Status

```bash
sudo supervisorctl status laravel-worker:*
```

Expected output:
```
laravel-worker:laravel-worker_00    RUNNING   pid 12345, uptime 0:05:23
laravel-worker:laravel-worker_01    RUNNING   pid 12346, uptime 0:05:23
```

### Restart Workers

```bash
sudo supervisorctl restart laravel-worker:*
```

### Stop Workers

```bash
sudo supervisorctl stop laravel-worker:*
```

### Start Workers

```bash
sudo supervisorctl start laravel-worker:*
```

### View Logs

```bash
# Real-time log viewing
tail -f /var/www/laravel/storage/logs/worker.log

# Last 50 lines
tail -50 /var/www/laravel/storage/logs/worker.log

# Search for errors
grep -i error /var/www/laravel/storage/logs/worker.log
```

### Reload Configuration (after changes)

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart laravel-worker:*
```

## Verification

### Test Queue Processing

1. **Check if workers are running:**
   ```bash
   ps aux | grep "queue:work" | grep -v grep
   ```
   You should see 2 processes (if numprocs=2).

2. **Check Supervisor status:**
   ```bash
   sudo supervisorctl status
   ```

3. **Test queue processing:**
   ```bash
   cd /var/www/laravel
   sudo -u www-data php artisan tinker
   ```
   Then in tinker:
   ```php
   \App\Jobs\YourJob::dispatch();
   ```
   Check the worker log to see if it processes.

4. **Check pending jobs:**
   ```bash
   cd /var/www/laravel
   sudo -u www-data php artisan tinker --execute="echo 'Pending: ' . DB::table('jobs')->count();"
   ```

## Troubleshooting

### Workers Not Starting

**Check Supervisor logs:**
```bash
sudo tail -f /var/log/supervisor/supervisord.log
```

**Check worker logs:**
```bash
sudo tail -f /var/www/laravel/storage/logs/worker.log
```

**Common issues:**

1. **Permission denied:**
   ```bash
   sudo chown -R www-data:www-data /var/www/laravel
   sudo chmod -R 755 /var/www/laravel
   sudo chmod -R 775 /var/www/laravel/storage
   ```

2. **PHP path incorrect:**
   ```bash
   which php
   # Update command in config to use full path: /usr/bin/php
   ```

3. **Artisan path incorrect:**
   Verify `/var/www/laravel/artisan` exists and is executable:
   ```bash
   ls -la /var/www/laravel/artisan
   sudo chmod +x /var/www/laravel/artisan
   ```

### Workers Keep Restarting

Check the worker log for errors:
```bash
tail -100 /var/www/laravel/storage/logs/worker.log
```

Common causes:
- Database connection issues
- Missing environment variables
- Permission problems
- Memory limits

### Workers Not Processing Jobs

1. **Check queue connection:**
   ```bash
   grep "^QUEUE_CONNECTION=" /var/www/laravel/.env
   ```
   Should be `QUEUE_CONNECTION=database`

2. **Check jobs table exists:**
   ```bash
   cd /var/www/laravel
   sudo -u www-data php artisan migrate:status
   ```

3. **Check pending jobs:**
   ```bash
   cd /var/www/laravel
   sudo -u www-data php artisan tinker --execute="print_r(DB::table('jobs')->get()->toArray());"
   ```

## Advanced Configuration

### Multiple Queue Types

If you have different queue types (e.g., `emails`, `notifications`):

```ini
[program:laravel-worker-emails]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/laravel/artisan queue:work --queue=emails --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
stdout_logfile=/var/www/laravel/storage/logs/worker-emails.log

[program:laravel-worker-notifications]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/laravel/artisan queue:work --queue=notifications --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
stdout_logfile=/var/www/laravel/storage/logs/worker-notifications.log
```

### Increase Worker Count

For higher load, increase `numprocs`:

```ini
numprocs=4  # Run 4 worker processes
```

### Memory Optimization

Add memory limit to prevent memory leaks:

```ini
command=php -d memory_limit=256M /var/www/laravel/artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

### Priority Queues

Process high-priority queues first:

```ini
command=php /var/www/laravel/artisan queue:work --queue=high,default --sleep=3 --tries=3
```

## CI/CD Integration

The deployment pipeline automatically restarts queue workers if Supervisor is installed:

```bash
# From .github/workflows/deploy.yml
if command -v supervisorctl &> /dev/null; then
  sudo supervisorctl reread || true
  sudo supervisorctl update || true
  sudo supervisorctl restart laravel-worker:* || true
fi
```

No manual intervention needed after initial setup!

## Monitoring

### Check Queue Health

Create a monitoring script:

```bash
#!/bin/bash
# /usr/local/bin/check-queue-health.sh

PENDING=$(cd /var/www/laravel && sudo -u www-data php artisan tinker --execute="echo DB::table('jobs')->count();" 2>/dev/null | tail -1)
FAILED=$(cd /var/www/laravel && sudo -u www-data php artisan tinker --execute="echo DB::table('failed_jobs')->count();" 2>/dev/null | tail -1)
WORKERS=$(ps aux | grep "queue:work" | grep -v grep | wc -l)

echo "Pending Jobs: $PENDING"
echo "Failed Jobs: $FAILED"
echo "Running Workers: $WORKERS"

if [ "$FAILED" -gt 100 ]; then
  echo "⚠️  High number of failed jobs!"
fi

if [ "$WORKERS" -eq 0 ]; then
  echo "⚠️  No queue workers running!"
fi
```

Make it executable:
```bash
sudo chmod +x /usr/local/bin/check-queue-health.sh
```

Run it:
```bash
/usr/local/bin/check-queue-health.sh
```

## Maintenance

### Restart After Code Updates

After deploying new code, restart workers:

```bash
sudo supervisorctl restart laravel-worker:*
```

### Clear Failed Jobs

```bash
cd /var/www/laravel
sudo -u www-data php artisan queue:flush
```

### Retry Failed Jobs

```bash
cd /var/www/laravel
sudo -u www-data php artisan queue:retry all
```

## Uninstallation

If you need to remove Supervisor:

```bash
# Stop all workers
sudo supervisorctl stop laravel-worker:*

# Remove config
sudo rm /etc/supervisor/conf.d/laravel-worker.conf

# Reload Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Uninstall Supervisor (optional)
sudo apt remove --purge supervisor
```

---

**Last Updated:** January 2025

