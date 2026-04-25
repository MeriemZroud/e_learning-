import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

document.addEventListener('DOMContentLoaded', function () {
	const closeAllNotificationPanels = function () {
		document.querySelectorAll('.student-notifications-panel.is-visible').forEach(function (panel) {
			panel.classList.remove('is-visible');
			panel.setAttribute('aria-hidden', 'true');

			const wrapper = panel.closest('.student-notifications');
			const toggle = wrapper ? wrapper.querySelector('#notifications-toggle, [data-notifications-toggle]') : null;
			if (toggle) {
				toggle.setAttribute('aria-expanded', 'false');
			}
		});
	};

	document.addEventListener('click', function (event) {
		const toggle = event.target.closest('.student-notifications__button');
		const panel = event.target.closest('.student-notifications-panel');

		if (toggle) {
			const wrapper = toggle.closest('.student-notifications');
			const targetPanel = wrapper ? wrapper.querySelector('.student-notifications-panel') : null;

			if (!targetPanel) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();

			const isVisible = targetPanel.classList.contains('is-visible');
			closeAllNotificationPanels();

			if (!isVisible) {
				targetPanel.classList.add('is-visible');
				targetPanel.setAttribute('aria-hidden', 'false');
				toggle.setAttribute('aria-expanded', 'true');
			}

			return;
		}

		if (panel) {
			event.stopPropagation();
			return;
		}

		closeAllNotificationPanels();
	}, true);

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			closeAllNotificationPanels();
		}
	});
});

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
