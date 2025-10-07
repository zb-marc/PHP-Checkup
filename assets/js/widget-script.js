/**
 * AS PHP Checkup Dashboard Widget JavaScript
 *
 * @package AS_PHP_Checkup
 * @since 1.1.0
 */

(function($, wp) {
	'use strict';

	/**
	 * Dashboard Widget Handler
	 */
	var ASPhpCheckupWidget = {
		
		/**
		 * Widget element
		 */
		$widget: null,
		
		/**
		 * Auto-refresh timer
		 */
		refreshTimer: null,
		
		/**
		 * Initialize
		 */
		init: function() {
			this.$widget = $('.as-php-checkup-widget');
			
			if (this.$widget.length === 0) {
				return;
			}
			
			this.bindEvents();
			this.initAutoRefresh();
			this.initHealthScoreAnimation();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			var self = this;
			
			// Refresh button
			$('#widget-refresh-check').on('click', function(e) {
				e.preventDefault();
				self.refreshWidget();
			});
			
			// Widget configuration save
			$('#as_php_checkup_dashboard_widget').on('submit', 'form', function(e) {
				// Form is handled by WordPress, but we can add validation if needed
				return true;
			});
			
			// Compact mode toggle
			$(document).on('change', 'input[name="compact_mode"]', function() {
				if ($(this).is(':checked')) {
					self.$widget.addClass('compact-mode');
				} else {
					self.$widget.removeClass('compact-mode');
				}
			});
		},

		/**
		 * Initialize auto-refresh
		 */
		initAutoRefresh: function() {
			var self = this;
			
			// Check if auto-refresh is enabled
			if (!asPhpCheckupWidget.autoRefresh) {
				return;
			}
			
			// Set refresh interval (5 minutes)
			var refreshInterval = 5 * 60 * 1000;
			
			// Clear existing timer
			if (this.refreshTimer) {
				clearInterval(this.refreshTimer);
			}
			
			// Set new timer
			this.refreshTimer = setInterval(function() {
				// Only refresh if page is visible
				if (!document.hidden) {
					self.refreshWidget();
				}
			}, refreshInterval);
			
			// Handle visibility change
			document.addEventListener('visibilitychange', function() {
				if (document.hidden) {
					// Stop refresh when page is hidden
					if (self.refreshTimer) {
						clearInterval(self.refreshTimer);
						self.refreshTimer = null;
					}
				} else {
					// Restart refresh when page becomes visible
					if (asPhpCheckupWidget.autoRefresh && !self.refreshTimer) {
						self.initAutoRefresh();
					}
				}
			});
		},

		/**
		 * Initialize health score animation
		 */
		initHealthScoreAnimation: function() {
			var $scoreElement = this.$widget.find('.health-score-mini');
			if ($scoreElement.length === 0) {
				return;
			}
			
			var score = parseInt($scoreElement.data('score'));
			var $circle = $scoreElement.find('.circle');
			
			// Animate the circle fill
			setTimeout(function() {
				$circle.css('stroke-dasharray', score + ', 100');
			}, 100);
		},

		/**
		 * Refresh widget data
		 */
		refreshWidget: function() {
			var self = this;
			var $button = $('#widget-refresh-check');
			
			// Show loading state
			$button.addClass('loading');
			$button.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-update');
			this.$widget.addClass('loading');
			
			// Remove any existing notifications
			this.$widget.find('.widget-notification').remove();
			
			// Send AJAX request
			wp.ajax.post('as_php_checkup_widget_refresh', {
				nonce: asPhpCheckupWidget.nonce
			}).done(function(response) {
				// Update widget content
				self.updateWidgetContent(response);
				
				// Show success notification
				self.showNotification('Widget refreshed successfully', 'success');
				
				// Update widget status class
				self.updateWidgetStatus(response.overall_status);
				
				// Re-initialize health score animation
				self.initHealthScoreAnimation();
				
			}).fail(function(error) {
				// Show error notification
				self.showNotification(error || asPhpCheckupWidget.error, 'error');
				
			}).always(function() {
				// Remove loading state
				$button.removeClass('loading');
				self.$widget.removeClass('loading');
			});
		},

		/**
		 * Update widget content
		 */
		updateWidgetContent: function(data) {
			// Update health score
			var $scoreElement = this.$widget.find('.health-score-mini');
			$scoreElement.attr('data-score', data.health_score);
			$scoreElement.find('.percentage').text(data.health_score + '%');
			
			// Update status counts
			this.$widget.find('.stat-item.optimal .stat-number').text(data.status_counts.optimal);
			this.$widget.find('.stat-item.acceptable .stat-number').text(data.status_counts.acceptable);
			this.$widget.find('.stat-item.warning .stat-number').text(data.status_counts.warning);
			
			// Update health title
			var healthTitle = 'Good Configuration';
			if (data.overall_status === 'optimal') {
				healthTitle = 'Excellent Configuration';
			} else if (data.overall_status === 'warning') {
				healthTitle = 'Needs Attention';
			}
			this.$widget.find('.health-title').text(healthTitle);
			
			// Update last check time
			this.$widget.find('.health-subtitle').text('Last checked just now');
			
			// Update critical issues if present
			if (data.critical_issues && data.critical_issues.length > 0) {
				this.updateCriticalIssues(data.critical_issues);
			} else {
				// Show success message if no issues
				this.showSuccessState();
			}
		},

		/**
		 * Update critical issues list
		 */
		updateCriticalIssues: function(issues) {
			var $issuesContainer = this.$widget.find('.widget-issues');
			
			// If container doesn't exist, create it
			if ($issuesContainer.length === 0) {
				// Remove success state if present
				this.$widget.find('.widget-success').remove();
				this.$widget.find('.widget-recommendations').remove();
				
				$issuesContainer = $('<div class="widget-issues"></div>');
				$issuesContainer.html('<h4>Critical Issues:</h4><ul class="issue-list"></ul>');
				this.$widget.find('.widget-plugins-info').before($issuesContainer);
			}
			
			var $list = $issuesContainer.find('.issue-list');
			$list.empty();
			
			// Add issues to list
			$.each(issues, function(index, issue) {
				var $li = $('<li></li>');
				$li.html(
					'<span class="issue-label">' + issue.label + ':</span>' +
					'<span class="issue-values">' +
					'<span class="current">' + (issue.current || 'Not set') + '</span>' +
					'<span class="arrow">→</span>' +
					'<span class="needed">' + issue.needed + '</span>' +
					'</span>'
				);
				$list.append($li);
			});
		},

		/**
		 * Show success state
		 */
		showSuccessState: function() {
			// Remove issues/recommendations containers
			this.$widget.find('.widget-issues, .widget-recommendations').remove();
			
			// Add success message if not present
			if (this.$widget.find('.widget-success').length === 0) {
				var $success = $('<div class="widget-success"></div>');
				$success.html(
					'<p class="success-message">' +
					'<span class="dashicons dashicons-yes-alt"></span>' +
					'All PHP settings are optimally configured!' +
					'</p>'
				);
				this.$widget.find('.widget-plugins-info').before($success);
			}
		},

		/**
		 * Update widget status
		 */
		updateWidgetStatus: function(status) {
			// Update widget data attribute
			this.$widget.attr('data-status', status);
			
			// Update widget container class
			var $widgetContainer = $('#as_php_checkup_dashboard_widget');
			$widgetContainer.removeClass('status-optimal status-acceptable status-warning');
			$widgetContainer.addClass('status-' + status);
			
			// Update circle chart class
			var $chart = this.$widget.find('.circular-chart');
			$chart.removeClass('optimal acceptable warning');
			$chart.addClass(status);
		},

		/**
		 * Show notification
		 */
		showNotification: function(message, type) {
			var self = this;
			
			// Remove existing notifications
			this.$widget.find('.widget-notification').remove();
			
			// Create notification element
			var $notification = $('<div class="widget-notification ' + type + '">' + message + '</div>');
			
			// Insert after header
			this.$widget.find('.widget-header').after($notification);
			
			// Auto-remove after 3 seconds
			setTimeout(function() {
				$notification.fadeOut(function() {
					$(this).remove();
				});
			}, 3000);
		},

		/**
		 * Load widget details via AJAX
		 */
		loadDetails: function() {
			var self = this;
			
			wp.ajax.post('as_php_checkup_widget_details', {
				nonce: asPhpCheckupWidget.nonce
			}).done(function(response) {
				// Could open a modal or expand the widget with details
				console.log('Widget details loaded:', response);
			}).fail(function(error) {
				self.showNotification('Failed to load details', 'error');
			});
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		ASPhpCheckupWidget.init();
		
		// Also reinitialize when widgets are saved
		$(document).on('widget-updated widget-added', function() {
			ASPhpCheckupWidget.init();
		});
	});

	/**
	 * Export to global scope for debugging
	 */
	window.ASPhpCheckupWidget = ASPhpCheckupWidget;

})(jQuery, window.wp);