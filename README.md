# Nova Video Manager

A WordPress plugin that automatically syncs YouTube videos from a channel and manages them as WordPress content with full metadata support.

## Description

Nova Video Manager creates a seamless integration between your YouTube channel and WordPress site. It automatically fetches videos from your YouTube channel and creates WordPress posts with all the relevant metadata, making it easy to display and manage your video content.

**This plugin provides backend functionality only** - all front-end display is handled by your page builder (e.g., Bricks Builder).

## Features

- **Automatic YouTube Sync**: Automatically fetch and sync videos from your YouTube channel (including unlisted videos)
- **OAuth 2.0 Authentication**: Secure authentication to access private and unlisted videos
- **Custom Post Types**:
  - Videos stored as `nova_video` post type
  - Members stored as `nova_member` post type for reusable community member profiles
- **Rich Metadata**: Import video title, description, URL, thumbnail, duration, view count, likes, and comments
- **Taxonomy Support**:
  - YouTube playlists → WordPress categories
  - YouTube tags → WordPress tags
  - Automatic video type classification (Short vs Video based on duration)
- **ACF Integration**: All video metadata stored in ACF fields for easy access
- **Manual Editing**: Edit video descriptions for SEO without sync overwriting your changes
- **Community Members**:
  - Create reusable member profiles with name, profile URL, and Circle.so category
  - Assign multiple members to videos using ACF relationship field
  - Filter videos by member in Bricks Builder
  - Add new members on-the-fly while editing videos
- **Scheduled Sync**: Automatic syncing on configurable schedules (hourly, twice daily, daily)
- **Manual Sync**: Trigger syncs manually from the admin interface
- **Git Updater Compatible**: Easy updates via Git Updater plugin

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- Advanced Custom Fields Pro 6.0+
- YouTube Data API v3 access with OAuth 2.0
- Git Updater plugin (for updates)

## Installation

1. Clone or download this repository to your WordPress plugins directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Videos → Settings
4. Configure your YouTube OAuth 2.0 credentials (see OAUTH_SETUP.md for detailed instructions)
5. Authenticate with YouTube
6. Enable auto-sync or trigger a manual sync

For detailed installation and OAuth setup instructions, see:
- [INSTALLATION.md](INSTALLATION.md)
- [OAUTH_SETUP.md](OAUTH_SETUP.md)

## Configuration

### OAuth 2.0 Setup

This plugin uses OAuth 2.0 to authenticate with YouTube, which allows access to:
- Public videos
- Unlisted videos
- Private videos (if you're the channel owner)

See [OAUTH_SETUP.md](OAUTH_SETUP.md) for complete setup instructions.

### Finding Your Channel ID

1. Go to your YouTube channel
2. Click on your profile icon → Settings → Advanced settings
3. Copy your Channel ID

### Plugin Settings

Navigate to **Videos → Settings** in your WordPress admin:

1. **OAuth Settings**: Configure Client ID and Client Secret
2. **Authenticate**: Click "Authenticate with YouTube" to authorize the plugin
3. **YouTube Channel ID**: Enter your YouTube channel ID
4. **Enable Auto Sync**: Toggle automatic syncing
5. **Sync Frequency**: Choose how often to sync (hourly, twice daily, or daily)

## Usage

### Automatic Sync

Once configured, the plugin will automatically sync new videos based on your chosen frequency.

### Manual Sync

Click the "Sync Now" button in the settings page to manually trigger a sync.

### Managing Members

1. **Create Members**: Go to **Videos → Members → Add New**
   - Enter member name
   - Add profile URL (link to their bio on another site or Circle.so)
   - Add Circle Category (optional)

2. **Assign Members to Videos**:
   - Edit any video
   - Scroll to "Community Members" section
   - Search for existing members or click "+ Add New Member"
   - Select multiple members per video

### Editing Video Content

- **Title**: Can be edited, but will be overwritten on next sync
- **Description**: Edit freely - check the "Description Manually Modified" field to prevent sync from overwriting
- **Metadata**: View-only fields synced from YouTube (views, likes, duration, etc.)
- **Community Members**: Assign members using the relationship field

### Displaying Videos

Use your page builder (Bricks, Elementor, etc.) to query and display the `nova_video` post type. Access all metadata through ACF fields:

**Video Fields:**
- `nvm_youtube_id` - YouTube video ID
- `nvm_youtube_url` - Full YouTube URL
- `nvm_duration` - Video duration
- `nvm_published_at` - Original publish date
- `nvm_view_count` - View count
- `nvm_like_count` - Like count
- `nvm_comment_count` - Comment count
- `nvm_featured_members` - Community members (ACF relationship field)

**Member Fields** (accessible in relationship query loop):
- `post_title` - Member name
- `nvm_member_profile_url` - Profile URL
- `nvm_member_circle_category` - Circle.so category

**Bricks Builder Example:**
1. Create a query loop with "ACF Relationship: Featured Community Members"
2. Inside the loop, add elements with dynamic data:
   - Text: `{post_title}` (member name)
   - Link URL: `{acf_nvm_member_profile_url}`
   - Text: `{acf_nvm_member_circle_category}`

## Taxonomies

- **Categories** (`nova_video_category`): Synced from YouTube playlists
- **Tags** (`nova_video_tag`): Synced from YouTube video tags
- **Video Types** (`nova_video_type`): Automatically classified as "Short" (≤60s) or "Video" (>60s)

## Changelog

### 0.2.0
- Added Members custom post type for reusable community member profiles
- Changed community members from repeater field to ACF relationship field
- Added ability to create members on-the-fly while editing videos
- Added support for unlisted videos via OAuth 2.0
- Fixed video sync to use `channelId` parameter to restrict to specific channel
- Added REST API support for member fields (Bricks Builder compatibility)
- Added custom admin columns for Members list
- Improved member field accessibility in Bricks Builder query loops

### 0.1.0
- Initial release
- OAuth 2.0 authentication for YouTube API
- Custom post type and taxonomies
- ACF field groups
- Automatic and manual sync
- WP-Cron scheduling
- Admin settings interface
- Video type classification (Short vs Video)

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/kbrookes/Nova-Video-Manager).

## License

GPL v2 or later

## Credits

Developed by Kelsey Brookes for Nova Video Management