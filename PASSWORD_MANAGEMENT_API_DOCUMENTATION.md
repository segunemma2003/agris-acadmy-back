# Password Management API Documentation

## Overview

This document provides comprehensive API documentation for password management features including forgot password, reset password, and change password functionality.

---

## Base Configuration

### Base URL
```
Production: https://academy-backends.agrisiti.com/api
Development: http://localhost:8000/api
```

### Authentication
- **Forgot Password & Reset Password:** Public endpoints (no authentication required)
- **Change Password:** Requires authentication (Bearer token)

### Content Type
All requests should include:
```
Content-Type: application/json
Accept: application/json
```

---

## 1. Forgot Password

**Endpoint:** `POST /api/forgot-password`

**Description:** Request a password reset link to be sent to the user's email address.

**Authentication:** Not required (public endpoint)

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Validation Rules:**
- `email`: Required, must be a valid email format
- `email`: Must exist in the users table

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password reset link has been sent to your email address."
}
```

**Error Responses:**

**400 Bad Request - Invalid Email Format:**
```json
{
  "message": "The email field must be a valid email address.",
  "errors": {
    "email": ["The email field must be a valid email address."]
  }
}
```

**400 Bad Request - Email Not Found:**
```json
{
  "message": "The selected email is invalid.",
  "errors": {
    "email": ["The selected email is invalid."]
  }
}
```

**400 Bad Request - Unable to Send:**
```json
{
  "success": false,
  "message": "Unable to send password reset link. Please try again later."
}
```

**429 Too Many Requests - Throttled:**
```json
{
  "success": false,
  "message": "Please wait before retrying. You can request a password reset once per minute."
}
```

**500 Internal Server Error:**
```json
{
  "success": false,
  "message": "An error occurred while sending the password reset link. Please try again later."
}
```

**Example cURL:**
```bash
curl -X POST https://academy-backends.agrisiti.com/api/forgot-password \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com"
  }'
```

**Example JavaScript (Fetch):**
```javascript
const response = await fetch('https://academy-backends.agrisiti.com/api/forgot-password', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    email: 'user@example.com'
  })
});

const data = await response.json();
console.log(data);
```

**Notes:**
- The password reset link will be sent to the user's email address
- The reset link expires in 60 minutes
- Users can only request a password reset once per minute (throttled)
- The reset link contains a token that must be used with the reset password endpoint
- The email will contain a link to your frontend application with the token as a query parameter

---

## 2. Reset Password

**Endpoint:** `POST /api/reset-password`

**Description:** Reset the user's password using the token received from the forgot password email.

**Authentication:** Not required (public endpoint)

**Request Body:**
```json
{
  "token": "reset_token_from_email",
  "email": "user@example.com",
  "password": "NewSecurePassword123!",
  "password_confirmation": "NewSecurePassword123!"
}
```

**Validation Rules:**
- `token`: Required, must be a valid reset token from the email
- `email`: Required, must be a valid email format and exist in the users table
- `password`: Required, must be at least 8 characters
- `password_confirmation`: Required, must match the password field

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password has been reset successfully. You can now login with your new password."
}
```

**Error Responses:**

**400 Bad Request - Invalid Token:**
```json
{
  "success": false,
  "message": "Invalid reset token. Please request a new password reset link."
}
```

**400 Bad Request - Expired Token:**
```json
{
  "success": false,
  "message": "Invalid or expired reset token. Please request a new password reset link."
}
```

**400 Bad Request - Validation Error:**
```json
{
  "message": "The password field must be at least 8 characters.",
  "errors": {
    "password": ["The password field must be at least 8 characters."]
  }
}
```

**404 Not Found - User Not Found:**
```json
{
  "success": false,
  "message": "No user found with this email address."
}
```

**500 Internal Server Error:**
```json
{
  "success": false,
  "message": "An error occurred while resetting your password. Please try again later."
}
```

**Example cURL:**
```bash
curl -X POST https://academy-backends.agrisiti.com/api/reset-password \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "token": "reset_token_from_email",
    "email": "user@example.com",
    "password": "NewSecurePassword123!",
    "password_confirmation": "NewSecurePassword123!"
  }'
```

**Example JavaScript (Fetch):**
```javascript
const response = await fetch('https://academy-backends.agrisiti.com/api/reset-password', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    token: 'reset_token_from_email',
    email: 'user@example.com',
    password: 'NewSecurePassword123!',
    password_confirmation: 'NewSecurePassword123!'
  })
});

const data = await response.json();
console.log(data);
```

**Notes:**
- The token is valid for 60 minutes from the time it was generated
- After successfully resetting the password, the user should be redirected to the login screen
- The token can only be used once
- If the token is expired or invalid, the user must request a new password reset link

---

## 3. Change Password

**Endpoint:** `PUT /api/user/password`

