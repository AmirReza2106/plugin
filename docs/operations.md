# Operations Guide

## Employee Access

Assign the WordPress `employee` role from **Users**. Employees receive these
least-privilege capabilities:

- `create_workshop_bookings`
- `view_own_workshop_bookings`
- `view_room_availability`

Their dashboard is available from `رزرو اتاق جلسه` in WordPress administration.
Shared room timelines contain only room numbers and occupied/free times. Personal
request details are filtered by the authenticated WordPress user ID.

## Administrator Workflow

Administrators receive `manage_workshop_requests` and can use:

```text
رزرو اتاق جلسه > مدیریت درخواست‌ها
```

Requests can be filtered by status and date or searched by reference, title,
employee name, email, or normalized mobile number. Decisions are final:

- `pending -> approved` retains the assigned room.
- `pending -> rejected` releases the room.
- Approved and rejected requests cannot be changed again.

Every creation and decision is stored in status history. Decision writes lock the
request row and update the request and history in one InnoDB transaction.

## Scheduling

Scheduling settings are under `Settings > رزرو اتاق جلسه`. Configure working
hours, minimum/maximum duration, and numbered room capacity. All times use
15-minute increments. Adjacent meetings can use the same room without a gap.

Pending and approved requests reserve rooms. Rejected requests do not.

## Verification

Run the complete static and automated suite:

```bash
docker compose exec php composer --working-dir=/var/www/html/wp-content/plugins/workshop-registration check
```

After deployment, verify:

1. Plugin activation completes without an administrator notice.
2. The schema option is `workshop_registration_schema_version = 2.1.0`.
3. Both custom tables use InnoDB.
4. An employee can submit and view only their own request.
5. An administrator can approve or reject a pending request once.
6. Rejection makes the released interval available immediately.

## Release Package

Build a production archive from the repository root:

```bash
./bin/build-release.sh
```

The archive is written to `dist/`, excludes tests and Docker/development files,
and contains an optimized production Composer autoloader.

## Data Safety

The plugin does not delete booking tables on deactivation or uninstall. Back up
the WordPress database before schema upgrades. Database migration failures are
logged privately and shown to administrators as a generic notice.
