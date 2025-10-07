# BunnyCDN Webhook Integration

This document outlines the integration of BunnyCDN webhooks for video status updates in the Shoot90 application.

## Overview

BunnyCDN provides a webhook system that notifies our application when a video's processing status changes. This allows us to update our database and notify users in real-time about their video processing status.

## Webhook Endpoint

The webhook endpoint is:

```
POST /webhook/bunny-video-status
```

This endpoint is publicly accessible (no authentication required) as it is called by BunnyCDN's servers.

## Status Codes

BunnyCDN sends the following status codes:

| Status Code | Description | Our Internal Status |
|-------------|-------------|---------------------|
| 0 | Created | queued |
| 1 | Uploaded | processing |
| 2 | Processing | processing |
| 3 | Finished | finished |
| 4 | Resolution Finished | available |
| 5 | Failed | failed |
| 6 | Deleted | deleted |

## Webhook Payload

Example payload from BunnyCDN:

```json
{
  "VideoGuid": "123e4567-e89b-12d3-a456-426614174000",
  "Status": 3,
  "LibraryId": "12345",
  "DateCreated": "2023-01-01T12:00:00Z",
  "AvailableResolutions": "360p,480p,720p",
  "Width": 1280,
  "Height": 720,
  "Duration": 120.5,
  "ThumbnailCount": 5,
  "HasMP4Fallback": true,
  "Framerate": 30,
  "EncodingTime": 60,
  "StorageSize": 1024000,
  "TranscodedStorageSize": 2048000,
  "CaptionsEnabled": false,
  "MimeType": "video/mp4",
  "OriginalFilename": "sample_video.mp4",
  "Title": "Sample Video",
  "MetaData": {
    "Description": "This is a sample video for testing",
    "Tags": ["test", "sample", "video"]
  }
}
```

## Implementation

The webhook is handled by the `BunnyWebhookController` which:

1. Validates the incoming webhook payload
2. Finds the corresponding video in our database using the `VideoGuid`
3. Updates the video status based on the BunnyCDN status code
4. Broadcasts events to notify users about status changes:
   - `VideoProcessingCompleted` when processing is successful
   - `VideoProcessingFailed` when processing fails

## Events

### VideoProcessingCompleted

Triggered when a video has been successfully processed. This event is broadcast on a private channel for the video owner.

### VideoProcessingFailed

Triggered when video processing has failed. This event is broadcast on a private channel for the video owner and includes error details.

## Testing

A test script is provided at `tests/bunny_webhook_test.php` to simulate webhook calls from BunnyCDN. To use it:

1. Replace the `YOUR_VIDEO_GUID` with an actual video GUID from your database
2. Make sure your Laravel server is running
3. Run the script with: `php tests/bunny_webhook_test.php`
4. Check your application logs for webhook processing details

## Configuration

No additional configuration is required for the webhook itself, but ensure that your BunnyCDN account is properly configured to send webhooks to your application's endpoint.

## Security Considerations

The webhook endpoint is publicly accessible, but it only processes requests with valid BunnyCDN payload structures. In a production environment, consider implementing additional security measures such as:

1. IP whitelisting for BunnyCDN servers
2. Webhook signing/verification using a shared secret
3. Rate limiting to prevent abuse
