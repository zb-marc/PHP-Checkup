# AS PHP Checkup

[![WordPress Version](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.3.3-orange.svg)](https://akkusys.de)

**Intelligenter PHP-Konfigurations-Checker mit automatischen Lösungen, One-Click-Fixes und Konfigurations-Generatoren für WordPress.**

## 🚀 Hauptfunktionen

### Kernfunktionen
- **Umfassende PHP-Konfigurations-Prüfung**: Analysiert 30+ kritische PHP-Einstellungen
- **Intelligente Plugin-Analyse**: Scannt aktive Plugins nach PHP-Anforderungen und passt Empfehlungen an
- **One-Click-Lösungen**: Wendet automatisch Fixes auf Ihre Server-Konfiguration an
- **Konfigurations-Generator**: Erstellt optimierte Config-Dateien für verschiedene Server-Typen
- **Server-Erkennung**: Identifiziert automatisch Ihren Hosting-Anbieter und Server-Typ
- **Health-Score-Dashboard**: Visuelle Darstellung Ihrer PHP-Konfigurations-Gesundheit
- **Multi-Environment-Support**: Funktioniert mit Apache, NGINX, LiteSpeed, IIS und mehr
- **Effizientes Caching**: 5-Minuten-Cache für Performance-Optimierung

### Lösungstypen
- **Automatische Fixes**: Direkte Anwendung von PHP-Konfigurationen
- **Konfigurations-Downloads**: Generiert einsatzbereite Config-Dateien
- **Hosting-spezifische Anleitungen**: Schritt-für-Schritt-Anleitungen für große Hosting-Anbieter
- **Erweiterte Konfigurationen**: Docker, Kubernetes und benutzerdefinierte Setups

### Unterstützte Hosting-Anbieter
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

## 📋 Systemanforderungen

- WordPress 5.8 oder höher
- PHP 7.4 oder höher
- Administrator-Zugang zu WordPress
- Schreibrechte für Konfigurations-Dateien (optional für Auto-Fixes)

## 📦 Installation

### Via WordPress Admin
1. Laden Sie die neueste Version von [GitHub Releases](https://github.com/zb-marc/PHP-Checkup/releases) herunter
2. Navigieren Sie zu **Plugins → Installieren** in Ihrem WordPress Admin
3. Klicken Sie auf **Plugin hochladen** und wählen Sie die heruntergeladene ZIP-Datei
4. Klicken Sie auf **Jetzt installieren** und dann **Aktivieren**

### Via FTP
1. Laden Sie die Plugin-ZIP-Datei herunter und entpacken Sie sie
2. Laden Sie den `as-php-checkup` Ordner nach `/wp-content/plugins/` hoch
3. Navigieren Sie zu **Plugins** in Ihrem WordPress Admin
4. Suchen Sie **AS PHP Checkup** und klicken Sie auf **Aktivieren**

### Via Composer
```bash
composer require akkusys/as-php-checkup
```

### Via WP-CLI
```bash
wp plugin install https://github.com/zb-marc/PHP-Checkup/archive/main.zip --activate
```

## 🎯 Verwendung

### Grundlegende Verwendung

1. **Tool aufrufen**: Navigieren Sie zu **Werkzeuge → PHP Checkup** in Ihrem WordPress Admin
2. **Status überprüfen**: Prüfen Sie Ihren Gesundheits-Score und identifizierte Probleme
3. **Lösungen anwenden**: Klicken Sie auf verfügbare Lösungen, um Probleme automatisch zu beheben
4. **Configs herunterladen**: Generieren Sie Konfigurations-Dateien zur manuellen Implementierung

### Dashboard-Widget

Das Plugin fügt ein Widget zu Ihrem WordPress-Dashboard hinzu, das zeigt:
- Aktuelle PHP-Version und Status
- Anzahl kritischer Probleme
- Schnellzugriff auf die vollständige Checkup-Seite
- Auto-Refresh-Fähigkeit

### WP-CLI-Befehle

```bash
# PHP-Konfigurations-Status prüfen
wp as-php-checkup status

# Plugin-Anforderungen analysieren
wp as-php-checkup analyze

# System-Informationen abrufen
wp as-php-checkup system

# Detaillierten Bericht exportieren
wp as-php-checkup export --format=csv

# Schnell-Check mit Exit-Codes für CI/CD
wp as-php-checkup check
```

### REST API Endpoints

```javascript
// Aktuellen Status und Gesundheits-Score abrufen
GET /wp-json/as-php-checkup/v1/status

// System-Informationen abrufen
GET /wp-json/as-php-checkup/v1/system-info

// Plugin-Anforderungs-Analyse abrufen
GET /wp-json/as-php-checkup/v1/plugin-analysis

// Check aktualisieren
POST /wp-json/as-php-checkup/v1/refresh

// Bericht exportieren (JSON/CSV)
GET /wp-json/as-php-checkup/v1/export?format=csv
```

## 🔧 Konfigurations-Optionen

### Geprüfte PHP-Einstellungen

#### Basis-Einstellungen
- PHP-Version
- Memory Limit
- Max Execution Time
- Max Input Time
- Max Input Vars
- Post Max Size
- Upload Max Filesize

#### Session-Konfiguration
- Session Auto Start
- Session GC Maxlifetime
- Session Save Handler

#### OPcache-Einstellungen
- OPcache Enable
- OPcache Memory Consumption
- OPcache Interned Strings Buffer
- OPcache Max Accelerated Files
- OPcache Revalidate Frequency
- OPcache Enable CLI

#### Performance-Einstellungen
- Realpath Cache Size
- Realpath Cache TTL
- Output Buffering
- Zlib Output Compression

## ⏱️ Cache-System

Das Plugin verwendet ein intelligentes Cache-System zur Performance-Optimierung:

| Cache-Typ | Dauer | Beschreibung |
|-----------|-------|--------------|
| PHP-Checks | 5 Minuten | Konfigurations-Prüfungen |
| System-Info | 30 Minuten | Server- und System-Details |
| Plugin-Analyse | 24 Stunden | Plugin-Anforderungen |

**Hinweis**: Der "Refresh Now" Button umgeht den Cache und erzwingt eine neue Prüfung.

## 🛠️ Erweiterte Funktionen

### Automatische Plugin-Analyse

Das Plugin scannt automatisch Ihre aktiven Plugins nach:
- Erforderlicher PHP-Version
- Speicheranforderungen
- PHP-Extension-Abhängigkeiten
- Spezifischen Konfigurations-Bedürfnissen

### One-Click-Lösungen

Verfügbare automatische Fixes:
- **php.ini-Generierung**: Erstellt optimierte PHP-Konfiguration
- **.htaccess-Updates**: Fügt PHP-Direktiven für Apache/LiteSpeed hinzu
- **wp-config.php-Konstanten**: Fügt WordPress-Speicher-Konstanten ein
- **NGINX-Konfigurationen**: Generiert Server-Block-Configs

### Konfigurations-Export-Formate

- **php.ini**: Standard PHP-Konfigurations-Datei
- **.user.ini**: Per-Directory PHP-Konfiguration
- **.htaccess**: Apache/LiteSpeed-Direktiven
- **NGINX Config**: Server-Block-Konfiguration
- **Docker Compose**: Container-Konfiguration
- **Kubernetes ConfigMap**: K8s Deployment-Configs
- **wp-config-Konstanten**: WordPress-spezifische Einstellungen

## 🔒 Sicherheit

Das Plugin implementiert mehrere Sicherheitsmaßnahmen:
- Nonce-Verifizierung bei allen AJAX-Anfragen
- Capability-Checks (`manage_options`)
- Input-Sanitization und -Validierung
- Output-Escaping
- Datei-Schreibberechtigungs-Prüfungen
- Automatische Backup-Erstellung vor Änderungen
- CSV-Injection-Schutz in Exporten

## 🌍 Internationalisierung

Das Plugin ist vollständig übersetzbar und beinhaltet:
- Text Domain: `as-php-checkup`
- POT-Datei für Übersetzungen
- Unterstützung für RTL-Sprachen

### Verfügbare Übersetzungen
- Englisch (Standard)
- Deutsch (de_DE) - In Vorbereitung
- Französisch (fr_FR) - In Vorbereitung
- Spanisch (es_ES) - In Vorbereitung

## 📊 Performance-Auswirkungen

- **Leichtgewicht**: < 500KB Gesamtgröße
- **Gecachte Ergebnisse**: Reduziert Server-Last
- **Lazy Loading**: Lädt Assets nur auf Plugin-Seiten
- **Optimierte Abfragen**: Minimale Datenbank-Nutzung
- **Hintergrund-Verarbeitung**: Plugin-Analyse läuft asynchron

## 🐛 Fehlerbehebung

### Häufige Probleme

**Problem**: Kann keine automatischen Fixes anwenden
- **Lösung**: Prüfen Sie Datei-Berechtigungen für php.ini, .htaccess und wp-config.php

**Problem**: Plugin-Analyse wird nicht angezeigt
- **Lösung**: Klicken Sie auf "Plugins neu analysieren", um manuelle Analyse auszulösen

**Problem**: Lösungen erscheinen nicht
- **Lösung**: Stellen Sie sicher, dass Sie mindestens eine nicht-optimale Einstellung haben

**Problem**: REST API antwortet nicht
- **Lösung**: Prüfen Sie Permalink-Einstellungen und REST API-Verfügbarkeit

### Debug-Modus

Aktivieren Sie Debug-Logging durch Hinzufügen zu `wp-config.php`:
```php
define( 'AS_PHP_CHECKUP_DEBUG', true );
```

## 🤝 Mitwirken

Beiträge sind willkommen! Bitte folgen Sie diesen Richtlinien:

1. Forken Sie das Repository
2. Erstellen Sie einen Feature-Branch (`git checkout -b feature/NeuesFeature`)
3. Committen Sie Ihre Änderungen (`git commit -m 'Add NeuesFeature'`)
4. Pushen Sie zum Branch (`git push origin feature/NeuesFeature`)
5. Öffnen Sie einen Pull Request

### Entwicklungs-Setup

```bash
# Repository klonen
git clone https://github.com/zb-marc/PHP-Checkup.git

# Abhängigkeiten installieren
cd PHP-Checkup
composer install
npm install

# Assets bauen
npm run build

# Tests ausführen
composer test
npm test

# Coding-Standards prüfen
composer phpcs
npm run lint
```

## 📝 Changelog

### Version 1.3.3 (2025-01-08)
- 🐛 **BUGFIX:** Fatal Error bei Plugin-Aktivierung behoben
- 🐛 **BUGFIX:** Cache-System komplett überarbeitet und korrigiert
- 🐛 **BUGFIX:** PHP Parse Error in class-checkup.php behoben
- 🔒 **SECURITY:** CSV-Injection-Schutz implementiert
- 🔒 **SECURITY:** Debug-Konstante korrekt definiert
- ✨ **FEATURE:** Automatische Backup-Bereinigung nach 7 Tagen
- ⚡ **IMPROVEMENT:** Cache-Manager für bessere Performance
- ⚡ **IMPROVEMENT:** Verbesserte Error-Behandlung in REST API

### Version 1.3.2 (2025-01-XX)
- ✨ Backup-System vor Änderungen
- 🔒 Security-Trait für besseren Schutz
- ⚡ Cache-Manager-System eingeführt

### Version 1.3.0 (2024-12-XX)
- ✨ Automatischer Lösungs-Anbieter hinzugefügt
- ✨ One-Click-Konfigurations-Fixes
- ✨ Server- und Hosting-Erkennung
- ✨ Erweiterte Konfigurations-Generatoren
- ✨ Konfigurations-Vorschau-Modal
- 🎨 Verbesserte UI mit Lösungs-Karten
- 🔧 Schreibberechtigungs-Prüfungen hinzugefügt

### Version 1.2.0 (2024-12-XX)
- ✨ Plugin-Anforderungs-Analyzer hinzugefügt
- ✨ REST API-Implementierung
- ✨ WP-CLI-Befehls-Support
- ✨ Dashboard-Widget
- 📊 Tägliche automatische Plugin-Analyse
- 🎨 Visueller Health-Score-Indikator

### Version 1.1.0 (2024-11-XX)
- ✅ Basis PHP-Konfigurations-Prüfungen
- 📊 System-Informations-Anzeige
- 📥 CSV-Export-Funktionalität
- 🌍 Internationalisierungs-Support

## 📄 Lizenz

Dieses Plugin ist lizenziert unter der GPL v2 oder später.

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

## 👨‍💻 Autor

**Marc Mirschel**
- Website: [https://mirschel.biz](https://mirschel.biz)
- Firma: [ZooBrothers](https://zoobro.de)
- E-Mail: marc@mirschel.biz
- GitHub: [@zb-marc](https://github.com/zb-marc)

## 🙏 Credits

- WordPress Core Team für die Plugin API
- PHP Community für Konfigurations-Best-Practices
- Alle Mitwirkenden und Tester

## 💬 Support

- **Dokumentation**: [GitHub Wiki](https://github.com/zb-marc/PHP-Checkup/wiki)
- **Issues**: [GitHub Issues](https://github.com/zb-marc/PHP-Checkup/issues)
- **Forum**: [WordPress Support Forum](https://wordpress.org/support/plugin/as-php-checkup/)
- **E-Mail**: support@akkusys.de

## 🎯 Roadmap

### Version 1.4.0 (Geplant)
- [ ] Multi-Site-Netzwerk-Support
- [ ] Geplante Konfigurations-Prüfungen
- [ ] Konfigurations-Historie-Tracking
- [ ] Erweiterte Reporting-Dashboard
- [ ] Integration mit populären Hosting-APIs

### Version 2.0.0 (Zukunft)
- [ ] React-basiertes Admin-Interface
- [ ] Gutenberg-Block für Frontend-Status
- [ ] Real-Time-Updates über WebSockets
- [ ] Cloud-Konfigurations-Profile
- [ ] Performance-Benchmarking
- [ ] E-Mail-Benachrichtigungen für kritische Probleme

## 📸 Screenshots

1. **Haupt-Dashboard** - Übersicht mit Health-Score und Status-Karten
2. **Lösungs-Sektion** - Verfügbare automatische Fixes und Downloads
3. **Konfigurations-Ergebnisse** - Detaillierter PHP-Einstellungen-Vergleich
4. **System-Informationen** - Server- und WordPress-Details
5. **Dashboard-Widget** - Schnelle Status-Übersicht
6. **WP-CLI-Ausgabe** - Command-Line-Interface-Ergebnisse

## 🏆 Badges & Zertifizierungen

- WordPress Plugin Directory Ready
- WPCS (WordPress Coding Standards) Compliant
- GDPR Compliant (Keine persönlichen Daten-Sammlung)
- Accessibility Ready (WCAG 2.1 Level AA)
- Translation Ready

---

**Mit ❤️ erstellt von [Marc Mirschel](https://mirschel.biz)**

*Wenn Sie dieses Plugin nützlich finden, bitte [⭐ das Repository markieren](https://github.com/zb-marc/PHP-Checkup) und [eine Bewertung schreiben](https://wordpress.org/support/plugin/as-php-checkup/reviews/).*