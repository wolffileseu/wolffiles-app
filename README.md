# 🐺 Wolffiles.eu

![CI](https://github.com/wolffileseu/wolffiles-app/actions/workflows/check.yml/badge.svg)

The ultimate community platform for classic Wolfenstein games — **Enemy Territory** and **Return to Castle Wolfenstein**.

---

## 🎮 About

Wolffiles.eu is a comprehensive file repository and gaming hub serving the ET/RtCW community since day one. We host over **4,900 files** totaling **169+ GB** of maps, mods, scripts, and tools — preserving classic gaming content while building modern community features.

## ✨ Features

### 📁 File Repository
- Massive archive of maps, mods, skins, sounds, utilities and full game versions
- Support for ET, RtCW, ET Legacy, ETe, True Combat Elite, ET Quake Wars and more
- Automated file analysis with PK3/ZIP content extraction
- Screenshot generation and BSP map metadata parsing
- Download tracking with statistics and trending content
- File approval workflow with auto-approve for trusted uploaders

### 🗺️ 3D Map Viewer
- Interactive BSP map preview directly in the browser
- Powered by a custom WebGL-based Quake 3 BSP renderer
- View map geometry, textures and layout before downloading

### 🤖 Omni-Bot Waypoint Browser
- Browse waypoints for **1,040+ ET maps** and **120+ RtCW maps**
- Automated GitHub synchronization to keep waypoints up to date
- Direct download of waypoint files for bot navigation

### 📡 Server Tracker
- Live game server tracking with real-time player counts
- ELO-based ranking system for competitive players
- Player session tracking with aliases and statistics
- Clan detection and clan pages
- Server history, map statistics and peak player tracking
- Master server polling for ET and RtCW

### 🎬 ETTV Streaming
- ETTV slot management integrated with Pterodactyl
- Live match streaming for Enemy Territory
- Event scheduling and management

### 🎥 Demo Browser
- Upload and browse game demos
- Categorized demo archive with metadata

### 🌐 FastDL Hosting
- Fast download server for game files
- Clan-specific FastDL directories
- Automated PK3 extraction from ZIP archives
- Game-specific directory management

### 🖥️ Game Server Hosting
- Hosted game servers via Pterodactyl panel integration
- Server products with slot-based pricing
- Automated provisioning, suspension and termination
- Invoice management and payment tracking via PayPal
- Server backup management

### 💰 Donations
- Community donation system with progress tracking
- Donation statistics and contributor recognition

### 📰 News & Content
- Blog posts with multilingual support (DE, EN, FR, NL, PL, TR)
- Static pages with content management
- Wiki system with articles, categories and revision history
- Tutorial system with step-by-step guides and voting

### 🗳️ Community Features
- User profiles with activity tracking and badges
- Comment system with notifications
- Poll system for community decisions
- Contact form with spam protection
- Achievement and badge system

### 📱 Social Media & Notifications
- Telegram bot integration with interactive commands
- Discord webhook notifications
- Automated social media broadcasting for new content
- Multi-channel notification system

### 🔍 SEO & Discovery
- JSON-LD structured data for search engines
- Sitemap generation
- robots.txt management
- llms.txt for AI discoverability

### 🛠️ Admin Panel
- Full Filament admin dashboard
- Resource management for all content types
- Activity logging and moderation tools
- Telegram settings and social media channel management
- Statistics widgets and overview dashboards

## 🏗️ Tech Stack

| Layer | Technology |
|---|---|
| **Framework** | Laravel 12 (PHP 8.3) |
| **Admin Panel** | Filament 3 |
| **Frontend** | Blade, Alpine.js, Tailwind CSS |
| **Database** | MySQL/MariaDB |
| **Storage** | Hetzner Object Storage (S3) |
| **Server** | Hetzner Dedicated, Plesk |
| **Game Servers** | Pterodactyl Panel |
| **CI/CD** | GitHub Actions |
| **Backups** | Automated daily/weekly to Synology NAS |

## 🚀 Deployment

```bash
# Clone
git clone git@github.com:wolffileseu/wolffiles-app.git
cd wolffiles-app

# Install dependencies
composer install
npm install && npm run build

# Configure
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed

# Start
php artisan serve
```

## 📊 Quality

- **PHPStan** static analysis on every push
- **Security audits** via Composer
- **PHP syntax checks** automated in CI
- **Laravel config validation** in pipeline

## 🌍 Community

- 🌐 [wolffiles.eu](https://wolffiles.eu)
- 💬 [Discord](https://discord.gg/wolffiles)
- 📱 [Telegram Bot](cd /var/www/vhosts/wolffiles.eu/httpdocs_new/wolffiles-app

cat > README.md << 'READMEEOF'
# 🐺 Wolffiles.eu

![CI](https://github.com/wolffileseu/wolffiles-app/actions/workflows/check.yml/badge.svg)

The ultimate community platform for classic Wolfenstein games — **Enemy Territory** and **Return to Castle Wolfenstein**.

---

## 🎮 About

Wolffiles.eu is a comprehensive file repository and gaming hub serving the ET/RtCW community since day one. We host over **4,900 files** totaling **169+ GB** of maps, mods, scripts, and tools — preserving classic gaming content while building modern community features.

## ✨ Features

### 📁 File Repository
- Massive archive of maps, mods, skins, sounds, utilities and full game versions
- Support for ET, RtCW, ET Legacy, ETe, True Combat Elite, ET Quake Wars and more
- Automated file analysis with PK3/ZIP content extraction
- Screenshot generation and BSP map metadata parsing
- Download tracking with statistics and trending content
- File approval workflow with auto-approve for trusted uploaders

### 🗺️ 3D Map Viewer
- Interactive BSP map preview directly in the browser
- Powered by a custom WebGL-based Quake 3 BSP renderer
- View map geometry, textures and layout before downloading

### 🤖 Omni-Bot Waypoint Browser
- Browse waypoints for **1,040+ ET maps** and **120+ RtCW maps**
- Automated GitHub synchronization to keep waypoints up to date
- Direct download of waypoint files for bot navigation

### 📡 Server Tracker
- Live game server tracking with real-time player counts
- ELO-based ranking system for competitive players
- Player session tracking with aliases and statistics
- Clan detection and clan pages
- Server history, map statistics and peak player tracking
- Master server polling for ET and RtCW

### 🎬 ETTV Streaming
- ETTV slot management integrated with Pterodactyl
- Live match streaming for Enemy Territory
- Event scheduling and management

### 🎥 Demo Browser
- Upload and browse game demos
- Categorized demo archive with metadata

### 🌐 FastDL Hosting
- Fast download server for game files
- Clan-specific FastDL directories
- Automated PK3 extraction from ZIP archives
- Game-specific directory management

### 🖥️ Game Server Hosting
- Hosted game servers via Pterodactyl panel integration
- Server products with slot-based pricing
- Automated provisioning, suspension and termination
- Invoice management and payment tracking via PayPal
- Server backup management

### 💰 Donations
- Community donation system with progress tracking
- Donation statistics and contributor recognition

### 📰 News & Content
- Blog posts with multilingual support (DE, EN, FR, NL, PL, TR)
- Static pages with content management
- Wiki system with articles, categories and revision history
- Tutorial system with step-by-step guides and voting

### 🗳️ Community Features
- User profiles with activity tracking and badges
- Comment system with notifications
- Poll system for community decisions
- Contact form with spam protection
- Achievement and badge system

### 📱 Social Media & Notifications
- Telegram bot integration with interactive commands
- Discord webhook notifications
- Automated social media broadcasting for new content
- Multi-channel notification system

### 🔍 SEO & Discovery
- JSON-LD structured data for search engines
- Sitemap generation
- robots.txt management
- llms.txt for AI discoverability

### 🛠️ Admin Panel
- Full Filament admin dashboard
- Resource management for all content types
- Activity logging and moderation tools
- Telegram settings and social media channel management
- Statistics widgets and overview dashboards

## 🏗️ Tech Stack

| Layer | Technology |
|---|---|
| **Framework** | Laravel 12 (PHP 8.3) |
| **Admin Panel** | Filament 3 |
| **Frontend** | Blade, Alpine.js, Tailwind CSS |
| **Database** | MySQL/MariaDB |
| **Storage** | Hetzner Object Storage (S3) |
| **Server** | Hetzner Dedicated, Plesk |
| **Game Servers** | Pterodactyl Panel |
| **CI/CD** | GitHub Actions |
| **Backups** | Automated daily/weekly to Synology NAS |

## 🚀 Deployment

```bash
# Clone
git clone git@github.com:wolffileseu/wolffiles-app.git
cd wolffiles-app

# Install dependencies
composer install
npm install && npm run build

# Configure
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed

# Start
php artisan serve
```

## 📊 Quality

- **PHPStan** static analysis on every push
- **Security audits** via Composer
- **PHP syntax checks** automated in CI
- **Laravel config validation** in pipeline

## 🌍 Community

- 🌐 [wolffiles.eu](https://wolffiles.eu)
- 💬 [Discord](https://discord.com/invite/wzkRyWWuxP)
- 📱 [Telegram Bot](https://t.me/+toCHcabu-MZhYzE8)

---

Made with ❤️ in Luxembourg for the Wolfenstein community.
