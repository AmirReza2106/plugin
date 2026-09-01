<?php
/**
 * Administrator request management screen.
 *
 * @var WorkshopRegistration\Domain\WorkshopStatus|null                         $status
 * @var string|null                                                             $date
 * @var string                                                                  $search
 * @var WorkshopRegistration\Application\AdminRequests\AdminRequestPage       $requests
 * @var array<int, list<WorkshopRegistration\Application\AdminRequests\AdminStatusHistoryItem>> $history
 * @var string|null                                                             $notice
 * @var WorkshopRegistration\Admin\AdminRequestsPage                           $this
 *
 * @package WorkshopRegistration
 */

defined( 'ABSPATH' ) || exit;

$notice_data = $this->noticeData( $notice );
$all_count   = array_sum( $requests->statusCounts );
$tabs        = array(
	''         => array( 'همه', $all_count ),
	'pending'  => array( 'در انتظار تأیید', $requests->statusCounts['pending'] ),
	'approved' => array( 'تأییدشده', $requests->statusCounts['approved'] ),
	'rejected' => array( 'ردشده', $requests->statusCounts['rejected'] ),
);
$base_args   = array_filter(
	array(
		'page'           => WorkshopRegistration\Admin\AdminRequestsPage::PAGE_SLUG,
		'request_status' => $status?->value,
		'request_date'   => $date,
		'request_search' => $search,
	)
);
?>
<div class="wrap workshop-admin-requests" dir="rtl">
	<header class="workshop-admin-requests__hero">
		<div>
			<p>سامانه داخلی شرکت</p>
			<h1>مدیریت درخواست‌های رزرو</h1>
			<span>درخواست‌های کارکنان را بررسی کنید و تصمیم نهایی بگیرید.</span>
		</div>
		<div class="workshop-admin-requests__summary">
			<strong><?php echo esc_html( (string) $requests->statusCounts['pending'] ); ?></strong>
			<span>در انتظار بررسی</span>
		</div>
	</header>

	<?php if ( null !== $notice_data ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_data['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice_data['message'] ); ?></p></div>
	<?php endif; ?>

	<section class="workshop-admin-requests__panel" aria-labelledby="request-filters-title">
		<div class="workshop-admin-requests__panel-heading">
			<div>
				<h2 id="request-filters-title">درخواست‌ها</h2>
				<p><?php echo esc_html( (string) $requests->totalItems ); ?> نتیجه مطابق فیلترهای انتخاب‌شده</p>
			</div>
			<a class="button" href="<?php echo esc_url( add_query_arg( 'page', WorkshopRegistration\Employee\EmployeeDashboardPage::PAGE_SLUG, admin_url( 'admin.php' ) ) ); ?>">مشاهده وضعیت اتاق‌ها</a>
		</div>

		<nav class="workshop-admin-requests__tabs" aria-label="فیلتر وضعیت درخواست‌ها">
			<?php foreach ( $tabs as $tab_status => $tab ) : ?>
				<?php
				$tab_url = add_query_arg(
					array_filter(
						array(
							'page'           => WorkshopRegistration\Admin\AdminRequestsPage::PAGE_SLUG,
							'request_status' => $tab_status,
							'request_date'   => $date,
							'request_search' => $search,
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

		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="workshop-admin-requests__filters">
			<input type="hidden" name="page" value="<?php echo esc_attr( WorkshopRegistration\Admin\AdminRequestsPage::PAGE_SLUG ); ?>">
			<?php if ( null !== $status ) : ?>
				<input type="hidden" name="request_status" value="<?php echo esc_attr( $status->value ); ?>">
			<?php endif; ?>
			<label>
				<span>جست‌وجو</span>
				<input type="search" name="request_search" value="<?php echo esc_attr( $search ); ?>" maxlength="100" placeholder="نام، عنوان، ایمیل، موبایل یا شناسه">
			</label>
			<label>
				<span>تاریخ جلسه</span>
				<input type="date" name="request_date" value="<?php echo esc_attr( $date ?? '' ); ?>">
			</label>
			<button type="submit" class="button button-primary">اعمال فیلتر</button>
			<a class="button" href="<?php echo esc_url( add_query_arg( 'page', WorkshopRegistration\Admin\AdminRequestsPage::PAGE_SLUG, admin_url( 'admin.php' ) ) ); ?>">پاک کردن</a>
		</form>

		<?php if ( array() === $requests->items ) : ?>
			<div class="workshop-admin-requests__empty">
				<span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
				<p>درخواستی مطابق این فیلترها وجود ندارد.</p>
			</div>
		<?php else : ?>
			<div class="workshop-admin-requests__list">
				<?php foreach ( $requests->items as $request ) : ?>
					<article class="workshop-admin-request workshop-admin-request--<?php echo esc_attr( $request->status->value ); ?>">
						<header>
							<div class="workshop-admin-request__identity">
								<span class="workshop-admin-status workshop-admin-status--<?php echo esc_attr( $request->status->value ); ?>"><?php echo esc_html( $this->statusLabel( $request->status ) ); ?></span>
								<h3><?php echo esc_html( $request->workshopTitle ); ?></h3>
								<code dir="ltr"><?php echo esc_html( $request->publicReference ); ?></code>
							</div>
							<dl class="workshop-admin-request__schedule">
								<div><dt>تاریخ</dt><dd><?php echo esc_html( $request->workshopDate ); ?></dd></div>
								<div><dt>ساعت</dt><dd dir="ltr"><?php echo esc_html( $request->startTime . '–' . $request->endTime ); ?></dd></div>
								<div><dt>اتاق</dt><dd><?php echo null === $request->slotNumber ? '—' : esc_html( (string) $request->slotNumber ); ?></dd></div>
							</dl>
						</header>

						<details>
							<summary>مشاهده اطلاعات کامل درخواست</summary>
							<div class="workshop-admin-request__details">
								<dl>
									<div><dt>نام درخواست‌دهنده</dt><dd><?php echo esc_html( $request->firstName . ' ' . $request->lastName ); ?></dd></div>
									<div><dt>شناسه کاربر</dt><dd><?php echo esc_html( (string) $request->requesterUserId ); ?></dd></div>
									<div><dt>موبایل</dt><dd dir="ltr"><?php echo esc_html( $request->mobile ); ?></dd></div>
									<div><dt>ایمیل</dt><dd dir="ltr"><a href="mailto:<?php echo esc_attr( $request->email ); ?>"><?php echo esc_html( $request->email ); ?></a></dd></div>
									<div><dt>تاریخ ثبت</dt><dd dir="ltr"><?php echo esc_html( $request->createdAt ); ?> UTC</dd></div>
									<?php if ( null !== $request->reviewedBy ) : ?>
										<div><dt>شناسه بررسی‌کننده</dt><dd><?php echo esc_html( (string) $request->reviewedBy ); ?></dd></div>
									<?php endif; ?>
								</dl>
								<div class="workshop-admin-request__description"><strong>توضیحات</strong><p><?php echo esc_html( $request->description ); ?></p></div>
								<section class="workshop-admin-request__history" aria-label="سابقه وضعیت درخواست">
									<strong>سابقه وضعیت</strong>
									<ol>
										<?php foreach ( $history[ $request->id ] ?? array() as $event ) : ?>
											<li>
												<span class="workshop-admin-request__history-marker" aria-hidden="true"></span>
												<div>
													<b><?php echo esc_html( $this->statusLabel( $event->toStatus ) ); ?></b>
													<span>
														<?php if ( null === $event->fromStatus ) : ?>ثبت اولیه درخواست<?php else : ?>تغییر از <?php echo esc_html( $this->statusLabel( $event->fromStatus ) ); ?><?php endif; ?>
														<?php if ( null !== $event->actorUserId ) : ?> توسط <?php echo esc_html( $event->actorDisplayName ?? ( 'کاربر ' . $event->actorUserId ) ); ?><?php endif; ?>
													</span>
													<time dir="ltr" datetime="<?php echo esc_attr( str_replace( ' ', 'T', $event->createdAt ) . 'Z' ); ?>"><?php echo esc_html( $event->createdAt ); ?> UTC</time>
												</div>
											</li>
										<?php endforeach; ?>
									</ol>
								</section>
							</div>
						</details>

						<?php if ( WorkshopRegistration\Domain\WorkshopStatus::Pending === $request->status ) : ?>
							<div class="workshop-admin-request__actions">
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-decision-form data-confirm="درخواست «<?php echo esc_attr( $request->workshopTitle ); ?>» تأیید شود؟ این تصمیم نهایی است.">
									<input type="hidden" name="action" value="<?php echo esc_attr( WorkshopRegistration\Admin\AdminRequestDecisionHandler::ACTION ); ?>">
									<input type="hidden" name="request_id" value="<?php echo esc_attr( (string) $request->id ); ?>">
									<input type="hidden" name="decision" value="approve">
									<?php wp_nonce_field( WorkshopRegistration\Admin\AdminRequestDecisionHandler::ACTION, WorkshopRegistration\Admin\AdminRequestDecisionHandler::NONCE_NAME ); ?>
									<button type="submit" class="button button-primary">تأیید درخواست</button>
								</form>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-decision-form data-confirm="درخواست «<?php echo esc_attr( $request->workshopTitle ); ?>» رد شود و اتاق آن آزاد شود؟ این تصمیم نهایی است.">
									<input type="hidden" name="action" value="<?php echo esc_attr( WorkshopRegistration\Admin\AdminRequestDecisionHandler::ACTION ); ?>">
									<input type="hidden" name="request_id" value="<?php echo esc_attr( (string) $request->id ); ?>">
									<input type="hidden" name="decision" value="reject">
									<?php wp_nonce_field( WorkshopRegistration\Admin\AdminRequestDecisionHandler::ACTION, WorkshopRegistration\Admin\AdminRequestDecisionHandler::NONCE_NAME ); ?>
									<button type="submit" class="button workshop-admin-request__reject">رد درخواست</button>
								</form>
							</div>
						<?php else : ?>
							<p class="workshop-admin-request__final">تصمیم این درخواست نهایی و در سابقه وضعیت ثبت شده است.</p>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $requests->totalPages() > 1 ) : ?>
			<div class="workshop-admin-requests__pagination">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( array_merge( $base_args, array( 'request_page' => '%#%' ) ), admin_url( 'admin.php' ) ),
							'format'    => '',
							'current'   => $requests->currentPage,
							'total'     => $requests->totalPages(),
							'prev_text' => 'قبلی',
							'next_text' => 'بعدی',
						)
					)
				);
				?>
			</div>
		<?php endif; ?>
	</section>
</div>
