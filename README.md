# Workshop Registration

A WordPress plugin for secure workshop requests and conflict-free meeting room allocation.

The plugin is designed for authenticated company employees. Administrators assign
the `employee` role in WordPress, configure scheduling rules, and manage approval
decisions through the plugin administration screens.

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

Build a production archive in `dist/`:

```bash
./bin/build-release.sh
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

## Administration

The plugin has two administrator areas. They use separate permissions so an
administrator can manage requests and scheduling without exposing either area to
employees.

### Scheduling Settings

The scheduling page is available at:

```text
WordPress Admin > Settings > رزرو اتاق جلسه
```

The page requires the WordPress `manage_options` capability. It stores one
validated settings record and does not delete booking data when settings change.

#### Working Hours

These fields define the daily interval in which a new meeting may start and end.

| Setting | Default | Meaning |
| --- | --- | --- |
| شروع ساعت کاری | `09:00` | Earliest permitted start time. |
| پایان ساعت کاری | `18:00` | Latest permitted end time. |

Both values must be valid 24-hour times. Employee requests must stay completely
inside this interval. For example, with the default values, `08:45–09:30` and
`17:30–18:15` are invalid, while `09:00–09:30` and `17:00–18:00` are valid if
their durations also satisfy the duration settings.

Start and end times use 15-minute increments. The interface uses the browser's
time picker with a 15-minute step, and the server validates the submitted values
again, so changing the HTML form cannot bypass the rule.

#### Minimum Duration

The default minimum duration is `30 minutes`. A meeting shorter than this value is
rejected. If the minimum is changed to `15`, then a 15-minute request is valid;
if it is changed to `45`, a 30-minute request is invalid.

#### Maximum Duration

The default maximum duration is `60 minutes`. A meeting longer than this value is
rejected. The maximum must be at least as large as the minimum, and both duration
values must be compatible with 15-minute scheduling increments.

For example, with the default `30–60` minute range:

| Request | Result |
| --- | --- |
| `09:00–09:30` | Valid duration. |
| `09:00–10:00` | Valid duration. |
| `09:00–09:15` | Rejected as too short. |
| `09:00–10:15` | Rejected as too long. |

#### Number of Rooms

The default room capacity is `1`. The permitted range is `1` through `100`.
Rooms are automatically represented as numbered rooms from `1` to the configured
capacity. Employees do not select or submit a room number.

When a request is submitted, the allocator checks rooms in ascending order and
assigns the lowest room without an overlapping active booking. Existing room
assignments remain stable; a later request cannot cause an earlier request to move
to another room.

The capacity cannot be reduced below the highest room number used by an active
future request. For example, if an active future request uses room `3`, changing
capacity to `2` is rejected until that assignment is no longer active.

#### Applying Setting Changes

New requests use the saved settings immediately. Existing requests are not edited,
moved, or reassigned when settings change. Existing pending and approved requests
continue to participate in conflict detection, even if a later settings change
would make their original duration invalid.

Invalid settings are rejected as a complete update and the previous valid settings
remain active. The administrator receives a Persian validation message explaining
that the submitted values must be corrected.

### Request Management

Administrators can review requests from:

```text
WordPress Admin > رزرو اتاق جلسه > مدیریت درخواست‌ها
```

This page requires the `manage_workshop_requests` capability and provides:

- Status filters for all, pending, approved, and rejected requests.
- Meeting-date filtering.
- Search by public reference, meeting title, employee name, email, or mobile.
- Complete submitted request details for authorized administrators.
- The status-history timeline for each request.
- Final approve and reject actions for pending requests.

Only pending requests can receive a decision. An approval retains the assigned
room. A rejection clears and releases the assigned room. Approved and rejected
requests cannot be changed again. Every creation and decision is recorded in the
status-history table with the actor and timestamp.

### Room Availability Rules

The availability timeline is visible from:

```text
WordPress Admin > رزرو اتاق جلسه
```

It shows room numbers and free/occupied intervals only. It does not reveal another
employee's name, email, mobile number, meeting title, description, or request
reference.

Pending and approved requests occupy rooms. Rejected requests do not. Time ranges
are half-open: a room becomes available at the exact minute a meeting ends. With
one room, both `09:00–10:00` and `10:00–11:00` can use room `1`; two overlapping
requests such as `09:00–10:00` and `09:30–10:30` require two rooms when available.

### Employee Workflow

Employees assigned the `employee` role receive the `رزرو اتاق جلسه` dashboard.
They can:

1. View privacy-safe room availability for a selected date.
2. Submit a booking request with their name, contact details, meeting title,
   date, time, and description.
3. View the requests submitted from their own WordPress account.

Employees cannot choose a room, assign another user as requester, approve or reject
requests, or view another employee's personal request details. Submission and
administrator actions require authentication, capability checks, and WordPress
nonces.

### Examples

With one room and the default `09:00–18:00`, `30–60 minute` configuration:

1. A `09:00–09:30` request receives room `1` and remains pending.
2. A simultaneous `09:00–09:30` request cannot use room `1`; it is rejected if
   no additional room is configured.
3. A `09:30–10:00` request may use room `1` because the first request ends exactly
   when the second begins.
4. If the first request is rejected, its room is released and can be reused by an
   overlapping request.
5. If the first request is approved, it continues to occupy room `1` for its
   scheduled interval.

### Documentation and Operations

See [the scheduling rules](docs/scheduling.md) for conflict detection and
allocation details. See [the operations guide](docs/operations.md) for roles,
deployment verification, migration, and release packaging. See [the privacy guide](docs/privacy.md)
for stored data, visibility boundaries, and WordPress export/erasure behavior.
