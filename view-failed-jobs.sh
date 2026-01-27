#!/bin/bash

# Script to view failed job logs
# Usage: ./view-failed-jobs.sh

LOG_PATH="/var/www/laravel/storage/logs/laravel.log"

echo "============================================"
echo "FAILED JOBS LOG VIEWER"
echo "============================================"
echo ""

echo "--- Last 200 lines of log (most recent) ---"
sudo tail -200 "$LOG_PATH"
echo ""

echo "============================================"
echo "--- Failed ProcessStudentRegistrationWithCSV Jobs ---"
echo "============================================"
sudo grep -B 5 -A 30 "ProcessStudentRegistrationWithCSV" "$LOG_PATH" | grep -iE "failed|error|exception" | tail -50
echo ""

echo "============================================"
echo "--- Failed WelcomeStudentMail Jobs ---"
echo "============================================"
sudo grep -B 5 -A 30 "WelcomeStudentMail" "$LOG_PATH" | grep -iE "failed|error|exception" | tail -50
echo ""

echo "============================================"
echo "--- All Recent Errors (Last 100) ---"
echo "============================================"
sudo grep -iE "failed|error|exception" "$LOG_PATH" | tail -100
echo ""

echo "============================================"
echo "--- Full Error Traces (Last 20 errors) ---"
echo "============================================"
sudo grep -B 2 -A 25 "ProcessStudentRegistrationWithCSV\|WelcomeStudentMail" "$LOG_PATH" | tail -200
echo ""

echo "============================================"
echo "To view real-time logs:"
echo "  sudo tail -f $LOG_PATH"
echo ""
echo "To view last 500 lines:"
echo "  sudo tail -500 $LOG_PATH"
echo "============================================"

