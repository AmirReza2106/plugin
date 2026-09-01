<?php
/**
 * Employee room booking dashboard.
 *
 * @var string                                                                                   $selected_date
 * @var WorkshopRegistration\Domain\WorkshopStatus|null                                          $status
 * @var WorkshopRegistration\Domain\Scheduling\SchedulingRules                                  $rules
 * @var int                                                                                      $capacity
 * @var list<WorkshopRegistration\Application\EmployeeDashboard\RoomAvailability>               $rooms
 * @var WorkshopRegistration\Application\EmployeeDashboard\EmployeeBookingPage                  $bookings
 * @var array<string, mixed>|null                                                                $notice
 * @var WP_User                                                                                  $current_user
 * @var WorkshopRegistration\Employee\EmployeeDashboardPage                                      $this
 *
 * @package WorkshopRegistration
 */

defined( 'ABSPATH' ) || exit;

$all_count = array_sum( $bookings->statusCounts );
$base_url  = add_query_arg( 'page', WorkshopRegistration\Employee\EmployeeDashboardPage::PAGE_SLUG, admin_url( 'admin.php' ) );
$tabs      = array(
	''         => array( 'همه', $all_count ),
	'pending'  => array( 'در انتظار تأیید', $bookings->statusCounts['pending'] ),
	'approved' => array( 'تأییدشده', $bookings->statusCounts['approved'] ),
	'rejected' => array( 'ردشده', $bookings->statusCounts['rejected'] ),
);
?>
<div class="wrap workshop-dashboard" dir="rtl">
	<header class="workshop-dashboard__hero">
		<div>
			<p class="workshop-dashboard__eyebrow">سامانه داخلی شرکت</p>
			<h1>رزرو اتاق جلسه</h1>
			<p>برنامه اتاق‌ها را بررسی کنید و درخواست رزرو جدیدی ثبت کنید.</p>
		</div>
		<?php if ( current_user_can( WorkshopRegistration\Access\RoleManager::CREATE_BOOKINGS ) ) : ?>
			<button type="button" class="workshop-dashboard__primary" data-open-booking-dialog>
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
				درخواست رزرو اتاق
			</button>
		<?php endif; ?>
	</header>

	<?php if ( is_array( $notice ) && 'error' === ( $notice['type'] ?? '' ) ) : ?>
		<div class="workshop-dashboard__alert workshop-dashboard__alert--error" role="alert">
			<?php echo esc_html( $this->errorMessage( (string) ( $notice['code'] ?? '' ) ) ); ?>
		</div>
	<?php endif; ?>

	<section class="workshop-dashboard__section" aria-labelledby="room-availability-title">
		<div class="workshop-dashboard__section-heading">
			<div>
				<h2 id="room-availability-title">وضعیت اتاق‌ها</h2>
				<p>بازه‌های اشغال و آزاد بدون نمایش اطلاعات شخصی کارکنان</p>
			</div>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="workshop-dashboard__date-form">
				<input type="hidden" name="page" value="<?php echo esc_attr( WorkshopRegistration\Employee\EmployeeDashboardPage::PAGE_SLUG ); ?>">
				<label for="workshop-booking-date">تاریخ برنامه</label>
				<input id="workshop-booking-date" type="date" name="booking_date" value="<?php echo esc_attr( $selected_date ); ?>">
				<button type="submit" class="button">نمایش</button>
			</form>
		</div>

		<div class="workshop-dashboard__legend" aria-label="راهنمای وضعیت زمان‌ها">
			<span><i class="workshop-dashboard__legend-dot workshop-dashboard__legend-dot--available"></i> آزاد</span>
			<span><i class="workshop-dashboard__legend-dot workshop-dashboard__legend-dot--occupied"></i> اشغال</span>
		</div>

		<div class="workshop-dashboard__rooms">
			<?php foreach ( $rooms as $room ) : ?>
				<article class="workshop-room-card">
					<header>
						<h3>اتاق <?php echo esc_html( (string) $room->slotNumber ); ?></h3>
						<span><?php echo esc_html( count( $room->occupied ) . ' بازه اشغال' ); ?></span>
					</header>
					<div class="workshop-room-card__hours" aria-hidden="true">
						<span><?php echo esc_html( $this->formatMinute( $rules->workdayStartMinute ) ); ?></span>
						<span><?php echo esc_html( $this->formatMinute( $rules->workdayEndMinute ) ); ?></span>
					</div>
					<div class="workshop-room-card__timeline" aria-label="نمودار زمانی اتاق <?php echo esc_attr( (string) $room->slotNumber ); ?>">
						<?php foreach ( $room->occupied as $period ) : ?>
							<span
								class="workshop-room-card__occupied"
								style="<?php echo esc_attr( $this->periodStyle( $period, $rules ) ); ?>"
								title="اشغال: <?php echo esc_attr( $this->formatMinute( $period->startMinute ) . ' تا ' . $this->formatMinute( $period->endMinute ) ); ?>"
							></span>
						<?php endforeach; ?>
					</div>
					<div class="workshop-room-card__periods">
						<strong>زمان‌های آزاد:</strong>
						<?php if ( array() === $room->available ) : ?>
							<span class="workshop-room-card__none">زمان آزادی وجود ندارد</span>
						<?php else : ?>
							<?php foreach ( $room->available as $period ) : ?>
								<span><?php echo esc_html( $this->formatMinute( $period->startMinute ) . ' تا ' . $this->formatMinute( $period->endMinute ) ); ?></span>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="workshop-dashboard__section" aria-labelledby="my-bookings-title">
		<div class="workshop-dashboard__section-heading">
			<div>
				<h2 id="my-bookings-title">درخواست‌های من</h2>
				<p>وضعیت درخواست‌هایی که با حساب شما ثبت شده‌اند</p>
			</div>
		</div>

		<nav class="workshop-dashboard__tabs" aria-label="فیلتر وضعیت درخواست‌ها">
			<?php foreach ( $tabs as $tab_status => $tab ) : ?>
				<?php
				$tab_url = add_query_arg(
					array_filter(
						array(
							'page'           => WorkshopRegistration\Employee\EmployeeDashboardPage::PAGE_SLUG,
							'booking_date'   => $selected_date,
							'booking_status' => $tab_status,
						)
					),
					admin_url( 'admin.php' )
				);
				$is_active = ( $status?->value ?? '' ) === $tab_status;
				?>
				<a href="<?php echo esc_url( $tab_url ); ?>" class="<?php echo $is_active ? 'is-active' : ''; ?>" <?php echo $is_active ? 'aria-current="page"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php echo esc_html( $tab[0] ); ?> <span><?php echo esc_html( (string) $tab[1] ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>

		<?php if ( array() === $bookings->items ) : ?>
			<div class="workshop-dashboard__empty">
				<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
				<p>درخواستی در این بخش وجود ندارد.</p>
			</div>
		<?php else : ?>
			<div class="workshop-dashboard__booking-list">
				<?php foreach ( $bookings->items as $booking ) : ?>
					<article class="workshop-booking-card">
						<div class="workshop-booking-card__main">
							<span class="workshop-status workshop-status--<?php echo esc_attr( $booking->status->value ); ?>">
								<?php echo esc_html( $this->statusLabel( $booking->status ) ); ?>
							</span>
							<h3><?php echo esc_html( $booking->workshopTitle ); ?></h3>
							<code dir="ltr"><?php echo esc_html( $booking->publicReference ); ?></code>
						</div>
						<dl>
							<div><dt>تاریخ</dt><dd><?php echo esc_html( $booking->workshopDate ); ?></dd></div>
							<div><dt>ساعت</dt><dd dir="ltr"><?php echo esc_html( $booking->startTime . '–' . $booking->endTime ); ?></dd></div>
							<div><dt>اتاق</dt><dd><?php echo null === $booking->slotNumber ? '—' : esc_html( (string) $booking->slotNumber ); ?></dd></div>
						</dl>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $bookings->totalPages() > 1 ) : ?>
			<div class="workshop-dashboard__pagination">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( 'booking_page', '%#%', $base_url ),
							'format'    => '',
							'current'   => $bookings->currentPage,
							'total'     => $bookings->totalPages(),
							'prev_text' => 'قبلی',
							'next_text' => 'بعدی',
						)
					)
				);
				?>
			</div>
		<?php endif; ?>
	</section>

	<dialog class="workshop-booking-dialog" data-booking-dialog aria-labelledby="booking-dialog-title">
		<div class="workshop-booking-dialog__header">
			<div>
				<span>درخواست جدید</span>
				<h2 id="booking-dialog-title">رزرو اتاق جلسه</h2>
			</div>
			<button type="button" data-close-booking-dialog aria-label="بستن پنجره">×</button>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="workshop-booking-dialog__form" data-min-duration="<?php echo esc_attr( (string) $rules->minimumDuration ); ?>" data-max-duration="<?php echo esc_attr( (string) $rules->maximumDuration ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( WorkshopRegistration\Employee\EmployeeBookingHandler::ACTION ); ?>">
			<?php wp_nonce_field( WorkshopRegistration\Employee\EmployeeBookingHandler::ACTION, WorkshopRegistration\Employee\EmployeeBookingHandler::NONCE_NAME ); ?>
			<div class="workshop-booking-dialog__grid">
				<label>نام<input type="text" name="first_name" value="<?php echo esc_attr( $current_user->first_name ); ?>" maxlength="100" required></label>
				<label>نام خانوادگی<input type="text" name="last_name" value="<?php echo esc_attr( $current_user->last_name ); ?>" maxlength="100" required></label>
				<label>شماره موبایل<input type="tel" name="mobile" maxlength="32" inputmode="tel" dir="ltr" required></label>
				<label>ایمیل<input type="email" name="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" maxlength="254" dir="ltr" required></label>
				<label class="is-wide">عنوان جلسه<input type="text" name="workshop_title" maxlength="200" required></label>
				<label>تاریخ<input type="date" name="workshop_date" min="<?php echo esc_attr( wp_date( 'Y-m-d', null, wp_timezone() ) ); ?>" value="<?php echo esc_attr( $selected_date ); ?>" required></label>
				<label>ساعت شروع<input type="time" name="start_time" min="<?php echo esc_attr( $this->formatMinute( $rules->workdayStartMinute ) ); ?>" max="<?php echo esc_attr( $this->formatMinute( $rules->workdayEndMinute - $rules->minimumDuration ) ); ?>" step="900" required></label>
				<label>ساعت پایان<input type="time" name="end_time" min="<?php echo esc_attr( $this->formatMinute( $rules->workdayStartMinute + $rules->minimumDuration ) ); ?>" max="<?php echo esc_attr( $this->formatMinute( $rules->workdayEndMinute ) ); ?>" step="900" required></label>
				<label class="is-wide">توضیحات<textarea name="description" maxlength="2000" rows="4" required></textarea></label>
			</div>
			<p class="workshop-booking-dialog__rule">مدت رزرو باید بین <?php echo esc_html( (string) $rules->minimumDuration ); ?> تا <?php echo esc_html( (string) $rules->maximumDuration ); ?> دقیقه و در ساعات کاری باشد.</p>
			<div class="workshop-booking-dialog__actions">
				<button type="button" class="button" data-close-booking-dialog>انصراف</button>
				<button type="submit" class="button button-primary">ثبت درخواست</button>
			</div>
		</form>
	</dialog>

	<?php if ( is_array( $notice ) && 'success' === ( $notice['type'] ?? '' ) ) : ?>
		<dialog class="workshop-success-dialog" data-success-dialog aria-labelledby="booking-success-title" open>
			<div class="workshop-success-dialog__icon" aria-hidden="true">✓</div>
			<h2 id="booking-success-title">درخواست با موفقیت ثبت شد</h2>
			<p>درخواست شما در انتظار تأیید مدیر قرار گرفت.</p>
			<dl>
				<div><dt>شناسه درخواست</dt><dd><code dir="ltr"><?php echo esc_html( (string) ( $notice['reference'] ?? '' ) ); ?></code></dd></div>
				<div><dt>اتاق اختصاص‌یافته</dt><dd><?php echo esc_html( (string) ( $notice['room'] ?? '' ) ); ?></dd></div>
			</dl>
			<form method="dialog"><button type="submit" class="button button-primary" autofocus>بستن</button></form>
		</dialog>
	<?php endif; ?>
</div>
