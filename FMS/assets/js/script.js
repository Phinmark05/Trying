// FMS Custom JavaScript

// Keep the sidebar toggle functional even when the AdminLTE CDN script is unavailable.
document.addEventListener('DOMContentLoaded', function() {
    var sidebarToggle = document.getElementById('sidebarToggle');
    if (!sidebarToggle) {
        return;
    }

    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        var isCollapsed = document.body.classList.toggle('sidebar-collapse');
        sidebarToggle.setAttribute('aria-expanded', String(!isCollapsed));
    });
});

// Toggle the notification menu without depending on the Bootstrap dropdown plugin.
$(document).on('click', '.main-header .notification-toggle, .main-header .dropdown-toggle', function(e) {
    e.preventDefault();
    e.stopPropagation();

    var menu = $(this).closest('.nav-item.dropdown');
    var isOpen = menu.toggleClass('dropdown-open').hasClass('dropdown-open');
    $(this).attr('aria-expanded', String(isOpen));
});

$(document).on('click', function(e) {
    if (!$(e.target).closest('.main-header .nav-item.dropdown').length) {
        $('.main-header .nav-item.dropdown').removeClass('dropdown-open')
            .find('[aria-haspopup="true"]').attr('aria-expanded', 'false');
    }
});

// Auto-dismiss flash alerts after 5 seconds
$(document).ready(function() {
    setTimeout(function() {
        $('.alert-dismissible').fadeOut('slow');
    }, 5000);

    // Confirm before any delete/deactivate action
    $('.btn-outline-danger, .btn-outline-secondary').on('click', function(e) {
        if ($(this).text().trim().toLowerCase().includes('deactivate') ||
            $(this).text().trim().toLowerCase().includes('suspend')) {
            return confirm('Are you sure you want to perform this action?');
        }
    });

});
