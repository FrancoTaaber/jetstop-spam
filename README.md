# Jetstop Spam

[![CI](https://github.com/FrancoTaaber/jetstop-spam/actions/workflows/ci.yml/badge.svg)](https://github.com/FrancoTaaber/jetstop-spam/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/release/FrancoTaaber/jetstop-spam)](https://github.com/FrancoTaaber/jetstop-spam/releases)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![PHP](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://php.net/)
[![WordPress](https://img.shields.io/badge/wordpress-%3E%3D5.8-21759B.svg)](https://wordpress.org/)

**The most comprehensive anti-spam solution for WordPress.** Protect comments, registrations, logins, and 10+ form plugins with honeypot, rate limiting, blacklists, and more. No CAPTCHA required.

## Features

### Protection Methods

| Method | Description |
|--------|-------------|
| **JS Honeypot** | JavaScript-injected honeypot - bots can't execute JS |
| **Time Check** | Blocks instant submissions (bots submit in milliseconds) |
| **Rate Limiting** | Limits submissions per IP address |
| **IP Blacklist** | Block IPs, CIDR ranges, wildcards |
| **Email Blacklist** | Block emails, domains, patterns |
| **Keyword Blacklist** | Block content with specific words |
| **Disposable Emails** | Blocks 100+ temporary email services |
| **Link Limit** | Limits URLs in submissions |

### Supported Integrations

- WordPress Comments
- WordPress Registration
- WordPress Login
- Contact Form 7
- WPForms
- Gravity Forms
- **Forminator** (Free - competitors charge for this!)
- Elementor Forms
- Fluent Forms
- Ninja Forms
- Formidable Forms
- WooCommerce
- bbPress

## Why Jetstop Spam?

| Feature | Jetstop (FREE) | WP Armour Free | WP Armour Extended ($30/yr) |
|---------|----------------|----------------|----------------------------|
| JS Honeypot | ✅ | ✅ | ✅ |
| 13 Integrations | ✅ | 12 | 30+ |
| **Forminator** | ✅ | ❌ | ✅ |
| **IP Blacklist** | ✅ | ❌ | ✅ |
| **Email Blacklist** | ✅ | ❌ | ✅ |
| **Keyword Blacklist** | ✅ | ❌ | ❌ |
| **Disposable Emails** | ✅ | ❌ | ❌ |
| **Rate Limiting** | ✅ | ❌ | ❌ |
| **Detailed Logging** | ✅ | ❌ | ✅ |
| **Statistics Dashboard** | ✅ | Basic | ✅ |

## Requirements

- WordPress 5.8+
- PHP 7.4+

## Installation

### From GitHub Releases

1. Download `jetstop-spam.zip` from [Releases](https://github.com/FrancoTaaber/jetstop-spam/releases)
2. WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload and activate
4. Go to **Jetstop Spam** in the admin menu

## Configuration

### Dashboard

Overview of blocked spam with statistics:
- Today / Week / Month / All-time counts
- Breakdown by source and reason
- Top blocked IPs

### Settings

Configure protection methods:
- Enable/disable each protection type
- Set rate limits and time thresholds
- Choose which integrations to protect

### Blacklists

Manage IP, email, and keyword blacklists:
- One entry per line
- Supports wildcards and CIDR notation
- Regex patterns for keywords

### Blocked Log

View all blocked submissions:
- Filter by source, reason, or IP
- See submission details
- Export or clear logs

## Development

```bash
# Build distribution ZIP
./build.sh
```

## Hooks

```php
// Add custom disposable email domains
add_filter('jetstop_disposable_domains', function($domains) {
    $domains[] = 'custom-temp-mail.com';
    return $domains;
});

// Pre-check filter (return array to override)
add_filter('jetstop_pre_check', function($result, $data, $source) {
    // Whitelist certain IPs
    if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
        return array('is_spam' => false);
    }
    return null; // Continue with normal checks
}, 10, 3);

// Post-check filter
add_filter('jetstop_check_result', function($result, $data, $source) {
    // Log or modify result
    return $result;
}, 10, 3);
```

## Changelog

### 1.0.0
- Initial release
- 8 protection methods
- 13 form/system integrations
- Dashboard with statistics
- Detailed logging system
- IP/Email/Keyword blacklists
- 100+ disposable email domains

## License

GPL v2 or later. See [LICENSE](LICENSE) file.

## Credits

Developed by [Franco Taaber](https://francotaaber.com)

Part of the WordPress plugin collection:
- [Forminator Export Formats](https://github.com/FrancoTaaber/forminator-export-formats)
- [Forminator Field Widths](https://github.com/FrancoTaaber/forminator-field-widths)
