# Troubleshooting Guide

## Common Issues and Solutions

### Plugin Activation Errors

#### "Class not found" errors
**Symptom:** Fatal error about missing classes (NVM_Post_Type, NVM_Taxonomies, etc.)

**Solution:**
1. Deactivate the plugin
2. Make sure all files are uploaded correctly
3. Check file permissions (should be 644 for files, 755 for directories)
4. Re-activate the plugin

#### "ACF Pro is required" notice
**Symptom:** Admin notice saying ACF Pro is required

**Solution:**
1. Install Advanced Custom Fields Pro
2. Activate ACF Pro
3. Refresh the page

### YouTube API Issues

#### "API key not valid" error
**Possible causes:**
- Incorrect API key
- API key has restrictions that block the request
- YouTube Data API v3 not enabled

**Solution:**
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project
3. Go to "APIs & Services" → "Credentials"
4. Verify your API key
5. Check "APIs & Services" → "Library" and ensure YouTube Data API v3 is enabled
6. If using API restrictions, make sure YouTube Data API v3 is allowed

#### "Invalid channel ID" error
**Possible causes:**
- Wrong channel ID format
- Using channel handle instead of ID
- Using custom URL instead of ID

**Solution:**
1. Go to [YouTube Studio](https://studio.youtube.com/)
2. Click Settings → Channel → Advanced settings
3. Copy the "Channel ID" (should look like: UC...)
4. Paste into plugin settings

#### "Quota exceeded" error
**Symptom:** Sync fails with quota exceeded message

**Solution:**
- YouTube API has daily quotas (default: 10,000 units/day)
- Each video list request costs ~100 units
- Each video details request costs ~1 unit per video
- Wait 24 hours for quota to reset
- Consider reducing sync frequency
- Request quota increase from Google Cloud Console

### Sync Issues

#### Videos not syncing automatically
**Possible causes:**
- Auto-sync not enabled
- WordPress cron not running
- Server cron disabled

**Solution:**
1. Check Videos → Settings → Enable Auto Sync is checked
2. Install "WP Crontrol" plugin to verify cron jobs
3. Look for "nvm_sync_videos" scheduled event
4. If missing, disable and re-enable auto-sync
5. Check with your host if WP-Cron is disabled (some hosts disable it)

#### Manual sync button does nothing
**Possible causes:**
- JavaScript error
- AJAX blocked
- Permissions issue

**Solution:**
1. Open browser console (F12) and check for JavaScript errors
2. Verify you're logged in as an administrator
3. Check if any security plugins are blocking AJAX requests
4. Try disabling other plugins temporarily

#### Thumbnails not downloading
**Possible causes:**
- Server can't download external files
- Uploads directory not writable
- Firewall blocking YouTube

**Solution:**
1. Check PHP setting `allow_url_fopen` is enabled
2. Verify uploads directory permissions (should be writable)
3. Check server firewall settings
4. Try downloading a thumbnail manually to test connectivity

### Display Issues

#### Videos don't appear in Bricks Builder
**Possible causes:**
- Query not set up correctly
- Post type not selected

**Solution:**
1. In Bricks query builder, select "Post Type" → "Videos"
2. Make sure the query is set to "Custom"
3. Verify videos exist (check Videos → All Videos in admin)

#### ACF fields not showing
**Possible causes:**
- ACF Pro not activated
- Field group not assigned to post type
- Using wrong field names

**Solution:**
1. Verify ACF Pro is active
2. Go to Custom Fields → Field Groups
3. Check "Video Metadata" and "Community Members" groups exist
4. Verify they're assigned to "Videos" post type
5. Use correct field names (e.g., `nvm_youtube_url` not `youtube_url`)

### Performance Issues

#### Sync takes too long
**Possible causes:**
- Large number of videos
- Slow API responses
- Thumbnail downloads timing out

**Solution:**
1. Sync runs in background, so it's okay if it takes time
2. For initial sync of many videos, consider running multiple smaller syncs
3. Check server timeout settings
4. Monitor API quota usage

#### Site slow after activation
**Possible causes:**
- Too frequent sync schedule
- Large number of videos

**Solution:**
1. Reduce sync frequency (change from hourly to daily)
2. Disable auto-sync and use manual sync only
3. Check server resources

### Database Issues

#### Duplicate videos created
**Possible causes:**
- YouTube ID field not set correctly
- Multiple syncs running simultaneously

**Solution:**
1. Delete duplicate posts manually
2. Run sync again (should update existing posts)
3. Avoid running multiple manual syncs at once

#### Old videos not updating
**Possible causes:**
- Description manually modified flag set
- Sync only creates new videos

**Solution:**
- The plugin updates existing videos on each sync
- If description is marked as modified, it won't be overwritten
- Other metadata (views, likes, etc.) always updates

### Debug Mode

To enable WordPress debug mode for more detailed error messages:

1. Edit `wp-config.php`
2. Add or change these lines:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```
3. Check `wp-content/debug.log` for errors

### Getting Help

If you're still experiencing issues:

1. Check the debug log (`wp-content/debug.log`)
2. Note the exact error message
3. Document steps to reproduce
4. Check WordPress and PHP versions meet requirements
5. Try with all other plugins disabled
6. Contact support with the above information

## Useful Plugins for Debugging

- **Query Monitor** - Debug queries, hooks, and performance
- **WP Crontrol** - View and manage WP-Cron events
- **Debug Bar** - Show debug information in admin bar
- **ACF Debug** - Debug ACF field values

