/**
 * Jetstop Spam - Frontend Honeypot & Protection
 * 
 * This script injects honeypot fields and JS validation into forms.
 * Bots don't execute JavaScript, so they can't pass these checks.
 */

(function() {
	'use strict';

	if (typeof jetstopConfig === 'undefined') {
		return;
	}

	var config = jetstopConfig;

	/**
	 * Initialize protection on page load.
	 */
	function init() {
		// Wait for DOM ready
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', setup);
		} else {
			setup();
		}
	}

	/**
	 * Setup protection for all forms.
	 */
	function setup() {
		var forms = document.querySelectorAll('form');
		
		forms.forEach(function(form) {
			// Skip admin forms and search forms
			if (form.id === 'adminbar-search' || 
				form.classList.contains('search-form') ||
				form.getAttribute('role') === 'search') {
				return;
			}

			// Skip if already processed
			if (form.getAttribute('data-jetstop')) {
				return;
			}

			form.setAttribute('data-jetstop', 'protected');
			
			// Inject honeypot field
			injectHoneypot(form);
			
			// Inject timestamp
			if (config.timestamp) {
				injectTimestamp(form);
			}
			
			// Inject JS verification
			if (config.jsCheck) {
				injectJsCheck(form);
			}
		});

		// Watch for dynamically added forms
		observeNewForms();
	}

	/**
	 * Inject honeypot field into form.
	 */
	function injectHoneypot(form) {
		var fieldName = config.field || 'website_url';
		
		// Create wrapper (hidden from users)
		var wrapper = document.createElement('div');
		wrapper.className = 'jetstop-hp-wrap';
		wrapper.setAttribute('aria-hidden', 'true');
		wrapper.style.cssText = 'position:absolute!important;left:-9999px!important;top:-9999px!important;width:1px!important;height:1px!important;overflow:hidden!important;';

		// Create label
		var label = document.createElement('label');
		label.textContent = 'Leave empty';
		label.setAttribute('for', 'jetstop_' + fieldName);

		// Create input
		var input = document.createElement('input');
		input.type = 'text';
		input.name = fieldName;
		input.id = 'jetstop_' + fieldName;
		input.value = '';
		input.tabIndex = -1;
		input.autocomplete = 'off';

		wrapper.appendChild(label);
		wrapper.appendChild(input);

		// Insert at random position to confuse bots
		var children = form.children;
		if (children.length > 2) {
			var pos = Math.floor(Math.random() * (children.length - 1)) + 1;
			form.insertBefore(wrapper, children[pos]);
		} else {
			form.appendChild(wrapper);
		}
	}

	/**
	 * Inject timestamp field.
	 */
	function injectTimestamp(form) {
		var input = document.createElement('input');
		input.type = 'hidden';
		input.name = 'jetstop_ts';
		input.value = btoa(String(Math.floor(Date.now() / 1000)));
		form.appendChild(input);
	}

	/**
	 * Inject JS verification field.
	 */
	function injectJsCheck(form) {
		var input = document.createElement('input');
		input.type = 'hidden';
		input.name = 'jetstop_js';
		input.value = 'verified';
		form.appendChild(input);
	}

	/**
	 * Watch for dynamically added forms (for AJAX-loaded content).
	 */
	function observeNewForms() {
		if (typeof MutationObserver === 'undefined') {
			return;
		}

		var observer = new MutationObserver(function(mutations) {
			mutations.forEach(function(mutation) {
				mutation.addedNodes.forEach(function(node) {
					if (node.nodeType === 1) {
						// Check if the node is a form
						if (node.tagName === 'FORM') {
							setup();
						}
						// Check for forms inside the node
						var forms = node.querySelectorAll ? node.querySelectorAll('form') : [];
						if (forms.length) {
							setup();
						}
					}
				});
			});
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	}

	// Initialize
	init();

})();
