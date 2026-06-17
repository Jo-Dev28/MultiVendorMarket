document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-outline-primary, .btn-primary').forEach(function(button) {
        button.addEventListener('mouseover', function() {
            button.style.filter = 'brightness(1.05)';
        });
        button.addEventListener('mouseout', function() {
            button.style.filter = '';
        });
    });
});
