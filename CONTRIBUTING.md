# Contributing to Wolffiles

 Danke, dass du an **Wolffiles.eu** mitwirken möchtest!
*Thanks for considering contributing to **Wolffiles.eu**!*

Dieses Dokument beschreibt, wie du Bugs meldest, Features vorschlägst und Code beiträgst.
*This document describes how to report bugs, suggest features, and contribute code.*

---

##  Inhaltsverzeichnis / Table of Contents

- [Bugs melden / Reporting bugs](#-bugs-melden--reporting-bugs)
- [Features vorschlagen / Suggesting features](#-features-vorschlagen--suggesting-features)
- [Code-Beiträge / Code contributions](#-code-beiträge--code-contributions)
- [Entwicklungs-Setup / Development setup](#-entwicklungs-setup--development-setup)
- [Coding-Standards](#-coding-standards)
- [Commit-Konventionen / Commit conventions](#-commit-konventionen--commit-conventions)
- [Pull Request Workflow](#-pull-request-workflow)
- [Übersetzungen / Translations](#-übersetzungen--translations)

---

##  Bugs melden / Reporting bugs

Bitte nutze das **Bug Report Template** unter [Issues → New Issue](https://github.com/wolffileseu/wolffiles-app/issues/new/choose).
*Please use the Bug Report template at Issues → New Issue.*

**Vorher bitte:**
- Suche in offenen und geschlossenen Issues nach Duplikaten
- Mache einen Hard-Refresh (Strg+F5) und prüfe, ob der Bug noch besteht
- Sammle: Browser, OS, URL, Screenshots, Konsolen-Errors (F12)

---

##  Features vorschlagen / Suggesting features

Nutze das **Feature Request Template**. Beschreibe:
- **Welches Problem** löst das Feature?
- **Wie soll es funktionieren** aus User-Sicht?
- **Alternativen / Workarounds**, die du erwogen hast

Nicht jedes Feature kann umgesetzt werden – aber jedes wird gelesen und bewertet.
*Not every feature can be implemented — but every one is read and evaluated.*

---

##  Code-Beiträge / Code contributions

Du willst Code beisteuern? Super! 
*Want to contribute code? Awesome!*

**Workflow:**
1. Issue eröffnen oder bestehendes kommentieren („Ich nehm mir das vor")
2. Repo forken
3. Feature-Branch anlegen: `feature/kurze-beschreibung` oder `fix/issue-123`
4. Code schreiben, testen, committen
5. Pull Request gegen `main` öffnen → PR Template ausfüllen

---

##  Entwicklungs-Setup / Development setup

### Voraussetzungen / Requirements

- PHP **8.3+** mit Extensions: `mbstring`, `xml`, `bcmath`, `mysql`, `redis`, `gd`, `zip`
- MySQL **8.0+** oder MariaDB **10.6+**
- Node.js **20+**
- Composer **2.x**
- Redis (für Queue & Cache)

### Installation

```bash
# Clone
git clone https://github.com/wolffileseu/wolffiles-app.git
cd wolffiles-app

# Dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Frontend Build
npm run build

# Filament Cache
php artisan filament:cache-components

# Dev-Server starten
php artisan serve
npm run dev
```

### Queue & Scheduler (lokal)

```bash
php artisan queue:work
php artisan schedule:work
```

---

##  Coding-Standards

- **PHP**: PSR-12, Laravel-Konventionen
- **JavaScript**: bestehender Stil (Alpine.js, vanilla JS wo möglich)
- **CSS**: Tailwind Utility-First
- **Blade**: Komponenten in `resources/views/components/`, sprechende Namen

**Vor jedem Commit / Before each commit:**

```bash
# PHP Syntax Check
find app -name "*.php" -exec php -l {} \;

# Static Analysis
vendor/bin/phpstan analyse

# Tests (falls vorhanden / if available)
php artisan test
```

---

##  Commit-Konventionen / Commit conventions

Wir nutzen lose [Conventional Commits](https://www.conventionalcommits.org/):
*We loosely follow Conventional Commits:*

```
feat: Neues Feature
fix: Bugfix
docs: Doku-Änderung
style: Code-Stil (Formatierung, kein Logikchange)
refactor: Refactoring ohne Verhaltens-Änderung
perf: Performance-Verbesserung
test: Tests
chore: Build, Dependencies, Tooling
```

**Beispiele:**

```
feat(tracker): add player favorite system
fix(uploader): correct multipart boundary header
docs(readme): update installation steps
refactor(api): extract tracker logic into service class
```

Sprache: **Englisch bevorzugt** für Commits, aber Deutsch ist auch ok.
*Language: **English preferred** for commits, but German is also fine.*

---

##  Pull Request Workflow

1. **Klein halten** – ein PR = ein Thema. Lieber mehrere kleine als ein großer.
2. **PR Template** vollständig ausfüllen
3. **CI muss grün sein** (PHP-Syntax, PHPStan, Composer Audit)
4. **Verknüpftes Issue** erwähnen (`Closes #123`)
5. Bei UI-Änderungen: **Screenshots/GIFs** beilegen
6. Bei DB-Änderungen: **Migration** + Test mit `migrate:rollback`
7. Bei neuen Strings: **Übersetzungen** in allen 6 Sprachen ergänzen (siehe unten)

---

##  Übersetzungen / Translations

Wolffiles unterstützt **6 Sprachen**: DE, EN, FR, NL, PL, TR.
*Wolffiles supports 6 languages: DE, EN, FR, NL, PL, TR.*

- Übersetzungen liegen in `lang/{locale}/messages.php`
- Bei neuen Strings: in **allen** 6 Sprachen ergänzen
- Im Filament Admin gibt es einen **Translation Manager** mit `syncAll`-Funktion
- **Niemals** Lang-Files komplett überschreiben → nur fehlende Keys additiv einfügen

---

##  Fragen?

-  Discord: https://discord.com/invite/wzkRyWWuxP
-  Kontakt: https://wolffiles.eu/contact

Danke fürs Mithelfen! 
*Thanks for contributing!*
