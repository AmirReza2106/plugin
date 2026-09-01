# Privacy and Personal Data

## Stored Data

Each booking stores the authenticated WordPress user ID, submitted first and last
name, mobile number, email, meeting title and description, local meeting date and
times, assigned room, status, reviewer ID, and audit timestamps.

## Visibility

- Employees can view full details only for requests owned by their current user ID.
- Shared availability exposes only room numbers and occupied/free intervals.
- Administrators with `manage_workshop_requests` can view submitted details.
- Request mutations require authentication, capability checks, and WordPress nonces.

## WordPress Privacy Tools

The plugin registers with **Tools > Export Personal Data** and
**Tools > Erase Personal Data**.

Export returns matching booking records by account ID or submitted email. Erasure
anonymizes user IDs, names, contact data, meeting titles, descriptions, and reviewer
references. Non-identifying dates, times, room assignments, statuses, public request
references, and audit timestamps remain so room schedules and decision integrity are
not corrupted.

No automatic retention period is imposed because retention requirements vary by
organization. Site operators should define and document an appropriate retention
policy and process privacy requests through WordPress privacy tools.
