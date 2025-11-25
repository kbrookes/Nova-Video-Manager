# OAuth 2.0 Setup Guide for Nova Video Manager

This guide will walk you through setting up OAuth 2.0 credentials in Google Cloud Console to enable the Nova Video Manager plugin to access your YouTube channel data, including unlisted videos and playlists.

## Prerequisites

- A Google account with access to the YouTube channel you want to sync
- Access to Google Cloud Console

## Step 1: Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click "Select a project" at the top
3. Click "New Project"
4. Enter a project name (e.g., "Nova Video Manager")
5. Click "Create"

## Step 2: Enable YouTube Data API v3

1. In your project, go to "APIs & Services" > "Library"
2. Search for "YouTube Data API v3"
3. Click on it and click "Enable"

## Step 3: Configure OAuth Consent Screen

1. Go to "APIs & Services" > "OAuth consent screen"
2. Select "External" user type (unless you have a Google Workspace account)
3. Click "Create"
4. Fill in the required fields:
   - **App name**: Nova Video Manager
   - **User support email**: Your email
   - **Developer contact information**: Your email
5. Click "Save and Continue"
6. You'll be taken back to the overview screen - this is normal
7. In the **left sidebar**, look for and click on **"Data access"** (or "Scopes")
8. Click "Add or Remove Scopes" button
9. In the scopes panel, search for "youtube" or scroll to find YouTube Data API v3
10. Add the following scope:
    - `https://www.googleapis.com/auth/youtube.readonly`
    - Or check the box for "YouTube Data API v3" → "Read-only access"
11. Click "Update" to save the scope
12. Click "Save and Continue" (if prompted)
13. In the left sidebar, click on **"Audience"**
14. In the **"Test users"** section, click "Add Users" button
15. Add your Google account email address
    - This is required while the app is in "Testing" mode
    - Add the email address you'll use to authenticate with the plugin
16. Click "Save"

## Step 4: Create OAuth 2.0 Credentials

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth client ID"
3. Select "Web application" as the application type
4. Enter a name (e.g., "Nova Video Manager WordPress")
5. Under "Authorized redirect URIs", click "Add URI"
6. Add your WordPress admin URL (you'll get this from the plugin settings page):
   ```
   https://yourdomain.com/wp-admin/edit.php?post_type=nova_video&page=nvm-settings
   ```
   **Important**: Replace `yourdomain.com` with your actual domain
7. Click "Create"
8. A dialog will appear with your **Client ID** and **Client Secret**
9. **Copy both values** - you'll need them in the next step

## Step 5: Configure Plugin Settings

1. In WordPress admin, go to **Videos > Settings**
2. In the "YouTube OAuth Configuration" section:
   - Paste your **OAuth Client ID**
   - Paste your **OAuth Client Secret**
   - Enter your **YouTube Channel ID**
3. Click "Save Settings"
4. After saving, click the "Connect to YouTube" button
5. You'll be redirected to Google to authorize the app
6. Click "Allow" to grant permissions
7. You'll be redirected back to WordPress
8. You should see "Connected to YouTube" status

## Step 6: Sync Your Videos

1. Once connected, click "Sync Now" to start syncing videos
2. The plugin will now sync:
   - ✅ Public videos
   - ✅ Unlisted videos
   - ✅ Video playlists (as categories)
   - ✅ Video tags
   - ✅ Video metadata
   - ✅ Thumbnails

## Finding Your YouTube Channel ID

If you don't know your YouTube Channel ID:

1. Go to [YouTube Studio](https://studio.youtube.com/)
2. Click "Settings" (gear icon)
3. Click "Channel" > "Advanced settings"
4. Your Channel ID is displayed there

Alternatively, go to your channel page and look at the URL:
- If it's `youtube.com/channel/UC...`, the part after `/channel/` is your Channel ID
- If it's `youtube.com/c/YourName` or `youtube.com/@YourName`, you'll need to use YouTube Studio method

## Security Notes

- Your OAuth tokens are encrypted in the WordPress database
- Only administrators can configure OAuth settings
- You can disconnect at any time by clicking "Disconnect" in the settings
- The plugin only requests read-only access to your YouTube data

## Troubleshooting

### "Redirect URI mismatch" error
- Make sure the redirect URI in Google Cloud Console exactly matches your WordPress admin URL
- Check for `http` vs `https`
- Check for `www` vs non-`www`

### "Access denied" error
- Make sure you added your Google account as a test user in the OAuth consent screen
- Make sure you clicked "Allow" when authorizing

### "YouTube API is not configured" error
- Make sure you saved your OAuth credentials before clicking "Connect to YouTube"
- Make sure you enabled the YouTube Data API v3 in Google Cloud Console

## Support

For issues or questions, please refer to the plugin documentation or contact support.

