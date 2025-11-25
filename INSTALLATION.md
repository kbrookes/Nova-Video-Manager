# Installation & Setup Guide

## Prerequisites

Before installing Nova Video Manager, ensure you have:

1. **WordPress 6.0+** installed
2. **PHP 8.0+** on your server
3. **Advanced Custom Fields Pro** plugin installed and activated
4. **Git Updater** plugin installed (for updates)
5. **YouTube Data API v3** credentials

## Step 1: Install the Plugin

### Option A: Manual Installation
1. Download or clone this repository
2. Upload the `Nova-Video-Manager` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress

### Option B: Git Clone
```bash
cd /path/to/wordpress/wp-content/plugins/
git clone [your-repository-url] Nova-Video-Manager
```

## Step 2: Get YouTube API Credentials

### Create a Google Cloud Project
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click "Select a project" → "New Project"
3. Enter a project name (e.g., "Nova Video Manager")
4. Click "Create"

### Enable YouTube Data API v3
1. In your project, go to "APIs & Services" → "Library"
2. Search for "YouTube Data API v3"
3. Click on it and press "Enable"

### Create API Credentials
1. Go to "APIs & Services" → "Credentials"
2. Click "Create Credentials" → "API Key"
3. Copy your API key
4. (Optional but recommended) Click "Restrict Key" to limit usage:
   - Under "API restrictions", select "Restrict key"
   - Choose "YouTube Data API v3"
   - Save

### Find Your YouTube Channel ID
1. Go to [YouTube Studio](https://studio.youtube.com/)
2. Click on "Settings" (bottom left)
3. Click "Channel" → "Advanced settings"
4. Copy your "Channel ID"

**Alternative Method:**
1. Go to your YouTube channel
2. Look at the URL - it will be one of these formats:
   - `youtube.com/channel/[CHANNEL_ID]` - Copy the CHANNEL_ID
   - `youtube.com/c/[CUSTOM_NAME]` or `youtube.com/@[HANDLE]` - You'll need to use the advanced settings method above

## Step 3: Configure the Plugin

1. In WordPress admin, go to **Videos → Settings**
2. Enter your **YouTube API Key**
3. Enter your **YouTube Channel ID**
4. Configure sync settings:
   - **Enable Auto Sync**: Check to enable automatic syncing
   - **Sync Frequency**: Choose how often to sync (hourly, twice daily, or daily)
5. Click **Save Settings**

## Step 4: Initial Sync

1. After saving settings, click the **Sync Now** button
2. Wait for the sync to complete (you'll see a success message)
3. Go to **Videos → All Videos** to see your synced videos

## Step 5: Verify Installation

Check that everything is working:

- [ ] Videos appear in the Videos menu
- [ ] Video thumbnails are displayed
- [ ] ACF fields are populated with YouTube data
- [ ] Categories and tags are created
- [ ] Auto-sync is scheduled (if enabled)

## Troubleshooting

### "ACF Pro is required" error
- Install and activate Advanced Custom Fields Pro
- Refresh the plugins page

### "YouTube API Error: API key not valid"
- Double-check your API key in settings
- Ensure YouTube Data API v3 is enabled in Google Cloud Console
- Check if your API key has restrictions that might block requests

### "Invalid channel ID" error
- Verify your channel ID is correct
- Make sure you're using the channel ID, not the channel name or handle

### Videos not syncing
- Check that auto-sync is enabled in settings
- Verify your API credentials are correct
- Try a manual sync to see specific error messages
- Check WordPress cron is working: Install "WP Crontrol" plugin to verify

### Thumbnails not downloading
- Check your server has write permissions to the uploads folder
- Verify `allow_url_fopen` is enabled in PHP
- Check for firewall/security plugins blocking external requests

## Next Steps

### Display Videos on Your Site

Use Bricks Builder (or your preferred page builder) to create video displays:

1. Create a new page/template
2. Add a query loop for the `nova_video` post type
3. Access video data using ACF fields:
   - `{acf:nvm_youtube_url}` - Video URL
   - `{acf:nvm_duration}` - Duration
   - `{acf:nvm_view_count}` - Views
   - `{acf:nvm_like_count}` - Likes
   - etc.

### Customize Sync Behavior

Edit `includes/class-nvm-sync.php` to customize:
- Which videos to sync (e.g., only public videos)
- Additional metadata to capture
- Custom processing logic

### Add Playlist Syncing

The plugin includes playlist support but it's commented out by default (to reduce API calls). To enable:

1. Open `includes/class-nvm-sync.php`
2. Find line ~197: `// $this->set_video_categories( $post_id, $video_id );`
3. Uncomment this line
4. Save and re-sync

**Note:** This will significantly increase API usage as it requires additional API calls per video.

## Support

For issues or questions, please check the README.md or contact support.

