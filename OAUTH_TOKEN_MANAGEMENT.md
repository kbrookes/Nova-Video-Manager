# OAuth Token Management

## Understanding OAuth Tokens

The Nova Video Manager uses Google OAuth 2.0 to access your YouTube channel. This involves two types of tokens:

### 1. Access Token
- **Lifetime**: 1 hour (fixed by Google, cannot be changed)
- **Purpose**: Used for API requests to YouTube
- **Auto-refresh**: Yes, automatically refreshed using the refresh token

### 2. Refresh Token
- **Lifetime**: Can last indefinitely, but may expire if:
  - Not used for 6 months
  - User revokes access in Google Account settings
  - OAuth consent screen settings change
  - Client credentials are regenerated
- **Purpose**: Used to get new access tokens without re-authenticating

## How Token Refresh Works

The plugin automatically handles token refresh:

1. Before each API request, it checks if the access token is expired or expiring soon (5-minute buffer)
2. If expired, it uses the refresh token to get a new access token
3. The new access token is stored and used for subsequent requests
4. This happens transparently in the background

## Why OAuth "Times Out"

If you're experiencing OAuth timeouts every couple of weeks, it's likely because:

1. **Refresh Token Expired**: Google refresh tokens can expire after 6 months of inactivity
2. **User Revoked Access**: Someone revoked access in Google Account settings
3. **Client Credentials Changed**: OAuth credentials were regenerated in Google Cloud Console
4. **Encryption Key Changed**: WordPress salts changed (rare, but causes decryption failure)

## Monitoring Token Health

The plugin now includes enhanced logging and monitoring:

### In the Settings Page
- Shows when you authenticated
- Displays a warning if authentication is > 5 months old
- Suggests re-authentication if needed

### In Debug Logs
Look for these messages in `/wp-content/debug.log`:

**Successful refresh:**
```
NVM OAuth - Access token expired or expiring soon, attempting refresh...
NVM OAuth - Attempting to refresh access token...
NVM OAuth - Refresh response code: 200
NVM OAuth - Access token refreshed successfully
NVM OAuth - Token refresh successful
```

**Failed refresh (needs re-authentication):**
```
NVM OAuth - Access token expired or expiring soon, attempting refresh...
NVM OAuth - Attempting to refresh access token...
NVM OAuth - Refresh response code: 400
NVM OAuth - Refresh failed: invalid_grant
NVM OAuth - Refresh token is invalid or expired. User needs to re-authenticate.
NVM OAuth - Token refresh FAILED: invalid_grant
NVM OAuth - You need to re-authenticate. Go to Videos → Settings and click "Connect to YouTube"
```

## Best Practices

### 1. Monitor Your Logs
Check `/wp-content/debug.log` regularly for OAuth errors. The plugin now logs detailed information about token refresh attempts.

### 2. Re-authenticate Periodically
If you see the warning in Settings that your authentication is old (> 5 months), consider disconnecting and reconnecting to get a fresh refresh token.

### 3. Don't Change OAuth Credentials
Once set up, avoid regenerating your OAuth credentials in Google Cloud Console. If you must change them:
1. Update the credentials in plugin settings
2. Disconnect from YouTube
3. Reconnect to get new tokens

### 4. Keep WordPress Salts Stable
The plugin encrypts tokens using WordPress authentication salts. If these change, tokens can't be decrypted and you'll need to re-authenticate.

## Troubleshooting

### "OAuth keeps timing out every couple of weeks"

**Diagnosis:**
1. Check debug.log for refresh errors
2. Look for "invalid_grant" errors
3. Check when you last authenticated (Settings page)

**Solution:**
1. Go to Videos → Settings
2. Click "Disconnect"
3. Click "Connect to YouTube"
4. Authorize again

This gives you a fresh refresh token that should last much longer.

### "Sync fails with authentication error"

**Check:**
1. Is OAuth connected? (Settings page should show "Connected to YouTube")
2. Any errors in debug.log?
3. Is the warning showing about old authentication?

**Fix:**
Re-authenticate as described above.

### "Token refresh fails immediately after connecting"

**Possible causes:**
1. OAuth credentials are incorrect
2. Redirect URI mismatch
3. Client secret was copied incorrectly

**Fix:**
1. Verify OAuth credentials in Google Cloud Console
2. Ensure redirect URI matches exactly
3. Re-enter client secret (copy-paste carefully)
4. Disconnect and reconnect

## Technical Details

### Token Storage
- Tokens are encrypted using AES-256-CBC
- Encryption key is derived from WordPress authentication salts
- Stored in WordPress options table

### Token Refresh Logic
- Access tokens are refreshed 5 minutes before expiry
- Refresh happens automatically on any API request
- Failed refresh returns `false` and logs detailed error
- User must re-authenticate if refresh token is invalid

### Error Handling
- All OAuth errors are logged to debug.log
- User-friendly messages shown in admin UI
- Automatic retry logic for transient failures
- Clear instructions when re-authentication needed

## Support

If you continue to experience OAuth timeout issues after following this guide:

1. Enable WordPress debug logging (if not already enabled)
2. Reproduce the issue
3. Check debug.log for OAuth-related errors
4. Share the relevant log entries for support

