/**
 * Admin Demo Loader (Backwards Compatibility)
 * Loads and delegates to site-demo.js
 */
(function() {
    if (!window.SiteDemo) {
        const script = document.createElement('script');
        script.src = 'js/site-demo.js?v=20260926_all';
        script.async = false;
        document.head.appendChild(script);
    }
})();
