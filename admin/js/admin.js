/**
 * Jetstop Spam Admin JavaScript
 */

(function($) {
	'use strict';

	var Jetstop = {
		currentPage: 1,

		init: function() {
			this.bindEvents();
			this.loadLogIfNeeded();
		},

		bindEvents: function() {
			$('#jetstop-settings-form').on('submit', this.saveSettings.bind(this));
			$('#jetstop-blacklists-form').on('submit', this.saveBlacklists.bind(this));
			
			$('#jetstop-filter-source, #jetstop-filter-reason').on('change', this.filterLog.bind(this));
			$('#jetstop-filter-ip').on('keypress', function(e) {
				if (e.which === 13) Jetstop.filterLog();
			});
			$('#jetstop-refresh-log').on('click', this.refreshLog.bind(this));
			$('#jetstop-clear-log').on('click', this.clearLog.bind(this));
			
			$(document).on('click', '.jetstop-delete-entry', this.deleteEntry.bind(this));
			$(document).on('click', '.jetstop-pagination .page-btn:not(.current)', this.paginate.bind(this));
		},

		saveSettings: function(e) {
			e.preventDefault();
			var $form = $(e.target);
			var $btn = $form.find('button[type="submit"]');
			var $status = $form.find('.jetstop-save-status');
			var settings = {};

			$form.find('input, select').each(function() {
				var $el = $(this);
				var name = $el.attr('name');
				if (!name) return;
				
				if ($el.attr('type') === 'checkbox') {
					settings[name] = $el.is(':checked') ? '1' : '';
				} else {
					settings[name] = $el.val();
				}
			});

			$btn.prop('disabled', true);
			$status.removeClass('success error').text(jetstopAdmin.i18n.saving);

			$.post(jetstopAdmin.ajaxUrl, {
				action: 'jetstop_save_settings',
				nonce: jetstopAdmin.nonce,
				settings: settings
			}, function(response) {
				if (response.success) {
					$status.addClass('success').text(jetstopAdmin.i18n.saved);
				} else {
					$status.addClass('error').text(response.data.message || jetstopAdmin.i18n.error);
				}
			}).always(function() {
				$btn.prop('disabled', false);
				setTimeout(function() { $status.text(''); }, 3000);
			});
		},

		saveBlacklists: function(e) {
			e.preventDefault();
			var $form = $(e.target);
			var $btn = $form.find('button[type="submit"]');
			var $status = $form.find('.jetstop-save-status');

			$btn.prop('disabled', true);
			$status.removeClass('success error').text(jetstopAdmin.i18n.saving);

			$.post(jetstopAdmin.ajaxUrl, {
				action: 'jetstop_save_blacklists',
				nonce: jetstopAdmin.nonce,
				emails: $form.find('[name="emails"]').val(),
				ips: $form.find('[name="ips"]').val(),
				keywords: $form.find('[name="keywords"]').val()
			}, function(response) {
				if (response.success) {
					$status.addClass('success').text(jetstopAdmin.i18n.saved);
				} else {
					$status.addClass('error').text(response.data.message || jetstopAdmin.i18n.error);
				}
			}).always(function() {
				$btn.prop('disabled', false);
				setTimeout(function() { $status.text(''); }, 3000);
			});
		},

		loadLogIfNeeded: function() {
			if ($('#jetstop-log-table').length) {
				this.loadLog();
			}
		},

		loadLog: function(page) {
			page = page || 1;
			this.currentPage = page;

			var $tbody = $('#jetstop-log-body');
			$tbody.html('<tr><td colspan="5">Loading...</td></tr>');

			$.post(jetstopAdmin.ajaxUrl, {
				action: 'jetstop_get_log',
				nonce: jetstopAdmin.nonce,
				source: $('#jetstop-filter-source').val(),
				reason: $('#jetstop-filter-reason').val(),
				ip: $('#jetstop-filter-ip').val(),
				page: page,
				per_page: 20
			}, function(response) {
				if (response.success) {
					Jetstop.renderLog(response.data);
				}
			});
		},

		renderLog: function(data) {
			var $tbody = $('#jetstop-log-body');
			var $pagination = $('#jetstop-pagination');

			if (!data.entries || !data.entries.length) {
				$tbody.html('<tr><td colspan="5" style="text-align:center;color:#646970;">No entries found</td></tr>');
				$pagination.empty();
				return;
			}

			var html = '';
			data.entries.forEach(function(entry) {
				html += '<tr data-id="' + entry.id + '">' +
					'<td>' + entry.created_at + '</td>' +
					'<td>' + entry.source_label + '</td>' +
					'<td><span class="jetstop-reason-badge">' + entry.reason_label + '</span></td>' +
					'<td><code>' + entry.ip_address + '</code></td>' +
					'<td><a href="#" class="jetstop-delete-entry">Delete</a></td>' +
					'</tr>';
			});
			$tbody.html(html);

			// Pagination
			if (data.pages > 1) {
				var pHtml = '';
				for (var i = 1; i <= Math.min(data.pages, 10); i++) {
					pHtml += '<button class="page-btn' + (i === this.currentPage ? ' current' : '') + '" data-page="' + i + '">' + i + '</button>';
				}
				$pagination.html(pHtml);
			} else {
				$pagination.empty();
			}
		},

		filterLog: function() {
			this.loadLog(1);
		},

		refreshLog: function() {
			this.loadLog(this.currentPage);
		},

		clearLog: function() {
			if (!confirm(jetstopAdmin.i18n.confirmClear)) return;

			$.post(jetstopAdmin.ajaxUrl, {
				action: 'jetstop_clear_log',
				nonce: jetstopAdmin.nonce
			}, function(response) {
				if (response.success) {
					Jetstop.loadLog(1);
				}
			});
		},

		deleteEntry: function(e) {
			e.preventDefault();
			var $row = $(e.target).closest('tr');
			var id = $row.data('id');

			$row.css('opacity', 0.5);

			$.post(jetstopAdmin.ajaxUrl, {
				action: 'jetstop_delete_log',
				nonce: jetstopAdmin.nonce,
				id: id
			}, function(response) {
				if (response.success) {
					$row.fadeOut(300, function() { $(this).remove(); });
				} else {
					$row.css('opacity', 1);
				}
			});
		},

		paginate: function(e) {
			var page = $(e.target).data('page');
			this.loadLog(page);
		}
	};

	$(document).ready(function() {
		Jetstop.init();
	});

})(jQuery);
