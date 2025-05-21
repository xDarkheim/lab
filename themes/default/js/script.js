document.addEventListener('DOMContentLoaded', function() {
    const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
    const mainNavigation = document.querySelector('.main-navigation');
    const body = document.body;

    if (mobileNavToggle && mainNavigation) {
        mobileNavToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true' || false;
            this.setAttribute('aria-expanded', !isExpanded);
            mainNavigation.classList.toggle('active');
            body.classList.toggle('mobile-nav-open'); // Add/remove class on body
        });
    }
});