# Nova Video Manager - Project Context

## Project Overview

**Nova Video Manager** is a WordPress plugin that automatically syncs YouTube videos from a channel and manages them as WordPress content. The plugin serves as a backend data layer only—all front-end presentation is handled by Bricks Builder.

## Project Goals

### Primary Goals
1. **Automated YouTube Synchronization**: Automatically fetch and sync videos from a YouTube channel via the YouTube API
2. **Structured Content Management**: Create and manage videos as a custom post type with full WordPress taxonomy support
3. **Metadata Preservation**: Import and maintain all relevant video metadata (title, description, URL, thumbnail, tags, playlists)
4. **SEO Enhancement**: Enable editing of video descriptions and metadata for SEO/GEO optimization
5. **Community Integration**: Support manual assignment of users/community members to videos with links to profiles or Circle community categories

### Secondary Goals
- Provide reliable, scheduled synchronization
- Handle API rate limits and errors gracefully
- Maintain data integrity between YouTube and WordPress
- Offer intuitive admin interface for configuration and management

## Scope

### In Scope

#### YouTube API Integration
- **OAuth 2.0 authentication** with YouTube API (implemented)
  - Secure token storage with AES-256-CBC encryption
  - Automatic token refresh mechanism
  - CSRF protection with state parameter validation
- **Uploads playlist approach** for fetching videos (implemented)
  - Access to public AND unlisted videos
  - More efficient API usage
  - Includes playlist information in response
- Retrieve comprehensive metadata:
  - Video URL
  - Title
  - Description
  - Tags
  - Playlists (synced as categories)
  - Thumbnail/poster image
  - Duration (for Short vs Video classification)
  - Statistics (views, likes, etc.)
- Scheduled/automated sync process
- Manual sync trigger option

#### WordPress Data Layer
- Custom post type: 'videos' or 'catalog'
- Taxonomy mapping:
  - YouTube playlists → WordPress categories
  - YouTube tags → WordPress tags
  - Video duration → Video type (Short vs Video)
- ACF Pro integration for custom fields
- Store video metadata in appropriate fields
- Featured image handling (video thumbnails)

#### Admin Interface
- Settings page for API configuration
- Sync status and controls
- Video management interface
- User/community member assignment interface (optional feature)

#### Content Management
- Editable video descriptions
- Manual metadata editing
- User/profile assignment functionality

### Out of Scope

- **No front-end output**: No templates, shortcodes, or blocks for public display
- **No video player**: Video playback handled by Bricks/YouTube embed
- **No video hosting**: Videos remain hosted on YouTube
- **No analytics**: YouTube analytics remain on YouTube platform
- **No video upload**: One-way sync from YouTube to WordPress only
- **No comment sync**: YouTube comments not imported

## Technical Requirements

### Must-Haves

1. **YouTube API Integration**
   - Valid YouTube Data API v3 credentials
   - OAuth 2.0 authentication flow
   - API key storage and management
   - Error handling and rate limit management

2. **Custom Post Type**
   - Registered custom post type for videos
   - Support for standard WordPress features (title, content, excerpt, featured image)
   - Public queryable but not publicly displayed (handled by Bricks)

3. **Taxonomies**
   - Categories for YouTube playlists
   - Tags for YouTube video tags
   - Video types for differentiating Shorts (≤60s) from regular videos
   - Proper term creation and assignment

4. **ACF Pro Integration**
   - Field groups for video metadata
   - Fields for: video URL, YouTube ID, publish date, duration, view count, etc.
   - User/community member assignment fields

5. **Sync Mechanism**
   - WP-Cron scheduled sync
   - Manual sync trigger
   - Sync status logging
   - Prevent duplicate posts

6. **Admin Interface**
   - Settings page with API configuration
   - Sync controls and status
   - Clear documentation/help text

7. **Git Updater Compatibility**
   - Proper plugin header structure for Git Updater
   - GitHub/GitLab repository configuration
   - Version management and release tagging
   - Update URI header for Git Updater detection

8. **Security Requirements**
   - **OAuth Token Security**: Encrypted storage of access and refresh tokens in database
   - **Authentication Security**: Proper nonce verification on all admin actions and AJAX requests
   - **Authorization**: Capability checks to ensure only authorized users (administrators) can configure settings and trigger syncs
   - **Input Validation**: Sanitization of all user inputs (API keys, channel IDs, settings)
   - **Output Escaping**: Proper escaping of all outputs to prevent XSS attacks
   - **CSRF Protection**: Protection against Cross-Site Request Forgery attacks
   - **OAuth Flow Security**: Secure redirect handling with state parameter validation to prevent authorization code interception
   - **Credential Protection**: No exposure of API credentials, tokens, or secrets in frontend code, JavaScript, or HTML
   - **Token Refresh Security**: Secure token refresh mechanism with proper error handling
   - **Rate Limiting**: Protection against abuse through rate limiting on sync operations
   - **Audit Logging**: Logging of authentication events and sync operations for security monitoring
   - **SQL Injection Prevention**: Use of prepared statements for all database queries
   - **Data Encryption**: No storage of sensitive data (tokens, API keys) in plain text
   - **Secure Communication**: All API communications over HTTPS
   - **Error Handling**: No exposure of sensitive information in error messages
   - **Session Security**: Proper session handling during OAuth flow
   - **Least Privilege**: Request only necessary OAuth scopes (readonly access to YouTube data)