**Description:** Change the password for an authenticated user. This requires the user to provide their current password.

**Authentication:** Required (Bearer token)

**Headers:**
```
Authorization: Bearer {your_token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "current_password": "CurrentPassword123!",
  "password": "NewSecurePassword123!",
  "password_confirmation": "NewSecurePassword123!"
}
```

**Validation Rules:**
- `current_password`: Required, must match the user's current password
- `password`: Required, must be at least 8 characters
- `password_confirmation`: Required, must match the password field
- New password must be different from the current password

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

**Error Responses:**

**400 Bad Request - Incorrect Current Password:**
```json
{
  "success": false,
  "message": "Current password is incorrect"
}
```

**400 Bad Request - Same Password:**
```json
{
  "success": false,
  "message": "New password must be different from your current password"
}
```

**400 Bad Request - Validation Error:**
```json
{
  "message": "The password field must be at least 8 characters.",
  "errors": {
    "password": ["The password field must be at least 8 characters."]
  }
}
```

**401 Unauthorized - Missing or Invalid Token:**
```json
{
  "message": "Unauthenticated."
}
```

**500 Internal Server Error:**
```json
{
  "success": false,
  "message": "An error occurred while changing your password. Please try again later."
}
```

**Example cURL:**
```bash
curl -X PUT https://academy-backends.agrisiti.com/api/user/password \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "current_password": "CurrentPassword123!",
    "password": "NewSecurePassword123!",
    "password_confirmation": "NewSecurePassword123!"
  }'
```

**Example JavaScript (Fetch):**
```javascript
const token = 'your_token_here';

const response = await fetch('https://academy-backends.agrisiti.com/api/user/password', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    current_password: 'CurrentPassword123!',
    password: 'NewSecurePassword123!',
    password_confirmation: 'NewSecurePassword123!'
  })
});

const data = await response.json();
console.log(data);
```

**Notes:**
- This endpoint requires the user to be authenticated
- The user must provide their current password to verify their identity
- The new password must be different from the current password
- After successfully changing the password, the user remains logged in
- The token remains valid after password change

---

## Complete Password Management Flow

### Scenario 1: User Forgot Password

1. **User clicks "Forgot Password"** on the login screen
2. **User enters email address** and submits
3. **App calls:** `POST /api/forgot-password` with email
4. **User receives email** with reset link containing token
5. **User clicks link** in email (opens app or web page)
6. **App extracts token** from URL query parameter
7. **User enters new password** and confirms
8. **App calls:** `POST /api/reset-password` with token, email, and new password
9. **Password is reset** and user can login with new password

### Scenario 2: User Wants to Change Password (Logged In)

1. **User navigates to Settings/Profile** screen
2. **User clicks "Change Password"**
3. **User enters:**
   - Current password
   - New password
   - Confirm new password
4. **App calls:** `PUT /api/user/password` with all three fields
5. **Password is updated** and user sees success message

---

## Mobile App Implementation Guide

### Step 1: Forgot Password Screen

```javascript
// ForgotPasswordScreen.js
import React, { useState } from 'react';
import { View, TextInput, Button, Alert } from 'react-native';

const ForgotPasswordScreen = () => {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);

  const handleForgotPassword = async () => {
    if (!email) {
      Alert.alert('Error', 'Please enter your email address');
      return;
    }

    setLoading(true);
    try {
      const response = await fetch('https://academy-backends.agrisiti.com/api/forgot-password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ email })
      });

      const data = await response.json();

      if (data.success) {
        Alert.alert('Success', data.message);
        // Navigate to check email screen
      } else {
        Alert.alert('Error', data.message);
      }
    } catch (error) {
      Alert.alert('Error', 'Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View>
      <TextInput
        placeholder="Email"
        value={email}
        onChangeText={setEmail}
        keyboardType="email-address"
        autoCapitalize="none"
      />
      <Button
        title="Send Reset Link"
        onPress={handleForgotPassword}
        disabled={loading}
      />
    </View>
  );
};
```

### Step 2: Reset Password Screen

```javascript
// ResetPasswordScreen.js
import React, { useState, useEffect } from 'react';
import { View, TextInput, Button, Alert } from 'react-native';
import { useRoute } from '@react-navigation/native';

const ResetPasswordScreen = () => {
  const route = useRoute();
  const { token, email } = route.params; // Extract from deep link or navigation params

  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(false);

  const handleResetPassword = async () => {
    if (password.length < 8) {
      Alert.alert('Error', 'Password must be at least 8 characters');
      return;
    }

    if (password !== passwordConfirmation) {
      Alert.alert('Error', 'Passwords do not match');
      return;
    }

    setLoading(true);
    try {
      const response = await fetch('https://academy-backends.agrisiti.com/api/reset-password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          token,
          email,
          password,
          password_confirmation: passwordConfirmation
        })
      });

      const data = await response.json();

      if (data.success) {
        Alert.alert('Success', data.message);
        // Navigate to login screen
      } else {
        Alert.alert('Error', data.message);
      }
    } catch (error) {
      Alert.alert('Error', 'Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View>
      <TextInput
        placeholder="New Password"
        value={password}
        onChangeText={setPassword}
        secureTextEntry
      />
      <TextInput
        placeholder="Confirm New Password"
        value={passwordConfirmation}
        onChangeText={setPasswordConfirmation}
        secureTextEntry
      />
      <Button
        title="Reset Password"
        onPress={handleResetPassword}
        disabled={loading}
      />
    </View>
  );
};
```

