# Pusher Real-time Messaging Setup Guide

## Overview
This guide explains how to set up Pusher for real-time messaging in the Agrisiti Academy mobile app.

## Prerequisites
- Pusher account (sign up at https://pusher.com)
- Laravel backend with Pusher package installed

## Step 1: Get Pusher Credentials

1. Sign in to your Pusher dashboard: https://dashboard.pusher.com
2. Create a new app or select an existing one
3. Go to "App Keys" tab
4. Copy the following credentials:
   - App ID
   - Key
   - Secret
   - Cluster (e.g., mt1, eu, ap-southeast-1)

## Step 2: Configure Backend (.env file)

Add the following to your `.env` file:

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1

# Optional: For self-hosted Pusher
# PUSHER_HOST=your-pusher-host
# PUSHER_PORT=443
# PUSHER_SCHEME=https
```

## Step 3: Configure Frontend (.env file)

Add the following to your Flutter app's `.env` file:

```env
PUSHER_APP_KEY=your_app_key
PUSHER_APP_CLUSTER=mt1
PUSHER_ENCRYPTED=true
```

## Step 4: Channel Authorization

The backend now includes a channel authorization endpoint at:
```
POST /api/broadcasting/auth
```

This endpoint:
- Validates user authentication
- Checks if user is enrolled in the course
- Generates Pusher auth signature for private channels

## Step 5: Channel Naming Convention

Private channels follow this format:
```
private-course.{courseId}.user.{userId}
```

Example:
```
private-course.1.user.5
```

## Step 6: Events Broadcasted

### MessageSent Event
- **Channel**: `private-course.{courseId}.user.{senderId}` and `private-course.{courseId}.user.{recipientId}`
- **Event Name**: `message-sent`
- **Triggered**: When a new message is created
- **Data**: Full message object with sender and recipient info

### MessageRead Event
- **Channel**: `private-course.{courseId}.user.{senderId}`
- **Event Name**: `message-read`
- **Triggered**: When a message is marked as read
- **Data**: Message ID and read status

## Step 7: Testing

1. Start your Laravel backend
2. Ensure queue worker is running (for broadcasting):
   ```bash
   php artisan queue:work
   ```
3. Test message sending - messages should appear in real-time
4. Test message read receipts - sender should see read status update

## Troubleshooting

### Messages not appearing in real-time
1. Check Pusher credentials in `.env`
2. Ensure queue worker is running
3. Check browser console for Pusher connection errors
4. Verify channel authorization endpoint is accessible

### Channel authorization failing
1. Check user is authenticated
2. Verify user is enrolled in the course
3. Check channel name format matches expected pattern

### Events not broadcasting
1. Ensure `BROADCAST_DRIVER=pusher` in `.env`
2. Check Pusher dashboard for event logs
3. Verify events implement `ShouldBroadcast` interface

## Notes

- Private channels require authentication
- Channel authorization is handled automatically by Laravel
- Events are queued by default for better performance
- Make sure to run queue workers in production
