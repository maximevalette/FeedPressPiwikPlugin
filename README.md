# FeedPress Matomo Plugin

## Description

This plugin displays your subscribers analytics from FeedPress directly into your Matomo dashboard.

Requires Matomo 6 and PHP 8.1 or later. Use the 2.x versions (`master` branch) for Matomo 5. Connecting a FeedPress account requires admin access to the website; the widget is visible to users with view access.

## Changelog

### 3.0.0

- Matomo 6 only: the Marketplace no longer accepts a version declaring both Matomo 5 and 6. Matomo 5 users stay on 2.x.
- Requires PHP 8.1 or later, like Matomo 6.

### 2.0.0

- Ported to Matomo 5 and 6 (the plugin previously required Piwik 2.x).
- Includes the security fixes of 1.0.3, reported by ipankwave88. Thanks!

### 1.0.3

- Security: the `FeedPress.getSubscribers` API and the widget now require view access to the website (they were reachable anonymously).
- Security: connecting, selecting a feed and logging out now require admin access to the website and are protected against CSRF.
- Security: FeedPress API errors are no longer passed back to the caller, and feed names are escaped before display.
- Use the current FeedPress API endpoint over HTTPS (`https://api.feedpress.com`).

### 1.0.2

- Removed deprecated Period::factory()

### 1.0.1

- Using Piwik\Http instead of cURL.
- Check in case the feed is too young (less than 10 days old).

### 1.0.0

- First version. Enjoy!

## Support

Please direct any feedback to support@feedpress.it