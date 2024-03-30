jQuery(document).ready(function($) {
    $('.delete-btn').click(function(e) {
        e.preventDefault(); // Prevent the default form submission behavior
        
        var row_id = $(this).data('id');
        var data = {
            'action': 'custom_delete_row',
            'row_id': row_id
        };
        // Send AJAX request
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response === 'success') {
                    // Remove the row from the table
                    $('[data-id="' + row_id + '"]').closest('tr').remove();
                } else {
                    alert('Failed to delete row. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    });
});
