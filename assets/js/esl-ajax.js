jQuery(document).ready(function($) {
    $('#esl-event-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'esl_submit_event');
        formData.append('security', esl_ajax.nonce);
        $.ajax({
            url: esl_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
});