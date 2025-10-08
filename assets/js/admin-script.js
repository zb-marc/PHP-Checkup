/**
 * AS PHP Checkup Admin JavaScript
 *
 * @package AS_PHP_Checkup
 * @since 1.0.0
 * @version 1.2.0
 */

(function($) {
	'use strict';

	/**
	 * PHP Checkup Admin Handler
	 */
	var ASPhpCheckup = {
		
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
			this.initHealthScore();
			this.initTooltips();
			this.initSolutions();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			$('#refresh-check').on('click', this.refreshCheck.bind(this));
			$('#export-report').on('click', this.exportReport.bind(this));
			$('#analyze-plugins').on('click', this.analyzePlugins.bind(this));
			
			// Solution events - New in 1.2.0
			$('.apply-solution').on('click', this.applySolution.bind(this));
			$('.download-config').on('click', this.downloadConfig.bind(this));
			
			// Auto-refresh every 5 minutes if page is active
			this.startAutoRefresh();
			
			// Copy API endpoints on click
			$('.api-endpoints td:first-child, .cli-commands td:first-child').on('click', this.copyToClipboard.bind(this));
		},

		/**
		 * Initialize health score animation
		 */
		initHealthScore: function() {
			var $scoreCircle = $('.health-score-circle');
			if ($scoreCircle.length) {
				var score = $scoreCircle.data('score');
				var $circle = $scoreCircle.find('svg circle:nth-child(2)');
				
				// Animate score number
				var $scoreText = $scoreCircle.find('.score');
				var currentScore = 0;
				var targetScore = parseInt(score);
				var increment = targetScore / 30;
				
				var scoreInterval = setInterval(function() {
					currentScore += increment;
					if (currentScore >= targetScore) {
						currentScore = targetScore;
						clearInterval(scoreInterval);
					}
					$scoreText.text(Math.round(currentScore) + '%');
				}, 50);
				
				// Update color based on score
				var color = '#46b450'; // Optimal
				if (score < 50) {
					color = '#dc3232'; // Warning
				} else if (score < 75) {
					color = '#ffb900'; // Acceptable
				}
				$circle.css('stroke', color);
			}
		},

		/**
		 * Initialize tooltips
		 */
		initTooltips: function() {
			// Add tooltips to requirement sources
			$('.requirement-source').each(function() {
				var $this = $(this);
				var text = $this.text();
				$this.addClass('tooltip');
				$this.append('<span class="tooltiptext">' + text + '</span>');
			});
		},

		/**
		 * Initialize solutions - New in 1.2.0
		 */
		initSolutions: function() {
			// Test write permissions on load
			this.testWritePermissions();
			
			// Initialize solution cards
			$('.solution-card').each(function() {
				var $card = $(this);
				var hasAutoApply = $card.find('.apply-solution').length > 0;
				
				if (hasAutoApply) {
					$card.addClass('can-auto-apply');
				}
			});
		},

		/**
		 * Test write permissions - New in 1.2.0
		 */
		testWritePermissions: function() {
			$.ajax({
				url: asPhpCheckup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'as_php_checkup_test_writeable',
					nonce: asPhpCheckup.nonce
				},
				success: function(response) {
					if (response.success) {
						// Update UI based on permissions
						if (!response.data.php_ini) {
							$('.apply-solution[data-type="php_ini"]').prop('disabled', true)
								.attr('title', 'No write permission for php.ini');
						}
						if (!response.data.htaccess) {
							$('.apply-solution[data-type="htaccess"]').prop('disabled', true)
								.attr('title', 'No write permission for .htaccess');
						}
						if (!response.data.wp_config) {
							$('.apply-solution[data-type="wp_config"]').prop('disabled', true)
								.attr('title', 'No write permission for wp-config.php');
						}
					}
				}
			});
		},

		/**
		 * Refresh check via AJAX
		 */
		refreshCheck: function(e) {
			e.preventDefault();
			
			var $button = $(e.currentTarget);
			var originalText = $button.html();
			
			// Update button state
			$button.prop('disabled', true)
				.html('<span class="dashicons dashicons-update spinning"></span> ' + asPhpCheckup.refreshing);
			
			// Add loading state to wrap
			$('.as-php-checkup-wrap').addClass('loading');
			
			// Send AJAX request
			$.ajax({
				url: asPhpCheckup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'as_php_checkup_refresh',
					nonce: asPhpCheckup.nonce
				},
				success: function(response) {
					if (response.success) {
						// Update last check time
						$('#last-check-time').text(response.data.time);
						
						// Show success notice
						ASPhpCheckup.showNotice(response.data.message, 'success');
						
						// Reload page to show updated results
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						ASPhpCheckup.showNotice(response.data || asPhpCheckup.error, 'error');
						
						// Reset button
						$button.prop('disabled', false).html(originalText);
						$('.as-php-checkup-wrap').removeClass('loading');
					}
				},
				error: function() {
					ASPhpCheckup.showNotice(asPhpCheckup.error, 'error');
					
					// Reset button
					$button.prop('disabled', false).html(originalText);
					$('.as-php-checkup-wrap').removeClass('loading');
				}
			});
		},

		/**
		 * Analyze plugins via AJAX
		 */
		analyzePlugins: function(e) {
			e.preventDefault();
			
			var $button = $(e.currentTarget);
			var originalText = $button.html();
			
			// Update button state
			$button.prop('disabled', true)
				.html('<span class="dashicons dashicons-admin-plugins spinning"></span> ' + asPhpCheckup.analyzingPlugins);
			
			// Add loading state
			$('.as-php-checkup-wrap').addClass('loading');
			
			// Send AJAX request
			$.ajax({
				url: asPhpCheckup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'as_php_checkup_analyze_plugins',
					nonce: asPhpCheckup.nonce
				},
				success: function(response) {
					if (response.success) {
						// Update last analysis time
						$('#last-analysis-time').text('just now');
						
						// Show success notice
						ASPhpCheckup.showNotice(response.data.message, 'success');
						
						// Reload page to show updated results
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						ASPhpCheckup.showNotice(response.data || asPhpCheckup.error, 'error');
						
						// Reset button
						$button.prop('disabled', false).html(originalText);
						$('.as-php-checkup-wrap').removeClass('loading');
					}
				},
				error: function() {
					ASPhpCheckup.showNotice(asPhpCheckup.error, 'error');
					
					// Reset button
					$button.prop('disabled', false).html(originalText);
					$('.as-php-checkup-wrap').removeClass('loading');
				}
			});
		},

		/**
		 * Export report
		 */
		exportReport: function(e) {
			e.preventDefault();
			
			var $button = $(e.currentTarget);
			var originalText = $button.html();
			
			// Update button state
			$button.prop('disabled', true)
				.html('<span class="dashicons dashicons-download"></span> ' + asPhpCheckup.exportingReport);
			
			// Send AJAX request
			$.ajax({
				url: asPhpCheckup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'as_php_checkup_export',
					nonce: asPhpCheckup.nonce
				},
				success: function(response) {
					if (response.success) {
						// Create and download CSV file
						ASPhpCheckup.downloadCSV(response.data.content, response.data.filename);
						
						// Show success message
						ASPhpCheckup.showNotice('Report exported successfully!', 'success');
					} else {
						ASPhpCheckup.showNotice(response.data || asPhpCheckup.error, 'error');
					}
					
					// Reset button
					$button.prop('disabled', false).html(originalText);
				},
				error: function() {
					ASPhpCheckup.showNotice(asPhpCheckup.error, 'error');
					
					// Reset button
					$button.prop('disabled', false).html(originalText);
				}
			});
		},

		/**
		 * Apply solution - New in 1.2.0
		 */
		applySolution: function(e) {
			e.preventDefault();
			
			var $button = $(e.currentTarget);
			var solutionType = $button.data('type');
			var $card = $button.closest('.solution-card');
			
			// Confirm action
			if (!confirm(asPhpCheckup.confirmApply)) {
				return;
			}
			
			// Update UI
			$button.prop('disabled', true);
			var originalText = $button.html();
			$button.html('<span class="dashicons dashicons-update spinning"></span> ' + asPhpCheckup.applyingSolution);
			$card.addClass('loading');
			
			// Send AJAX request
			$.ajax({
				url: asPhpCheckup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'as_php_checkup_apply_solution',
					solution_type: solutionType,
					nonce: asPhpCheckup.nonce
				},
				success: function(response) {
					if (response.success) {
						// Show success
						$card.removeClass('loading').addClass('applied');
						$card.find('.solution-content').append(
							'<div class="solution-applied-message">' + 
							'<span class="dashicons dashicons-yes"></span> ' +
							response.data.message + 
							'</div>'
						);
						
						ASPhpCheckup.showNotice(asPhpCheckup.solutionApplied, 'success');
						
						// Refresh check after 2 seconds
						setTimeout(function() {
							$('#refresh-check').trigger('click');
						}, 2000);
					} else {
						ASPhpCheckup.showNotice(response.data || asPhpCheckup.error, 'error');
						$button.prop('disabled', false).html(originalText);
						$card.removeClass('loading');
					}
				},
				error: function() {
					ASPhpCheckup.showNotice(asPhpCheckup.error, 'error');
					$button.prop('disabled', false).html(originalText);
					$card.removeClass('loading');
				}
			});
		},

		/**
		 * Download configuration - New in 1.2.0
		 */
		downloadConfig: function(e) {
			e.preventDefault();
			
			var $button = $(e.currentTarget);
			var configType = $button.data('type');
			
			// Update UI
			$button.prop('disabled', true);
			var originalText = $button.html();
			$button.html('<span class="dashicons dashicons-download"></span> ' + asPhpCheckup.downloadingConfig);
			
			// Send AJAX request
			$.ajax({
				url: asPhpCheckup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'as_php_checkup_download_config',
					config_type: configType,
					nonce: asPhpCheckup.nonce
				},
				success: function(response) {
					if (response.success) {
						// Create download
						ASPhpCheckup.downloadFile(response.data.content, response.data.filename, 'text/plain');
						
						// Show preview modal if content is small enough
						if (response.data.content.length < 10000) {
							ASPhpCheckup.showConfigPreview(response.data.filename, response.data.content);
						}
					} else {
						ASPhpCheckup.showNotice(response.data || asPhpCheckup.error, 'error');
					}
					
					// Reset button
					$button.prop('disabled', false).html(originalText);
				},
				error: function() {
					ASPhpCheckup.showNotice(asPhpCheckup.error, 'error');
					$button.prop('disabled', false).html(originalText);
				}
			});
		},

		/**
		 * Show configuration preview - New in 1.2.0
		 */
		showConfigPreview: function(filename, content) {
			// Remove existing modal
			$('.config-preview-modal').remove();
			
			// Create modal HTML
			var modalHtml = `
				<div class="config-preview-modal active">
					<div class="config-preview-content">
						<div class="config-preview-header">
							<h3>${filename}</h3>
							<button class="button close-preview">
								<span class="dashicons dashicons-no"></span>
							</button>
						</div>
						<div class="config-preview-body">
							<pre>${this.escapeHtml(content)}</pre>
						</div>
						<div class="config-preview-footer">
							<button class="button copy-config">
								<span class="dashicons dashicons-clipboard"></span>
								Copy to Clipboard
							</button>
							<button class="button button-primary close-preview">Close</button>
						</div>
					</div>
				</div>
			`;
			
			// Append to body
			$('body').append(modalHtml);
			
			// Bind events
			$('.close-preview').on('click', function() {
				$('.config-preview-modal').removeClass('active');
				setTimeout(function() {
					$('.config-preview-modal').remove();
				}, 300);
			});
			
			$('.copy-config').on('click', function() {
				ASPhpCheckup.copyTextToClipboard(content);
				$(this).html('<span class="dashicons dashicons-yes"></span> Copied!');
				setTimeout(function() {
					$('.copy-config').html('<span class="dashicons dashicons-clipboard"></span> Copy to Clipboard');
				}, 2000);
			});
			
			// Close on ESC key
			$(document).on('keyup.configPreview', function(e) {
				if (e.key === 'Escape') {
					$('.close-preview').trigger('click');
					$(document).off('keyup.configPreview');
				}
			});
		},

		/**
		 * Download CSV file
		 */
		downloadCSV: function(content, filename) {
			var blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
			var link = document.createElement('a');
			
			if (navigator.msSaveBlob) {
				// IE 10+
				navigator.msSaveBlob(blob, filename);
			} else {
				var url = URL.createObjectURL(blob);
				link.setAttribute('href', url);
				link.setAttribute('download', filename);
				link.style.display = 'none';
				
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
				
				// Clean up
				setTimeout(function() {
					URL.revokeObjectURL(url);
				}, 100);
			}
		},

		/**
		 * Download file - New in 1.2.0
		 */
		downloadFile: function(content, filename, mimeType) {
			var blob = new Blob([content], { type: mimeType + ';charset=utf-8;' });
			var link = document.createElement('a');
			
			if (navigator.msSaveBlob) {
				// IE 10+
				navigator.msSaveBlob(blob, filename);
			} else {
				var url = URL.createObjectURL(blob);
				link.setAttribute('href', url);
				link.setAttribute('download', filename);
				link.style.display = 'none';
				
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
				
				// Clean up
				setTimeout(function() {
					URL.revokeObjectURL(url);
				}, 100);
			}
		},

		/**
		 * Show admin notice
		 */
		showNotice: function(message, type) {
			var noticeClass = 'as-php-checkup-notice notice-' + (type || 'info');
			var $notice = $('<div class="' + noticeClass + ' is-dismissible"><p>' + message + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
			
			// Remove existing notices
			$('.as-php-checkup-notice').remove();
			
			// Insert after page title
			$('.as-php-checkup-wrap h1').after($notice);
			
			// Auto-hide after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
			
			// Make dismissible
			$notice.on('click', '.notice-dismiss', function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			});
		},

		/**
		 * Start auto-refresh timer
		 */
		startAutoRefresh: function() {
			var self = this;
			var refreshInterval = 5 * 60 * 1000; // 5 minutes
			
			// Only auto-refresh if page is visible
			if (typeof document.hidden !== 'undefined') {
				document.addEventListener('visibilitychange', function() {
					if (document.hidden) {
						self.stopAutoRefresh();
					} else {
						self.startAutoRefresh();
					}
				});
				
				// Set interval if page is visible
				if (!document.hidden) {
					this.autoRefreshTimer = setInterval(function() {
						// Only refresh if no modals or active interactions
						if (!$('.as-php-checkup-wrap').hasClass('loading')) {
							$('#refresh-check').trigger('click');
						}
					}, refreshInterval);
				}
			}
		},

		/**
		 * Stop auto-refresh timer
		 */
		stopAutoRefresh: function() {
			if (this.autoRefreshTimer) {
				clearInterval(this.autoRefreshTimer);
				this.autoRefreshTimer = null;
			}
		},

		/**
		 * Copy to clipboard
		 */
		copyToClipboard: function(e) {
			var $target = $(e.currentTarget);
			var text = $target.text();
			
			// Create temporary textarea
			var $temp = $('<textarea>');
			$('body').append($temp);
			$temp.val(text).select();
			
			// Try to copy
			try {
				var successful = document.execCommand('copy');
				if (successful) {
					// Show feedback
					var originalHtml = $target.html();
					$target.html('✓ Copied!').css('color', '#46b450');
					setTimeout(function() {
						$target.html(originalHtml).css('color', '');
					}, 1000);
				}
			} catch(err) {
				console.error('Failed to copy:', err);
			}
			
			// Remove temporary element
			$temp.remove();
		},

		/**
		 * Copy text to clipboard - New in 1.2.0
		 */
		copyTextToClipboard: function(text) {
			if (navigator.clipboard && navigator.clipboard.writeText) {
				// Modern approach
				navigator.clipboard.writeText(text);
			} else {
				// Fallback
				var $temp = $('<textarea>');
				$('body').append($temp);
				$temp.val(text).select();
				document.execCommand('copy');
				$temp.remove();
			}
		},

		/**
		 * Format bytes to human readable
		 */
		formatBytes: function(bytes, decimals) {
			if (bytes === 0) return '0 Bytes';
			
			var k = 1024;
			var dm = decimals < 0 ? 0 : decimals || 2;
			var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
			var i = Math.floor(Math.log(bytes) / Math.log(k));
			
			return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
		},

		/**
	 	* Escape HTML - Fixed XSS vulnerability
	 	* Uses browser's built-in text node creation for safe escaping
	 	*
	 	* @since 1.2.1
	 	*/
			escapeHtml: function(text) {
		// Create a text node which automatically escapes HTML
			var div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},

		/**
		 * Update requirements display based on plugin analysis
		 */
		updateRequirementsDisplay: function(data) {
			if (data.analyzed && Object.keys(data.analyzed).length > 0) {
				// Update requirement sources in the table
				$('.check-category table tbody tr').each(function() {
					var $row = $(this);
					var settingKey = $row.data('setting');
					
					if (data.analyzed[settingKey]) {
						var sources = data.analyzed[settingKey].sources || [];
						if (sources.length > 0) {
							var $source = $('<p class="requirement-source"></p>');
							$source.html('<em>Required by: ' + sources.join(', ') + '</em>');
							$row.find('.column-setting').append($source);
						}
					}
				});
			}
		},

		/**
		 * Handle REST API interaction
		 */
		fetchAPIData: function(endpoint, callback) {
			$.ajax({
				url: wpApiSettings.root + 'as-php-checkup/v1/' + endpoint,
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
				},
				success: callback,
				error: function(xhr, status, error) {
					console.error('API Error:', error);
				}
			});
		}
	};

	/**
	 * Add spinning animation to dashicons
	 */
	$(document).on('DOMNodeInserted', function(e) {
		if ($(e.target).hasClass('dashicons-update') && $(e.target).parent().prop('disabled')) {
			$(e.target).addClass('spinning');
		}
	});

	/**
	 * Add CSS for spinning animation
	 */
	if (!$('#as-php-checkup-spinning-style').length) {
		$('head').append('<style id="as-php-checkup-spinning-style">' +
			'@keyframes as-spin {' +
				'0% { transform: rotate(0deg); }' +
				'100% { transform: rotate(360deg); }' +
			'}' +
			'.dashicons-update.spinning, .dashicons-admin-plugins.spinning {' +
				'animation: as-spin 2s linear infinite;' +
			'}' +
			'.api-endpoints td:first-child:hover, .cli-commands td:first-child:hover {' +
				'background: #f0f0f0;' +
				'cursor: pointer;' +
				'border-radius: 3px;' +
			'}' +
		'</style>');
	}

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		if ($('.as-php-checkup-wrap').length) {
			ASPhpCheckup.init();
		}
	});

	/**
	 * Export to global scope for debugging
	 */
	window.ASPhpCheckup = ASPhpCheckup;

})(jQuery);