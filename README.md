# Workshop Registration

A WordPress plugin for secure workshop requests and conflict-free meeting room allocation.

The plugin is designed for authenticated company employees. Administrators assign
the `employee` role in WordPress, configure scheduling rules, and later manage
approval decisions through the plugin administration screens.

## Requirements

- WordPress 6.5 or newer
- PHP 8.1 or newer
- MySQL 8.0 or MariaDB 10.6 or newer

## Docker development

The local environment uses Docker Compose; host PHP and Composer are not required.

Build and start Nginx, PHP-FPM, WordPress, and MariaDB:

```bash
docker compose up --build -d --wait
```

The PHP container installs Composer dependencies during startup. On the first run,
open <http://localhost:8080>, complete the WordPress browser installer, and activate
Workshop Registration from the Plugins page.

Run all current checks:

```bash
docker compose exec php composer --working-dir=/var/www/html/wp-content/plugins/workshop-registration check
```

Stop the environment without deleting its data:

```bash
docker compose down
```

Delete the local environment and all database and WordPress data:

```bash
docker compose down --volumes --remove-orphans
```

The plugin is under active development and is not ready for production use.

## Current administration

The scheduling page is available to administrators at:

```text
Settings > رزرو اتاق جلسه
```

It configures numbered room capacity, working hours, and minimum/maximum booking
duration. Times use fixed 15-minute increments, and adjacent meetings may use the
same room with no mandatory gap.
