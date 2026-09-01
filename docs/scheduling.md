# Scheduling Rules

The scheduling domain treats room bookings as half-open intervals. A room becomes
available at the exact minute its current meeting ends.

## Booking Policy

- Working hours default to 09:00 through 18:00 and are administrator-configurable.
- Booking duration defaults to 30 through 60 minutes and is configurable.
- Start and end times use 15-minute increments.
- There is no mandatory gap between meetings.
- Pending and approved requests reserve room capacity.
- Rejected requests do not reserve room capacity.

Rule changes apply when validating new requests. Existing active requests remain
part of conflict detection even if later settings would no longer accept them.

## Conflict Detection

Two bookings conflict only when their requested intervals overlap:

```text
existing_start < requested_end
and
existing_end > requested_start
```

An existing end equal to the next start is allowed.

## Stable Allocation

The allocator inspects numbered rooms from `1` through the configured capacity
and returns the lowest room without an active conflict. Existing assignments are
never changed. If every room conflicts, allocation returns no slot and the
application layer must reject the submission.

## Minimum Room Count

The theoretical minimum is calculated with a sweep line over active requests.
Each request contributes a start and end event. End events are processed before
starts at the same minute. The peak active count is the minimum room count.

For example:

```text
09:00-10:00
09:30-10:30
10:00-11:00
11:00-12:00
```

The minimum is two rooms because the 09:00 meeting releases its room exactly when
the 10:00 meeting begins.
