# Video Status & Schedule Synchronization

## Overview

The Nova Video Manager now automatically syncs the publish status and schedule from YouTube to WordPress. This ensures that your WordPress posts match the visibility and scheduling of your YouTube videos.

## How It Works

### YouTube Video States

YouTube videos can have three privacy states:
1. **Public** - Visible to everyone
2. **Unlisted** - Only people with the link can view
3. **Private** - Only you and people you choose can view

Additionally, private videos can be **scheduled** for future publication.

### WordPress Post Status Mapping

The plugin automatically maps YouTube video states to WordPress post statuses:

| YouTube Status | YouTube Schedule | WordPress Status | WordPress Schedule |
|---------------|------------------|------------------|-------------------|
| Public | Already published | `publish` | Uses YouTube publish date |
| Unlisted | Already published | `publish` | Uses YouTube publish date |
| Private | Scheduled (future) | `future` | Uses YouTube scheduled date |
| Private | Scheduled (past) | `draft` | No schedule |
| Private | No schedule | `draft` | No schedule |

### Sync Behavior

**On Every Sync:**
- The plugin checks the YouTube video's `privacyStatus` and `publishAt` fields
- WordPress post status is updated to match YouTube's current state
- If a video is scheduled on YouTube, the WordPress post is scheduled for the same time
- If a video is published on YouTube, the WordPress post is published with the YouTube publish date

**Status Changes:**
- If you change a video from Private to Public on YouTube, the next sync will publish the WordPress post
- If you schedule a video on YouTube, the next sync will schedule the WordPress post
- If you make a Public video Private on YouTube, the next sync will change the WordPress post to Draft

## Examples

### Example 1: Scheduled Video

**YouTube:**
- Privacy: Private
- Scheduled for: January 20, 2026 at 10:00 AM

**WordPress After Sync:**
- Post Status: Future
- Scheduled for: January 20, 2026 at 10:00 AM (in your local timezone)

### Example 2: Published Video

**YouTube:**
- Privacy: Public
- Published: January 15, 2026 at 3:00 PM

**WordPress After Sync:**
- Post Status: Publish
- Post Date: January 15, 2026 at 3:00 PM (in your local timezone)

### Example 3: Private Draft

**YouTube:**
- Privacy: Private
- No schedule set

**WordPress After Sync:**
- Post Status: Draft
- No scheduled date

### Example 4: Unlisted Video

**YouTube:**
- Privacy: Unlisted
- Published: January 10, 2026 at 2:00 PM

**WordPress After Sync:**
- Post Status: Publish
- Post Date: January 10, 2026 at 2:00 PM (in your local timezone)

## Metadata Fields

The plugin stores YouTube status information in custom fields:

- **YouTube Privacy Status** (`nvm_privacy_status`): The current privacy status (public, unlisted, private)
- **YouTube Scheduled Publish Time** (`nvm_scheduled_publish_time`): The scheduled publish time (if set)

These fields are visible in the WordPress admin when editing a video post.

## Workflow Examples

### Publishing a Scheduled Video

1. **On YouTube:** Upload a video as Private and schedule it for next week
2. **Run Sync:** The video appears in WordPress as a Draft with a future publish date
3. **WordPress:** The post will automatically publish at the scheduled time
4. **On YouTube:** The video publishes at the scheduled time
5. **Next Sync:** WordPress post status remains Published

### Making a Video Private

1. **On YouTube:** Change a Public video to Private
2. **Run Sync:** The WordPress post changes from Published to Draft
3. **WordPress:** The post is no longer publicly visible

### Rescheduling a Video

1. **On YouTube:** Change the scheduled publish time
2. **Run Sync:** WordPress post schedule updates to match the new time

## Important Notes

### Timezone Handling

- YouTube uses UTC for all times
- WordPress converts times to your site's configured timezone
- The plugin handles timezone conversion automatically

### Manual Overrides

- If you manually change a WordPress post status, it will be overwritten on the next sync
- To prevent sync from changing post status, you would need to modify the plugin code
- The sync always prioritizes YouTube's status as the source of truth

### Sync Frequency

- Status changes are only detected when a sync runs
- Use Auto Sync to keep statuses in sync automatically
- Manual sync can be triggered anytime from Settings

### Limitations

- WordPress doesn't have an "Unlisted" status, so unlisted videos are treated as Published
- If you want unlisted videos to be drafts, you'll need custom logic

## Troubleshooting

### Video is Published on YouTube but Draft in WordPress

**Check:**
1. Has a sync run since the video was published?
2. Check the debug log for sync errors
3. Verify the video's privacy status on YouTube

**Fix:**
Run a manual sync from Videos → Settings

### Scheduled Video Not Publishing

**Check:**
1. Is WordPress cron running? (required for scheduled posts)
2. Check the scheduled date in WordPress matches YouTube
3. Verify the post status is "Future" not "Draft"

**Fix:**
- Ensure WordPress cron is working (check with a cron plugin)
- Run a manual sync to update the schedule

### Video Status Not Updating

**Check:**
1. Check debug.log for sync errors
2. Verify OAuth is still connected
3. Check if the video exists on YouTube

**Fix:**
- Re-authenticate OAuth if needed
- Run a full sync to refresh all videos

## Debug Logging

The plugin logs status sync activity to `/wp-content/debug.log`:

```
NVM Sync - Video privacy: public, publishAt: , publishedAt: 2026-01-15T15:00:00Z
NVM Sync - Video is public, publishing with date: 2026-01-15 15:00:00
```

Enable WordPress debug logging to see these messages:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

