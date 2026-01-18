# S3 CORS Configuration Guide

## Problem
When uploading files to S3 from the browser, you may encounter CORS errors. This happens because the S3 bucket needs to be configured to allow cross-origin requests from your application domain.

## Solution: Configure CORS on Your S3 Bucket

### Step 1: Access S3 Bucket CORS Settings

1. Log in to AWS Console
2. Navigate to **S3** service
3. Click on your bucket name (the one configured in `AWS_BUCKET`)
4. Go to the **Permissions** tab
5. Scroll down to **Cross-origin resource sharing (CORS)**
6. Click **Edit**

### Step 2: Add CORS Configuration

Paste the following CORS configuration JSON:

```json
[
    {
        "AllowedHeaders": [
            "*"
        ],
        "AllowedMethods": [
            "GET",
            "PUT",
            "POST",
            "DELETE",
            "HEAD"
        ],
        "AllowedOrigins": [
            "https://academy-backends.agrisiti.com",
            "http://localhost:8000",
            "http://127.0.0.1:8000"
        ],
        "ExposeHeaders": [
            "ETag",
            "x-amz-server-side-encryption",
            "x-amz-request-id",
            "x-amz-id-2"
        ],
        "MaxAgeSeconds": 3000
    }
]
```

### Step 3: Update AllowedOrigins

**Important:** Update the `AllowedOrigins` array to include:
- Your production domain: `https://academy-backends.agrisiti.com`
- Your admin panel domain (if different)
- Any other domains that need to upload files

For development, you can temporarily add:
- `http://localhost:8000`
- `http://127.0.0.1:8000`

**For production, remove localhost origins for security.**

### Step 4: Save Configuration

Click **Save changes** to apply the CORS configuration.

## Alternative: Using AWS CLI

If you prefer using the command line:

```bash
# Create a CORS configuration file (cors.json)
cat > cors.json << 'EOF'
{
    "CORSRules": [
        {
            "AllowedHeaders": ["*"],
            "AllowedMethods": ["GET", "PUT", "POST", "DELETE", "HEAD"],
            "AllowedOrigins": [
                "https://academy-backends.agrisiti.com"
            ],
            "ExposeHeaders": [
                "ETag",
                "x-amz-server-side-encryption",
                "x-amz-request-id",
                "x-amz-id-2"
            ],
            "MaxAgeSeconds": 3000
        }
    ]
}
EOF

# Apply CORS configuration
aws s3api put-bucket-cors --bucket YOUR_BUCKET_NAME --cors-configuration file://cors.json
```

## Verify CORS Configuration

After configuring CORS, test by:

1. Opening browser developer tools (F12)
2. Going to the Network tab
3. Attempting to upload a file
4. Checking if CORS errors are resolved

## Additional S3 Bucket Policy (if needed)

If you still have permission issues, ensure your bucket policy allows public read access:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicReadGetObject",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::YOUR_BUCKET_NAME/*"
        },
        {
            "Sid": "AllowPutObject",
            "Effect": "Allow",
            "Principal": {
                "AWS": "arn:aws:iam::YOUR_ACCOUNT_ID:user/YOUR_IAM_USER"
            },
            "Action": [
                "s3:PutObject",
                "s3:PutObjectAcl"
            ],
            "Resource": "arn:aws:s3:::YOUR_BUCKET_NAME/*"
        }
    ]
}
```

**Note:** Replace `YOUR_BUCKET_NAME`, `YOUR_ACCOUNT_ID`, and `YOUR_IAM_USER` with your actual values.

## Troubleshooting

### Still getting CORS errors?

1. **Clear browser cache** - CORS settings are cached
2. **Check bucket region** - Ensure `AWS_DEFAULT_REGION` matches your bucket region
3. **Verify IAM permissions** - Your IAM user needs `s3:PutObject` and `s3:PutObjectAcl` permissions
4. **Check bucket policy** - Ensure it allows the operations you need
5. **Verify domain** - Make sure the origin in CORS matches exactly (including protocol: https vs http)

### Files still uploading as private?

The code has been updated to explicitly set `visibility('public')` on all FileUpload components. If files are still private:

1. Check that the IAM user has `s3:PutObjectAcl` permission
2. Verify the bucket allows public ACLs (check "Block public access" settings)
3. Ensure the code changes have been deployed

