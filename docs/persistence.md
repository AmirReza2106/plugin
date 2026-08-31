# Transactional Persistence

Workshop registration uses the WordPress database connection and the plugin's
custom InnoDB tables. The application service depends on contracts rather than
on WordPress, while infrastructure adapters contain all SQL and lock behavior.

## Atomic Registration

Registration follows this order:

1. Validate the date, timezone, time policy, and configured capacity.
2. Acquire a MariaDB advisory lock scoped to the WordPress installation and date.
3. Start a database transaction.
4. Re-read pending and approved reservations for that date.
5. Assign the lowest available stable room slot.
6. Insert the pending request.
7. Insert the initial pending status-history event.
8. Commit the transaction.
9. Release the advisory lock.

An exception before commit rolls the transaction back. Lock release is attempted
after both success and failure. Different workshop dates use different locks and
can be processed concurrently.

## Lock Names

Lock names contain a fixed plugin prefix and the first 40 hexadecimal characters
of a SHA-256 hash over the WordPress table prefix and workshop date. This keeps
names bounded, site-specific, and free of public input.

## Tracking Credentials

Each request receives:

- A public UUID reference.
- A 256-bit random tracking token returned to the caller once.
- A SHA-256 hash of that token stored in the requests table.

The raw tracking token is never passed to the repository, stored in MariaDB, or
written to logs. The private status page will use the raw token in Step 6.

## Error Boundary

Infrastructure throws application-level exceptions without SQL statements,
database errors, or personal data in their messages. User-facing handlers must
translate these exceptions into generic, localized responses.
