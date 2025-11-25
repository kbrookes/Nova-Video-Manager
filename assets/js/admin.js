/**
 * Nova Video Manager - Admin Scripts
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {

        /**
         * Add "Add New Member" button to relationship field if it doesn't exist
         */
        function addNewMemberButton() {
            var $relationshipField = $('.acf-field[data-name="nvm_featured_members"]');

            if ($relationshipField.length && !$relationshipField.find('.add-new-member-btn').length) {
                var $filters = $relationshipField.find('.filters');

                if ($filters.length) {
                    var $addButton = $('<a href="#" class="button add-new-member-btn" style="margin-left: 10px;">+ Add New Member</a>');

                    $addButton.on('click', function(e) {
                        e.preventDefault();

                        // Open new member in new tab
                        var newMemberUrl = nvmAdmin.newMemberUrl;
                        window.open(newMemberUrl, '_blank');

                        // Show message to user
                        alert('A new tab has been opened to create a member. After saving the member, come back to this page and refresh to select them.');
                    });

                    $filters.append($addButton);
                }
            }
        }

        // Add button on page load
        addNewMemberButton();

        // Re-add button when ACF refreshes the field
        if (typeof acf !== 'undefined') {
            acf.addAction('ready', addNewMemberButton);
        }

        /**
         * Handle manual sync button click
         */
        $('#nvm-manual-sync-btn').on('click', function() {
            var $button = $(this);
            var $status = $('#nvm-sync-status');
            
            // Disable button
            $button.prop('disabled', true);
            
            // Show loading status
            $status.removeClass('success error').addClass('loading');
            $status.text('Syncing videos from YouTube...');
            
            // Make AJAX request
            $.ajax({
                url: nvmAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'nvm_manual_sync',
                    nonce: nvmAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.removeClass('loading error').addClass('success');
                        $status.text(response.data.message);
                        
                        // Reload page after 2 seconds to update stats
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $status.removeClass('loading success').addClass('error');
                        $status.text(response.data.message || 'An error occurred during sync.');
                    }
                },
                error: function(xhr, status, error) {
                    $status.removeClass('loading success').addClass('error');
                    $status.text('An error occurred: ' + error);
                },
                complete: function() {
                    // Re-enable button
                    $button.prop('disabled', false);
                }
            });
        });
        
    });
    
})(jQuery);

