# S3 Configuration Guide for Agrisiti Academy

## Overview

The application supports S3 (Amazon Simple Storage Service) for storing uploaded images. This guide explains how to configure S3 in both local development and production environments.

## Why S3?

- **Scalability**: Handle large numbers of file uploads
- **Performance**: CDN integration for fast image delivery
- **Reliability**: AWS infrastructure with 99.999999999% durability
- **Cost-effective**: Pay only for what you use
- **Universal Access**: Images accessible from mobile apps, web, and desktop

## Required S3 Environment Variables

Add these to your `.env` file or GitHub Secrets:

```env
# Filesystem Configuration
FILESYSTEM_DISK=s3                    # Set to 's3' for S3, 'public' for local storage

# AWS S3 Credentials
AWS_ACCESS_KEY_ID=your_access_key_id
AWS_SECRET_ACCESS_KEY=your_secret_access_key
AWS_DEFAULT_REGION=us-east-1          # Your AWS region (e.g., us-east-1, eu-west-1)
AWS_BUCKET=your-bucket-name           # Your S3 bucket name
AWS_URL=https://your-bucket.s3.region.amazonaws.com
AWS_ENDPOINT=                         # Optional: For S3-compatible services (leave empty for AWS)
AWS_USE_PATH_STYLE_ENDPOINT=false    # Set to true for S3-compatible services
```

## GitHub Secrets Configuration

To enable S3 in production via CI/CD pipeline, add these secrets in GitHub:

1. Go to your repository → **Settings** → **Secrets and variables** → **Actions**
2. Click **New repository secret**
3. Add the following secrets:

### Required Secrets (if using S3):

- `FILESYSTEM_DISK` = `s3` (or `public` for local storage)
- `AWS_ACCESS_KEY_ID` = Your AWS access key ID
- `AWS_SECRET_ACCESS_KEY` = Your AWS secret access key
- `AWS_DEFAULT_REGION` = Your AWS region (e.g., `us-east-1`)
- `AWS_BUCKET` = Your S3 bucket name
- `AWS_URL` = Your S3 bucket URL (e.g., `https://agrisiti-academy.s3.us-east-1.amazonaws.com`)

### Optional Secrets:

- `AWS_ENDPOINT` = Leave empty for AWS S3, or set for S3-compatible services
- `AWS_USE_PATH_STYLE_ENDPOINT` = `false` for AWS, `true` for S3-compatible services

## Setting Up AWS S3 Bucket

### 1. Create S3 Bucket

1. Log in to AWS Console
2. Go to **S3** service
3. Click **Create bucket**
4. Configure:
   - **Bucket name**: `agrisiti-academy` (or your preferred name)
   - **Region**: Choose closest to your users
   - **Block Public Access**: Uncheck (or configure bucket policy for public read)
   - **Versioning**: Optional
   - **Encryption**: Enable (recommended)

### 2. Configure Bucket Policy (for Public Read)

If you want images to be publicly accessible:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadGetObject",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::your-bucket-name/*"
    }
  ]
}
```

### 3. Configure CORS (if needed for web uploads)

```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "PUT", "POST", "DELETE", "HEAD"],
    "AllowedOrigins": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3000
  }
]
```

### 4. Create IAM User for S3 Access

1. Go to **IAM** → **Users** → **Create user**
2. User name: `agrisiti-academy-s3-user`
3. Select **Programmatic access**
4. Attach policy: `AmazonS3FullAccess` (or create custom policy with minimal permissions)
5. Save the **Access Key ID** and **Secret Access Key**

### 5. Custom IAM Policy (Recommended - More Secure)

Instead of full S3 access, create a custom policy:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject",
        "s3:ListBucket"
      ],
      "Resource": [
        "arn:aws:s3:::your-bucket-name",
        "arn:aws:s3:::your-bucket-name/*"
      ]
    }
  ]
}
```

## Local Development Setup

### Option 1: Use Local Storage (Default)

```env
FILESYSTEM_DISK=public
```

Files will be stored in `storage/app/public` and accessible via `/storage` URL.

### Option 2: Use S3

1. Install AWS SDK (if not already installed):
   ```bash
   composer require league/flysystem-aws-s3-v3 "^3.0"
   ```

2. Add S3 credentials to `.env`:
   ```env
   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=your_key
   AWS_SECRET_ACCESS_KEY=your_secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=your-bucket-name
   AWS_URL=https://your-bucket.s3.us-east-1.amazonaws.com
   ```

3. Test S3 connection:
   ```bash
   php artisan tinker
   Storage::disk('s3')->put('test.txt', 'Hello S3!');
   ```

## Production Setup

The CI/CD pipeline automatically configures S3 if the secrets are set:

1. **Set GitHub Secrets** (as described above)
2. **Deploy** - The pipeline will automatically:
   - Set `FILESYSTEM_DISK=s3` if configured
   - Add all AWS credentials to `.env`
   - Clear config cache to load new settings

## Image Upload Dimensions

When using S3, images are automatically resized to optimal dimensions:

- **Course Images**: 1920×1080px (16:9), Max 2MB
- **Category Images**: 800×800px (1:1), Max 500KB
- **User Avatars**: 400×400px (1:1), Max 200KB

## Switching Between Local and S3

### To Switch to S3:

1. Set `FILESYSTEM_DISK=s3` in `.env`
2. Add AWS credentials
3. Run: `php artisan config:clear`

### To Switch Back to Local:

1. Set `FILESYSTEM_DISK=public` in `.env`
2. Run: `php artisan config:clear`
3. Run: `php artisan storage:link` (if not already linked)

## Troubleshooting

### Issue: "Class 'League\Flysystem\AwsS3V3\AwsS3V3Adapter' not found"

**Solution**: Install the AWS SDK:
```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

### Issue: "Access Denied" when uploading

**Solution**: 
- Check IAM user has correct permissions
- Verify bucket policy allows PutObject
- Check bucket CORS configuration

### Issue: Images not displaying

**Solution**:
- Verify `AWS_URL` is correct
- Check bucket is public or bucket policy allows GetObject
- Verify `FILESYSTEM_DISK` is set to `s3`
- Clear config cache: `php artisan config:clear`

### Issue: "Bucket does not exist"

**Solution**:
- Verify `AWS_BUCKET` name is correct
- Check `AWS_DEFAULT_REGION` matches bucket region
- Ensure bucket exists in AWS Console

## Cost Considerations

- **Storage**: ~$0.023 per GB/month
- **PUT requests**: ~$0.005 per 1,000 requests
- **GET requests**: ~$0.0004 per 1,000 requests
- **Data transfer out**: First 1GB free, then ~$0.09 per GB

For a typical LMS with 1,000 courses and 10,000 users:
- Estimated storage: ~50GB = ~$1.15/month
- Estimated requests: ~100,000/month = ~$0.50/month
- **Total: ~$2-5/month** (very affordable)

## Security Best Practices

1. **Never commit credentials** to Git
2. **Use IAM roles** instead of access keys when possible (for EC2)
3. **Rotate access keys** regularly
4. **Use bucket policies** to restrict access
5. **Enable versioning** for important files
6. **Enable encryption** at rest
7. **Use CloudFront CDN** for better performance and security

## Alternative: S3-Compatible Services

You can use S3-compatible services like:
- **DigitalOcean Spaces**
- **Wasabi**
- **Backblaze B2**
- **MinIO** (self-hosted)

Just set:
```env
AWS_ENDPOINT=https://your-service-endpoint.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

---

**Last Updated:** January 2025

