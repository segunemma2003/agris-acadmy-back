#!/bin/bash

# Quick Queue Status Check Script for EC2
# Run this on your EC2 instance: bash check-queue-status.sh

echo "=== Laravel Queue Status Check ==="
echo ""

# 1. Check if queue connection is set
echo "1. Queue Configuration:"
if [ -f /var/www/laravel/.env ]; then
    QUEUE_CONNECTION=$(grep "^QUEUE_CONNECTION=" /var/www/laravel/.env | cut -d '=' -f2)
    echo "   Queue Connection: ${QUEUE_CONNECTION:-database (default)}"
else
    echo "   ⚠️  .env file not found"
fi
echo ""

# 2. Check if jobs table exists and has pending jobs
echo "2. Pending Jobs in Database:"
cd /var/www/laravel
PENDING_JOBS=$(sudo -u www-data php artisan tinker --execute="echo DB::table('jobs')->count();" 2>/dev/null | tail -1)
FAILED_JOBS=$(sudo -u www-data php artisan tinker --execute="echo DB::table('failed_jobs')->count();" 2>/dev/null | tail -1)
echo "   Pending Jobs: ${PENDING_JOBS:-0}"
echo "   Failed Jobs: ${FAILED_JOBS:-0}"
echo ""

# 3. Check if Supervisor is installed and running
echo "3. Supervisor Status:"
if command -v supervisorctl &> /dev/null; then
    echo "   ✓ Supervisor is installed"
    echo ""
    echo "   Queue Worker Status:"
    sudo supervisorctl status laravel-worker:* 2>/dev/null || echo "   ⚠️  No queue workers configured in Supervisor"
else
    echo "   ✗ Supervisor is NOT installed"
    echo "   ⚠️  Queue workers are NOT running continuously"
    echo "   💡 Install Supervisor to run queue workers automatically"
fi
echo ""

# 4. Check if queue worker processes are running
echo "4. Running Queue Worker Processes:"
QUEUE_PROCESSES=$(ps aux | grep "queue:work" | grep -v grep | wc -l)
if [ "$QUEUE_PROCESSES" -gt 0 ]; then
    echo "   ✓ Found $QUEUE_PROCESSES queue worker process(es):"
    ps aux | grep "queue:work" | grep -v grep | awk '{print "   - PID: " $2 " | User: " $1 " | Command: " $11 " " $12 " " $13}'
else
    echo "   ✗ No queue worker processes running"
    echo "   ⚠️  Jobs will NOT be processed automatically"
fi
echo ""

# 5. Check queue worker log
echo "5. Queue Worker Log (last 10 lines):"
if [ -f /var/www/laravel/storage/logs/worker.log ]; then
    echo "   Recent log entries:"
    tail -10 /var/www/laravel/storage/logs/worker.log | sed 's/^/   /'
else
    echo "   ⚠️  No worker.log found (workers may not be running via Supervisor)"
fi
echo ""

# 6. Test queue processing
echo "6. Testing Queue Processing:"
cd /var/www/laravel
TEST_OUTPUT=$(sudo -u www-data php artisan queue:work --once --tries=1 --timeout=5 2>&1)
if echo "$TEST_OUTPUT" | grep -q "Processing\|No jobs"; then
    echo "   ✓ Queue processing test successful"
else
    echo "   ⚠️  Queue processing test had issues:"
    echo "$TEST_OUTPUT" | head -3 | sed 's/^/   /'
fi
echo ""

echo "=== Summary ==="
if [ "$QUEUE_PROCESSES" -gt 0 ] || command -v supervisorctl &> /dev/null; then
    echo "✓ Queue workers appear to be configured"
    echo "  Run: sudo supervisorctl status laravel-worker:*"
else
    echo "⚠️  Queue workers are NOT running continuously"
    echo ""
    echo "To set up queue workers:"
    echo "1. Install Supervisor: sudo apt install -y supervisor"
    echo "2. Create config: sudo nano /etc/supervisor/conf.d/laravel-worker.conf"
    echo "3. Start workers: sudo supervisorctl start laravel-worker:*"
fi






