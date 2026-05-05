document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.luma-reviews-plus-message').forEach(function (message) {
        message.setAttribute('role', 'status');
    });
});