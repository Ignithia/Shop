<script>
    window.csrfToken = '<?php echo CSRF::getToken(); ?>';

    // Add CSRF token to all AJAX requests
    (function() {
        // Store original fetch
        const originalFetch = window.fetch;

        window.fetch = function(url, options = {}) {
            // Add CSRF token to POST requests
            if (!options.method || options.method.toUpperCase() === 'POST') {
                if (options.body instanceof FormData) {
                    options.body.append('csrf_token', window.csrfToken);
                } else if (options.body instanceof URLSearchParams) {
                    options.body.append('csrf_token', window.csrfToken);
                } else if (typeof options.body === 'string') {
                    const separator = options.body.length > 0 ? '&' : '';
                    options.body += separator + 'csrf_token=' + encodeURIComponent(window.csrfToken);
                } else if (options.headers && options.headers['Content-Type'] === 'application/json') {
                    // For JSON requests, add token to body
                    try {
                        const body = JSON.parse(options.body || '{}');
                        body.csrf_token = window.csrfToken;
                        options.body = JSON.stringify(body);
                    } catch (e) {
                        // If parsing fails, skip
                    }
                }
            }

            return originalFetch.apply(this, arguments);
        };

        // Add CSRF token to jQuery AJAX if jQuery is loaded
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ajaxSend(function(event, jqxhr, settings) {
                if (settings.type && settings.type.toUpperCase() === 'POST') {
                    if (typeof settings.data === 'string') {
                        const separator = settings.data.length > 0 ? '&' : '';
                        settings.data += separator + 'csrf_token=' + encodeURIComponent(window.csrfToken);
                    } else if (settings.data instanceof FormData) {
                        settings.data.append('csrf_token', window.csrfToken);
                    } else if (typeof settings.data === 'object') {
                        settings.data.csrf_token = window.csrfToken;
                    }
                }
            });
        }
    })();
</script>