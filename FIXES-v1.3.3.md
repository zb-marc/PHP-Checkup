# AS PHP Checkup - Version 1.3.3 Fixes

## 🔴 KRITISCHE FEHLER BEHOBEN

### 1. Fatal Error: Class "AS_PHP_Checkup" not found
**Problem:** Während der Plugin-Aktivierung wurden Klassen aufgerufen, bevor sie geladen waren.

**Lösung:**
- Explizites Laden der benötigten Klassen in `as_php_checkup_activate()`
- Klassen-Existenz-Prüfung in `preload_cache()` Methode
- Sichere Deaktivierung mit Class-Check

**Betroffene Dateien:**
- `as-php-checkup.php` (Zeilen 184-227, 236-244)
- `includes/class-cache-manager.php` (Zeilen 326-340)

### 2. Undefined Constant: AS_PHP_CHECKUP_DEBUG
**Problem:** Debug-Konstante wurde verwendet, aber nie definiert.

**Lösung:**
- Definition der Konstante in der Hauptdatei (Zeile 37-40)
```php
if ( ! defined( 'AS_PHP_CHECKUP_DEBUG' ) ) {
    define( 'AS_PHP_CHECKUP_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );
}
```

### 3. CSV Injection Vulnerability
**Problem:** Ungesicherte CSV-Exports erlaubten Formula-Injection.

**Lösung:**
- Neue Methode `sanitize_csv_field()` in:
  - `includes/class-cli-command.php`
  - `includes/class-rest-controller.php`
- Escape von Formeln (=, +, -, @) mit vorangestelltem Apostroph

### 4. Missing Backup Cleanup Cron
**Problem:** Backup-Dateien wurden nicht automatisch gelöscht.

**Lösung:**
- Cron-Job `as_php_checkup_cleanup_backups` hinzugefügt
- Cleanup-Funktion in der Hauptdatei implementiert

## 📝 INSTALLATION

1. **Backup erstellen** (WICHTIG!)
2. Altes Plugin deaktivieren
3. Alte Plugin-Dateien löschen
4. Neue Dateien hochladen
5. Plugin aktivieren

## ✅ GETESTETE UMGEBUNGEN

- WordPress 5.8 - 6.4
- PHP 7.4 - 8.3
- MySQL 5.7+ / MariaDB 10.3+

## 🔄 ÄNDERUNGSPROTOKOLL

### Version 1.3.3 (2025-01-08)
- **BUGFIX:** Fatal Error bei Plugin-Aktivierung behoben
- **SECURITY:** CSV-Injection-Schutz implementiert
- **BUGFIX:** Debug-Konstante korrekt definiert
- **FEATURE:** Automatische Backup-Bereinigung
- **IMPROVEMENT:** Bessere Error-Handling in REST API
- **IMPROVEMENT:** Cache-Manager Robustheit erhöht

## ⚠️ WICHTIGE HINWEISE

1. **Cache leeren** nach Update empfohlen
2. **Permalinks** neu speichern für REST API
3. **Backup-Dateien** werden nach 7 Tagen automatisch gelöscht
4. **Debug-Modus** folgt jetzt WP_DEBUG Einstellung

## 🆘 SUPPORT

Bei Problemen:
1. Debug-Log prüfen: `/wp-content/debug.log`
2. Plugin deaktivieren/reaktivieren
3. Cache leeren (WP Cache und Browser)
4. GitHub Issue erstellen: https://github.com/zb-marc/PHP-Checkup/issues

## 📄 LIZENZ

GPL v2 oder später
Copyright (C) 2025 Marc Mirschel
