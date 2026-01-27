#!/bin/bash

# Email Log Checker Script for EC2
# Usage: ./check-email-logs.sh [email_address]

echo "============================================"
echo "EMAIL LOG CHECKER FOR EC2 INSTANCE"
echo "============================================"
echo ""

LARAVEL_PATH="/var/www/laravel"
LOG_PATH="$LARAVEL_PATH/storage/logs/laravel.log"
WORKER_LOG="$LARAVEL_PATH/storage/logs/worker.log"

# Check if email address provided
if [ -n "$1" ]; then
    EMAIL="$1"
    echo "Searching for email: $EMAIL"
    echo ""
    
    echo "--- Recent log entries for $EMAIL ---"
    sudo grep "$EMAIL" "$LOG_PATH" | tail -20
    echo ""
fi

echo "--- Recent Email-Related Log Entries ---"
sudo grep -iE "mail|email|enrollment|welcome|WelcomeStudentMail|EnrollmentCodeMail|ProcessStudentRegistrationWithCSV" "$LOG_PATH" | tail -30
echo ""

echo "--- Failed Email Jobs ---"
cd "$LARAVEL_PATH" && sudo -u www-data php artisan queue:failed 2>/dev/null | head -20
echo ""

echo "--- Queue Worker Status ---"
sudo supervisorctl status laravel-worker 2>/dev/null || echo "Supervisor not running or worker not found"
echo ""

echo "--- Recent Queue Worker Logs ---"
if [ -f "$WORKER_LOG" ]; then
    sudo tail -20 "$WORKER_LOG"
else
    echo "Worker log not found at $WORKER_LOG"
fi
echo ""

echo "--- Supervisor Worker Logs ---"
if [ -f "/var/log/supervisor/laravel-worker-stdout.log" ]; then
    echo "STDOUT:"
    sudo tail -10 /var/log/supervisor/laravel-worker-stdout.log
    echo ""
fi

if [ -f "/var/log/supervisor/laravel-worker-stderr.log" ]; then
    echo "STDERR:"
    sudo tail -10 /var/log/supervisor/laravel-worker-stderr.log
    echo ""
fi

echo "--- Email Sending Errors (Last 20) ---"
sudo grep -iE "failed|error|exception" "$LOG_PATH" | grep -iE "mail|email|enrollment" | tail -20
echo ""

echo "--- Successful Email Sends (Last 20) ---"
sudo grep -iE "sent.*email|queued.*mail|WelcomeStudentMail|EnrollmentCodeMail" "$LOG_PATH" | tail -20
echo ""

echo "============================================"
echo "To monitor in real-time, run:"
echo "  sudo tail -f $LOG_PATH | grep -i mail"
echo "============================================"

