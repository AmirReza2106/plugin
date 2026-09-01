document.querySelectorAll('[data-decision-form]').forEach((form) => {
	form.addEventListener('submit', (event) => {
		if (!window.confirm(form.dataset.confirm || 'آیا از ثبت این تصمیم مطمئن هستید؟')) {
			event.preventDefault();
		}
	});
});
