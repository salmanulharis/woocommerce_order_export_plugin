(function($) {
    'use strict';

    $(document).ready(function() {
        $('#wc-order-export-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var data = {};

            $.each($form.serializeArray(), function(_, item) {
                if (data[item.name]) {
                    if (!Array.isArray(data[item.name])) {
                        data[item.name] = [data[item.name]];
                    }
                    data[item.name].push(item.value);
                } else {
                    data[item.name] = item.value;
                }
            });

            $.ajax({
                url: wcOrderExport.rest_url + 'wc-order-export/v1/export',
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wcOrderExport.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        var decodedData = atob(response.data);  // atob() decodes the base64 string
            
                        // Convert the decoded data to a binary array
                        var byteNumbers = new Array(decodedData.length);
                        for (var i = 0; i < decodedData.length; i++) {
                            byteNumbers[i] = decodedData.charCodeAt(i);
                        }
                        var byteArray = new Uint8Array(byteNumbers);

                        var blob = new Blob([byteArray], {type: response.content_type});
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = response.filename;
                        link.click();
                    } else {
                        alert(response.message || 'An error occurred during export.');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred: ' + errorThrown);
                }
            });
        });
    });

})(jQuery);