// main.js
// All JavaScript for ArtStore.

// $(document).ready means: wait until the HTML page is fully loaded, then run this code.
$(document).ready(function () {
    // When the hamburger button is clicked,
    // add or remove the "open" class on the nav links.
    // The CSS shows the nav when it has the class "open".
    $('#burgerBtn').on('click', function () {
        // toggleClass adds the class if missing, removes it if present
        $('#navLinks').toggleClass('open');
    });

    // Close the nav if user clicks somewhere else on the page
    $(document).on('click', function (e) {
        // If the click was NOT on the burger button or nav links
        if (!$(e.target).closest('#navLinks, #burgerBtn').length) {
            $('#navLinks').removeClass('open');
        }
    });


   
    // When Login or Register tab is clicked,
    // show that form and hide the other one.
    $('.auth-tab').on('click', function () {
        var target = $(this).data('target'); // e.g. "loginForm"

        // Remove "active" from all tabs, add it to the clicked one
        $('.auth-tab').removeClass('active');
        $(this).addClass('active');

        // Hide both forms, then fade in the target one
        // fadeOut and fadeIn are jQuery animation methods
        $('.auth-form').fadeOut(200);
        $('#' + target).fadeIn(300);
    });


    // Alerts (success/error messages) disappear automatically
    if ($('.alert').length > 0) {
        // .delay() waits, then .slideUp() animates hiding
        $('.alert').delay(4000).slideUp(500);
    }

});


// These functions are outside $(document).ready
// because they are called directly from HTML onclick=""

// Shows the write-note form and fills in the user's info
function showNoteForm(userId, userName) {
    document.getElementById('noteUserId').value       = userId;
    document.getElementById('noteUserName').innerText = userName;
    document.getElementById('noteText').value         = '';

    // Use jQuery to slide the panel into view
    $('#notePanel').slideDown(300);

    // Scroll down to the note form
    $('html, body').animate({
        scrollTop: $('#notePanel').offset().top - 80
    }, 400);
}

// Hides the note form
function hideNoteForm() {
    $('#notePanel').slideUp(300);
}