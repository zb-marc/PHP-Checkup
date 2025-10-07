# AS PHP Checkup

[![WordPress Version](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.2.0-orange.svg)](https://akkusys.de)

**Intelligent PHP configuration checker with automatic solution provider, one-click fixes, and configuration generators for WordPress.**

## 🚀 Features

### Core Features
- **Comprehensive PHP Configuration Check**: Analyzes 30+ critical PHP settings
- **Intelligent Plugin Analysis**: Scans active plugins for PHP requirements and adjusts recommendations
- **One-Click Solutions**: Automatically applies fixes to your server configuration
- **Configuration Generator**: Creates optimized config files for various server types
- **Server Detection**: Automatically identifies your hosting provider and server type
- **Health Score Dashboard**: Visual representation of your PHP configuration health
- **Multi-Environment Support**: Works with Apache, NGINX, LiteSpeed, IIS, and more

### Solution Types
- **Automatic Fixes**: Direct application of PHP configurations
- **Configuration Downloads**: Generate ready-to-use config files
- **Hosting-Specific Guides**: Step-by-step instructions for major hosting providers
- **Advanced Configurations**: Docker, Kubernetes, and custom setups

### Supported Hosting Providers
- WP Engine
- Kinsta
- SiteGround
- Cloudways
- GoDaddy
- Bluehost
- DreamHost
- HostGator
- WordPress.com
- Flywheel
- Pantheon
- Platform.sh
- GridPane
- RunCloud
- ServerPilot
- Plesk
- cPanel
- Generic/Custom Hosting

## 📋 Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Administrator access to WordPress

## 📦 Installation

### Via WordPress Admin
1. Download the latest release from [GitHub Releases](https://github.com/zb-marc/PHP-Checkup/releases)
2. Navigate to **Plugins → Add New** in your WordPress admin
3. Click **Upload Plugin** and select the downloaded ZIP file
4. Click **Install Now** and then **Activate**

### Via FTP
1. Download and extract the plugin ZIP file
2. Upload the `as-php-checkup` folder to `/wp-content/plugins/`
3. Navigate to **Plugins** in your WordPress admin
4. Locate **AS PHP Checkup** and click **Activate**

### Via Composer
```bash
composer require akkusys/as-php-checkup
```

### Via WP-CLI
```bash
wp plugin install https://github.com/zb-marc/PHP-Checkup/archive/main.zip --activate
```

## 🎯 Usage

### Basic Usage

1. **Access the Tool**: Navigate to **Tools → PHP Checkup** in your WordPress admin
2. **Review Status**: Check your overall health score and identified issues
3. **Apply Solutions**: Click on available solutions to fix issues automatically
4. **Download Configs**: Generate configuration files for manual implementation

### Dashboard Widget

The plugin adds a widget to your WordPress dashboard showing:
- Current PHP version and status
- Critical issues count
- Quick access to the full checkup page
- Auto-refresh capability

### WP-CLI Commands

```bash
# Check PHP configuration status
wp as-php-checkup status

# Analyze plugin requirements
wp as-php-checkup analyze

# Get system information
wp as-php-checkup system

# Export detailed report
wp as-php-checkup export --format=csv

# Quick check with exit codes for CI/CD
wp as-php-checkup check
```

### REST API Endpoints

```javascript
// Get current status and health score
GET /wp-json/as-php-checkup/v1/status

// Get system information
GET /wp-json/as-php-checkup/v1/system-info

// Get plugin requirements analysis
GET /wp-json/as-php-checkup/v1/plugin-analysis

// Refresh the check
POST /wp-json/as-php-checkup/v1/refresh

// Export report (JSON/CSV)
GET /wp-json/as-php-checkup/v1/export?format=csv
```

## 🔧 Configuration Options

### Checked PHP Settings

#### Basic Settings
- PHP Version
- Memory Limit
- Max Execution Time
- Max Input Time
- Max Input Vars
- Post Max Size
- Upload Max Filesize

#### Session Configuration
- Session Auto Start
- Session GC Maxlifetime
- Session Save Handler

#### OPcache Settings
- OPcache Enable
- OPcache Memory Consumption
- OPcache Interned Strings Buffer
- OPcache Max Accelerated Files
- OPcache Revalidate Frequency
- OPcache Enable CLI

#### Performance Settings
- Realpath Cache Size
- Realpath Cache TTL
- Output Buffering
- Zlib Output Compression

## 🛠️ Advanced Features

### Automatic Plugin Analysis

The plugin automatically scans your active plugins for:
- Required PHP version
- Memory requirements
- PHP extension dependencies
- Specific configuration needs

### One-Click Solutions

Available automatic fixes:
- **php.ini Generation**: Creates optimized PHP configuration
- **.htaccess Updates**: Adds PHP directives for Apache/LiteSpeed
- **wp-config.php Constants**: Inserts WordPress memory constants
- **NGINX Configurations**: Generates server block configs

### Configuration Export Formats

- **php.ini**: Standard PHP configuration file
- **.user.ini**: Per-directory PHP configuration
- **.htaccess**: Apache/LiteSpeed directives
- **NGINX Config**: Server block configuration
- **Docker Compose**: Container configuration
- **Kubernetes ConfigMap**: K8s deployment configs
- **wp-config Constants**: WordPress-specific settings

## 🔒 Security

The plugin implements multiple security measures:
- Nonce verification on all AJAX requests
- Capability checks (`manage_options`)
- Input sanitization and validation
- Output escaping
- File write permission checks
- Automatic backup creation before modifications

## 🌍 Internationalization

The plugin is fully translatable and includes:
- Text domain: `as-php-checkup`
- POT file for translations
- Support for RTL languages

### Available Translations
- English (default)
- German (de_DE) - Coming soon
- French (fr_FR) - Coming soon
- Spanish (es_ES) - Coming soon

## 📊 Performance Impact

- **Lightweight**: < 500KB total size
- **Cached Results**: Reduces server load
- **Lazy Loading**: Only loads assets on plugin pages
- **Optimized Queries**: Minimal database usage
- **Background Processing**: Plugin analysis runs asynchronously

## 🐛 Troubleshooting

### Common Issues

**Issue**: Can't apply automatic fixes
- **Solution**: Check file permissions for php.ini, .htaccess, and wp-config.php

**Issue**: Plugin analysis not showing
- **Solution**: Click "Re-Analyze Plugins" button to trigger manual scan

**Issue**: Solutions not appearing
- **Solution**: Ensure you have at least one non-optimal setting

**Issue**: REST API not responding
- **Solution**: Check permalinks settings and REST API availability

### Debug Mode

Enable debug logging by adding to `wp-config.php`:
```php
define( 'AS_PHP_CHECKUP_DEBUG', true );
```

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/NewFeature`)
3. Commit your changes (`git commit -m 'Add NewFeature'`)
4. Push to the branch (`git push origin feature/NewFeature`)
5. Open a Pull Request

### Development Setup

```bash
# Clone repository
git clone https://github.com/zb-marc/PHP-Checkup.git

# Install dependencies
cd PHP-Checkup
composer install
npm install

# Build assets
npm run build

# Run tests
composer test
npm test

# Check coding standards
composer phpcs
npm run lint
```

## 📝 Changelog

### Version 1.2.0 (2025-01-XX)
- ✨ Added automatic solution provider
- ✨ One-click configuration fixes
- ✨ Server and hosting detection
- ✨ Advanced configuration generators
- ✨ Configuration preview modal
- 🎨 Improved UI with solution cards
- 🔧 Added write permission checks

### Version 1.1.0 (2024-12-XX)
- ✨ Added plugin requirements analyzer
- ✨ REST API implementation
- ✨ WP-CLI command support
- ✨ Dashboard widget
- 📊 Daily automatic plugin analysis
- 🎨 Visual health score indicator

### Version 1.0.0 (2024-11-XX)
- 🎉 Initial release
- ✅ Basic PHP configuration checks
- 📊 System information display
- 📥 CSV export functionality
- 🌍 Internationalization support

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 👨‍💻 Author

**Marc Mirschel**
- Website: [https://mirschel.biz](https://mirschel.biz)
- Company: [ZooBrothers](https://zoobro.de)
- Email: marc@mirschel.biz
- GitHub: [@zb-marc](https://github.com/zb-marc)

## 🙏 Credits

- WordPress Core Team for the Plugin API
- PHP Community for configuration best practices
- All contributors and testers

## 💬 Support

- **Documentation**: [GitHub Wiki](https://github.com/zb-marc/PHP-Checkup/wiki)
- **Issues**: [GitHub Issues](https://github.com/zb-marc/PHP-Checkup/issues)
- **Forum**: [WordPress Support Forum](https://wordpress.org/support/plugin/as-php-checkup/)
- **Email**: support@akkusys.de

## 🎯 Roadmap

### Version 1.3.0 (Planned)
- [ ] Backup system before applying changes
- [ ] Rollback functionality
- [ ] Cloud configuration profiles
- [ ] Performance benchmarking
- [ ] Email notifications for critical issues

### Version 1.4.0 (Future)
- [ ] Multi-site network support
- [ ] Scheduled configuration checks
- [ ] Configuration history tracking
- [ ] Advanced reporting dashboard
- [ ] Integration with popular hosting APIs

## 📸 Screenshots

1. **Main Dashboard** - Overview with health score and status cards
2. **Solutions Section** - Available automatic fixes and downloads
3. **Configuration Results** - Detailed PHP settings comparison
4. **System Information** - Server and WordPress details
5. **Dashboard Widget** - Quick status overview
6. **WP-CLI Output** - Command-line interface results

## 🏆 Badges & Certifications

- WordPress Plugin Directory Ready
- WPCS (WordPress Coding Standards) Compliant
- GDPR Compliant (No personal data collection)
- Accessibility Ready (WCAG 2.1 Level AA)
- Translation Ready

---

**Made with ❤️ by [Marc Mirschel](https://mirschel.biz)**

*If you find this plugin useful, please consider [⭐ starring the repository](https://github.com/zb-marc/PHP-Checkup) and [writing a review](https://wordpress.org/support/plugin/as-php-checkup/reviews/).*
