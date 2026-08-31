# Scheduling Rules

The scheduling domain treats room bookings as half-open intervals. A meeting
occupies its requested interval plus a cleanup period after its end time.

## Booking Policy

- Working hours are 09:00 through 18:00.
- The earliest valid start is 09:00.
- The latest valid end is 18:00.
- A booking lasts at least 30 minutes and at most 60 minutes.
- Start and end times use 15-minute increments.
- One room requires a 15-minute cleanup gap between bookings.
- Pending and approved requests reserve room capacity.
- Rejected requests do not reserve room capacity.

The submitted booking must end by 18:00. Its internal cleanup occupancy may end
after 18:00 because no later booking can begin outside working hours.

## Conflict Detection

Two bookings can use the same room only when either booking, including its
cleanup gap, finishes before the other begins:

```text
requested_end + 15 <= existing_start
or
existing_end + 15 <= requested_start
```

An end plus cleanup time equal to the next start is allowed.

## Stable Allocation

The allocator inspects numbered rooms from `1` through the configured capacity
and returns the lowest room without an active conflict. Existing assignments are
never changed. If every room conflicts, allocation returns no slot and the
application layer must reject the submission.

## Minimum Room Count

The theoretical minimum is calculated with a sweep line over active requests.
Each request contributes a start event and an end event at its requested end plus
15 minutes. End events are processed before starts at the same minute. The peak
number of active intervals is the minimum room count.

For example:

```text
09:00-10:00
09:30-10:30
10:00-11:00
11:15-12:00
```

The minimum is three rooms because the first three cleanup-aware intervals are
simultaneously active at 10:00.