### Step 3: Change Password Screen (Settings)

```javascript
// ChangePasswordScreen.js
import React, { useState } from 'react';
import { View, TextInput, Button, Alert } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

const ChangePasswordScreen = () => {
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleChangePassword = async () => {
    if (newPassword.length < 8) {
      Alert.alert('Error', 'Password must be at least 8 characters');
      return;
    }

    if (newPassword !== confirmPassword) {
      Alert.alert('Error', 'Passwords do not match');
      return;
    }

    const token = await AsyncStorage.getItem('auth_token');

    setLoading(true);
    try {
      const response = await fetch('https://academy-backends.agrisiti.com/api/user/password', {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          current_password: currentPassword,
          password: newPassword,
          password_confirmation: confirmPassword
        })
      });

      const data = await response.json();

      if (data.success) {
        Alert.alert('Success', data.message);
        // Clear form or navigate back
        setCurrentPassword('');
        setNewPassword('');
        setConfirmPassword('');
      } else {
        Alert.alert('Error', data.message);
      }
    } catch (error) {
      Alert.alert('Error', 'Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View>
      <TextInput
        placeholder="Current Password"
        value={currentPassword}
        onChangeText={setCurrentPassword}
        secureTextEntry
      />
      <TextInput
        placeholder="New Password"
        value={newPassword}
        onChangeText={setNewPassword}
        secureTextEntry
      />
      <TextInput
        placeholder="Confirm New Password"
        value={confirmPassword}
        onChangeText={setConfirmPassword}
        secureTextEntry
      />
      <Button
        title="Change Password"
        onPress={handleChangePassword}
        disabled={loading}
      />
    </View>
  );
};
```

---

## Deep Linking Configuration

For mobile apps, you'll need to configure deep linking to handle password reset emails:

### iOS (Info.plist)
```xml
<key>CFBundleURLTypes</key>
<array>
  <dict>
    <key>CFBundleURLSchemes</key>
    <array>
      <string>agrisiti</string>
    </array>
  </dict>
</array>
```

### Android (AndroidManifest.xml)
```xml
<intent-filter>
  <action android:name="android.intent.action.VIEW" />
  <category android:name="android.intent.category.DEFAULT" />
  <category android:name="android.intent.category.BROWSABLE" />
  <data android:scheme="agrisiti" />
</intent-filter>
```

### Email Link Format
The email will contain a link like:
```
https://your-frontend-url.com/reset-password?token=xxxxx&email=user@example.com
```

Or for deep linking:
```
agrisiti://reset-password?token=xxxxx&email=user@example.com
```

---

## Security Considerations

1. **Token Expiration:** Reset tokens expire after 60 minutes
2. **Throttling:** Users can only request a password reset once per minute
3. **One-Time Use:** Reset tokens can only be used once
4. **Password Requirements:** Minimum 8 characters
5. **Current Password Verification:** Required for changing password while logged in
6. **HTTPS:** Always use HTTPS in production

---

## Testing

### Test Cases

1. **Forgot Password:**
   - ✅ Valid email sends reset link
   - ✅ Invalid email returns error
   - ✅ Non-existent email returns error
   - ✅ Throttling works (can't request multiple times in 1 minute)

2. **Reset Password:**
   - ✅ Valid token resets password
   - ✅ Expired token returns error
   - ✅ Invalid token returns error
   - ✅ Used token can't be reused
   - ✅ Password validation works

3. **Change Password:**
   - ✅ Correct current password changes password
   - ✅ Incorrect current password returns error
   - ✅ Same password returns error
   - ✅ Password validation works
   - ✅ Requires authentication

---

## Troubleshooting

### Issue: Email not received
- Check spam folder
- Verify email configuration in `.env`
- Check mail logs
- Verify user email exists in database

### Issue: Token invalid/expired
- Token expires after 60 minutes
- Token can only be used once
- Request a new password reset link

### Issue: Throttling error
- Wait 60 seconds between requests
- This prevents abuse

### Issue: Password change fails
- Verify current password is correct
- Ensure new password is different from current
- Check password meets requirements (min 8 characters)

---

## Support

For issues or questions, contact the development team or refer to the main API documentation.

