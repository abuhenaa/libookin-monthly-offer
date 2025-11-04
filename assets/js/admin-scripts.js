/**
 * LiBookin Monthly Offer - Admin Scripts
 */

(function($) {
    'use strict';

    /**
     * Admin Dashboard Handler
     */
    const AdminDashboard = {
        init: function() {
            this.bindEvents();
            this.initCharts();
        },

        bindEvents: function() {
            // Add any admin-specific event handlers here
            $('.libookin-refresh-stats').on('click', this.refreshStats.bind(this));
        },

        refreshStats: function(e) {
            e.preventDefault();
            
            const $btn = $(e.currentTarget);
            $btn.prop('disabled', true).append('<span class="libookin-loading"></span>');

            // Reload the page to refresh stats
            setTimeout(function() {
                location.reload();
            }, 500);
        },

        initCharts: function() {
            // Initialize any charts or graphs
            // This is a placeholder for future chart implementations
        }
    };

    /**
     * Vote Results Handler
     */
    const VoteResults = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Month selector auto-submit
            $('#month-select').on('change', function() {
                $(this).closest('form').submit();
            });

            // Confirm CSV export
            $('.libookin-export-btn').on('click', function(e) {
                if (!confirm('Are you sure you want to export the vote results?')) {
                    e.preventDefault();
                }
            });
        }
    };

    /**
     * Charity Management
     */
    const CharityManagement = {
        init: function() {
            this.enhanceFeaturedImage();
        },

        enhanceFeaturedImage: function() {
            // Add custom text for featured image
            if ($('#postimagediv').length) {
                $('#postimagediv h2').text('Charity Logo');
                $('#set-post-thumbnail').text('Set charity logo');
                $('#remove-post-thumbnail').text('Remove charity logo');
            }
        }
    };

    /**
     * Admin Notices
     */
    const AdminNotices = {
        init: function() {
            this.bindDismiss();
        },

        bindDismiss: function() {
            $(document).on('click', '.libookin-notice .notice-dismiss', function() {
                const noticeId = $(this).closest('.libookin-notice').data('notice-id');
                
                if (noticeId) {
                    // Save dismissed state via AJAX
                    $.post(ajaxurl, {
                        action: 'libookin_dismiss_notice',
                        notice_id: noticeId
                    });
                }
            });
        }
    };

    /**
     * Table Enhancements
     */
    const TableEnhancements = {
        init: function() {
            this.addRowActions();
            this.highlightWinner();
        },

        addRowActions: function() {
            // Add hover effects to table rows
            $('.wp-list-table tbody tr').hover(
                function() {
                    $(this).addClass('hover');
                },
                function() {
                    $(this).removeClass('hover');
                }
            );
        },

        highlightWinner: function() {
            // Highlight the winning charity in results table
            $('.libookin-current-results tbody tr:first-child').css({
                'background-color': '#f0f9ff',
                'font-weight': '600'
            });
        }
    };

    /**
     * Form Validation
     */
    const FormValidation = {
        init: function() {
            this.validateCharityForm();
        },

        validateCharityForm: function() {
            $('#post').on('submit', function(e) {
                if ($('body').hasClass('post-type-libookin_charity')) {
                    const title = $('#title').val().trim();
                    
                    if (title === '') {
                        alert('Please enter a charity name.');
                        $('#title').focus();
                        e.preventDefault();
                        return false;
                    }
                }
            });
        }
    };

    /**
     * Utility Functions
     */
    const Utils = {
        showLoading: function(element) {
            $(element).append('<span class="libookin-loading"></span>');
        },

        hideLoading: function(element) {
            $(element).find('.libookin-loading').remove();
        },

        showNotice: function(message, type) {
            type = type || 'success';
            
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible libookin-notice"><p>' + message + '</p></div>');
            $('.wrap > h1').after($notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        AdminDashboard.init();
        VoteResults.init();
        CharityManagement.init();
        AdminNotices.init();
        TableEnhancements.init();
        FormValidation.init();
    });

    /**
     * Make Utils available globally
     */
    window.LibookinMO = window.LibookinMO || {};
    window.LibookinMO.Utils = Utils;

})(jQuery);
