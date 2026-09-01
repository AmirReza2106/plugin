const bookingDialog = document.querySelector('[data-booking-dialog]');

if (bookingDialog instanceof HTMLDialogElement) {
	document.querySelectorAll('[data-open-booking-dialog]').forEach((button) => {
		button.addEventListener('click', () => bookingDialog.showModal());
	});

	document.querySelectorAll('[data-close-booking-dialog]').forEach((button) => {
		button.addEventListener('click', () => bookingDialog.close());
	});

	bookingDialog.addEventListener('click', (event) => {
		if (event.target === bookingDialog) {
			bookingDialog.close();
		}
	});
}

document.querySelectorAll('[data-success-dialog]').forEach((dialog) => {
	if (!(dialog instanceof HTMLDialogElement) || typeof dialog.showModal !== 'function') {
		return;
	}

	if (dialog.open) {
		dialog.close();
	}

	dialog.showModal();
});

document.querySelectorAll('.workshop-booking-dialog__form').forEach((form) => {
	form.addEventListener('submit', (event) => {
		const startField = form.querySelector('[name="start_time"]');
		const endField = form.querySelector('[name="end_time"]');

		if (!(startField instanceof HTMLInputElement) || !(endField instanceof HTMLInputElement)) {
			return;
		}

		endField.setCustomValidity('');

		if (startField.value === '' || endField.value === '') {
			return;
		}

		const [startHour, startMinute] = startField.value.split(':').map(Number);
		const [endHour, endMinute] = endField.value.split(':').map(Number);
		const duration = endHour * 60 + endMinute - (startHour * 60 + startMinute);
		const minimum = Number(form.dataset.minDuration);
		const maximum = Number(form.dataset.maxDuration);

		if (duration < minimum || duration > maximum) {
			event.preventDefault();
			endField.setCustomValidity(`مدت جلسه باید بین ${minimum} تا ${maximum} دقیقه باشد.`);
			endField.reportValidity();
		}
	});

	form.addEventListener(
		'invalid',
		(event) => {
			const field = event.target;

			if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement)) {
				return;
			}

			if (field.validity.valueMissing) {
				field.setCustomValidity('لطفاً این فیلد را تکمیل کنید.');
			} else if (field.validity.typeMismatch) {
				field.setCustomValidity('لطفاً یک مقدار معتبر وارد کنید.');
			} else if (field.validity.stepMismatch) {
				field.setCustomValidity('زمان را با فاصله‌های ۱۵ دقیقه‌ای انتخاب کنید.');
			}
		},
		true
	);

	form.addEventListener('input', (event) => {
		const field = event.target;

		if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
			field.setCustomValidity('');
		}
	});
});
