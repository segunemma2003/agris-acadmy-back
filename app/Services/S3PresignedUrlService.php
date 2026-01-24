<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;

class S3PresignedUrlService
{
    /**
     * Generate a presigned URL with public-read ACL
     */
    public static function generatePresignedUrlWithPublicAcl(string $path, int $expiration = 3600): string
    {
        $disk = Storage::disk('s3');
        $adapter = $disk->getDriver()->getAdapter();
        
        // Get S3 client from adapter
        $client = $adapter->getClient();
        $bucket = $adapter->getBucket();
        
        // Generate presigned URL with ACL parameter
        $command = $client->getCommand('PutObject', [
            'Bucket' => $bucket,
            'Key' => $path,
            'ACL' => 'public-read',
        ]);
        
        $request = $client->createPresignedRequest($command, '+' . $expiration . ' seconds');
        
        return (string) $request->getUri();
    }
}