### Nice-to-Haves

- Sync history/log viewer
- Selective sync (by playlist, date range, etc.)
- Bulk edit capabilities
- Import existing videos (historical sync)
- Webhook support for real-time sync
- Multi-channel support
- Automatic changelog generation for releases

## Risks & Mitigation

### Technical Risks

1. **YouTube API Rate Limits**
   - **Risk**: Exceeding quota limits, especially with large channels
   - **Mitigation**: Implement intelligent caching, batch requests, and configurable sync frequency

2. **API Authentication Complexity**
   - **Risk**: OAuth flow can be complex for non-technical users
   - **Mitigation**: Provide clear documentation, consider API key option for read-only access

3. **Data Sync Conflicts**
   - **Risk**: Manual edits in WordPress overwritten by sync
   - **Mitigation**: Track which fields are manually edited and exclude from sync updates

4. **Large Channel Performance**
   - **Risk**: Channels with thousands of videos may cause timeouts or memory issues
   - **Mitigation**: Implement pagination, batch processing, and background processing

5. **ACF Pro Dependency**
   - **Risk**: Plugin requires ACF Pro to function
   - **Mitigation**: Clear documentation of dependencies, graceful degradation or activation checks

### Security Risks

1. **OAuth Token Compromise**
   - **Risk**: Stolen access tokens could allow unauthorized access to YouTube channel data
   - **Mitigation**: Encrypt tokens at rest, use HTTPS only, implement token rotation, limit OAuth scopes to read-only

2. **Credential Exposure**
   - **Risk**: API keys or tokens exposed in code, logs, or frontend
   - **Mitigation**: Never output credentials in HTML/JS, sanitize error messages, use WordPress options API with encryption

3. **CSRF Attacks**
   - **Risk**: Malicious sites could trigger unauthorized sync operations or settings changes
   - **Mitigation**: Implement WordPress nonces on all forms and AJAX requests, verify referrer

4. **XSS Vulnerabilities**
   - **Risk**: Malicious scripts injected through video titles, descriptions, or tags
   - **Mitigation**: Escape all output using WordPress escaping functions, sanitize all inputs

5. **SQL Injection**
   - **Risk**: Malicious data could compromise database
   - **Mitigation**: Use WordPress prepared statements exclusively, never concatenate SQL queries

6. **Privilege Escalation**
   - **Risk**: Non-admin users gaining access to plugin settings or sync functions
   - **Mitigation**: Check user capabilities (manage_options) on all admin pages and AJAX handlers

7. **OAuth Redirect Attacks**
   - **Risk**: Authorization code interception through redirect manipulation
   - **Mitigation**: Validate state parameter, whitelist redirect URIs, use PKCE if supported

8. **Rate Limit Abuse**
   - **Risk**: Malicious actors triggering excessive API calls to exhaust quota or cause DoS
   - **Mitigation**: Implement rate limiting, require authentication for sync triggers, log suspicious activity

### Business Risks

1. **YouTube API Changes**
   - **Risk**: Google may change or deprecate API endpoints
   - **Mitigation**: Follow API versioning, monitor Google announcements, build abstraction layer

2. **API Costs**
   - **Risk**: YouTube API quota may require paid tier for heavy usage
   - **Mitigation**: Optimize API calls, document quota usage, provide usage monitoring

## Dependencies

### Required
- WordPress 6.0+
- PHP 8.0+
- ACF Pro 6.0+
- YouTube Data API v3 access
- WP-Cron or alternative cron system
- Git Updater plugin (for updates and distribution)

### Recommended
- Bricks Builder (for front-end display)
- SSL certificate (required for OAuth)

## Success Criteria

1. Videos automatically sync from YouTube channel within configured interval
2. All metadata accurately imported and mapped to WordPress structures
3. Descriptions editable without being overwritten on subsequent syncs
4. Admin interface is intuitive and requires minimal technical knowledge
5. Plugin handles errors gracefully without breaking site
6. Performance remains acceptable with 100+ videos
7. Bricks can query and display video data using standard WordPress queries

## Future Considerations

- Support for multiple YouTube channels
- Integration with other video platforms (Vimeo, Wistia)
- Advanced filtering and search capabilities
- Video series/course structuring
- Transcript import and management
- Multi-language support for international content

