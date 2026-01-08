<script>
    // Initialize CSRF token globally
    window.csrfToken = '<?php echo CSRF::getToken(); ?>';

    // Automatically add CSRF token to all POST AJAX requests
    (function() {
        'use strict';

        // Function to add CSRF to jQuery AJAX
        function initJQueryCSRF() {
            if (typeof jQuery === 'undefined') {
                return false;
            }

            // Use ajaxPrefilter - most reliable method
            jQuery.ajaxPrefilter(function(options) {
                // Only for POST requests
                if (options.type && options.type.toUpperCase() === 'POST') {
                    // Handle different data types
                    if (!options.data) {
                        options.data = {};
                    }

                    if (typeof options.data === 'string') {
                        // URL-encoded string
                        const separator = options.data.length > 0 ? '&' : '';
                        options.data += separator + 'csrf_token=' + encodeURIComponent(window.csrfToken);
                    } else if (options.data instanceof FormData) {
                        // FormData object
                        options.data.append('csrf_token', window.csrfToken);
                    } else if (typeof options.data === 'object') {
                        // Plain object
                        options.data.csrf_token = window.csrfToken;
                    }
                }
            });

            return true;
        }

        // Function to add CSRF to fetch API
        function initFetchCSRF() {
            const originalFetch = window.fetch;

            window.fetch = function(url, options) {
                options = options || {};

                // Only for POST requests
                if (!options.method || options.method.toUpperCase() === 'POST') {
                    if (options.body instanceof FormData) {
                        options.body.append('csrf_token', window.csrfToken);
                    } else if (typeof options.body === 'string') {
                        const separator = options.body.length > 0 ? '&' : '';
                        options.body += separator + 'csrf_token=' + encodeURIComponent(window.csrfToken);
                    } else if (options.body instanceof URLSearchParams) {
                        options.body.append('csrf_token', window.csrfToken);
                    }
                }

                return originalFetch.apply(this, arguments);
            };
        }

        // Initialize fetch CSRF immediately
        initFetchCSRF();

        // Initialize jQuery CSRF when available
        if (typeof jQuery !== 'undefined') {
            initJQueryCSRF();
        } else {
            // Wait for jQuery to load
            let attempts = 0;
            const interval = setInterval(function() {
                if (initJQueryCSRF() || attempts++ > 100) {
                    clearInterval(interval);
                }
            }, 50);
        }
    })();
</script>