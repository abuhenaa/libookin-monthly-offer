/**
 * LiBookin Monthly Offer - Frontend Scripts
 */

(function($) {
    'use strict';

    /**
     * Vote Handler
     */
    const VoteHandler = {
        init: function() {
            this.bindEvents();
            this.initPopup();
        },

        bindEvents: function() {
            $(document).on('click', '.libookin-vote-btn', this.handleVote.bind(this));
        },

        handleVote: function(e) {
            e.preventDefault();
            
            const $btn = $(e.currentTarget);
            const charityId = $btn.data('charity-id');
            const orderId = $btn.data('order-id');
            const orderKey = $btn.data('order-key');

            if (!charityId || !orderId) {
                this.showMessage('error', libookinMO.strings.error);
                return;
            }

            // Disable all vote buttons
            $('.libookin-vote-btn').prop('disabled', true).text(libookinMO.strings.voting);

            // Submit vote via AJAX
            $.ajax({
                url: libookinMO.ajax_url,
                type: 'POST',
                data: {
                    action: 'libookin_submit_vote',
                    nonce: libookinMO.nonce,
                    charity_id: charityId,
                    order_id: orderId,
                    order_key: orderKey
                },
                success: function(response) {
                    if (response.success) {
                        VoteHandler.showMessage('success', response.data.message);
                        $('#libookin-vote-form').fadeOut();
                    } else {
                        VoteHandler.showMessage('error', response.data.message);
                        $('.libookin-vote-btn').prop('disabled', false).text(libookinMO.strings.vote || 'Vote');
                    }
                },
                error: function() {
                    VoteHandler.showMessage('error', libookinMO.strings.error);
                    $('.libookin-vote-btn').prop('disabled', false).text(libookinMO.strings.vote || 'Vote');
                }
            });
        },

        showMessage: function(type, message) {
            const $messageDiv = $('#libookin-vote-message');
            $messageDiv
                .removeClass('success error')
                .addClass(type)
                .html(message)
                .slideDown();

            // Auto hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $messageDiv.slideUp();
                }, 5000);
            }
        },

        initPopup: function() {
            const $popup = $('#libookin-results-popup');
            
            if ($popup.length) {
                // Show popup after a small delay
                setTimeout(function() {
                    $popup.addClass('active');
                }, 1000);

                // Close popup on close button click
                $popup.find('.libookin-popup-close').on('click', function() {
                    $popup.removeClass('active');
                });

                // Close popup on overlay click
                $popup.on('click', function(e) {
                    if ($(e.target).is('.libookin-popup-overlay')) {
                        $popup.removeClass('active');
                    }
                });

                // Close popup on ESC key
                $(document).on('keyup', function(e) {
                    if (e.key === 'Escape' && $popup.hasClass('active')) {
                        $popup.removeClass('active');
                    }
                });
            }
        }
    };

    /**
     * Vote Counter Animations
     */
    const VoteCounter = {
        init: function() {
            this.animateProgress();
        },

        animateProgress: function() {
            $('.vote-counter-item').each(function() {
                const $item = $(this);
                const $progressFill = $item.find('.progress-fill');
                
                if ($progressFill.length) {
                    const width = $progressFill.css('width');
                    $progressFill.css('width', '0%');
                    
                    setTimeout(function() {
                        $progressFill.css('width', width);
                    }, 300);
                }
            });
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        VoteHandler.init();
        VoteCounter.init();
    });

})(jQuery);
