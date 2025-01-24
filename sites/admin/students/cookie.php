<?php
function manageCookieAndRedirect($redirectURL = "/p06_grp2/sites/index.php", $warningMessage = "You have been idle for 5 seconds. Click OK to stay logged in.", $logoutMessage = "You have been idle for 10 seconds. Click OK to log out.") {
    // Start the session if it's not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Inject JavaScript for activity tracking, warning, and logout
    echo "<script>
        let inactivityTime = 0;
        let warningShown = false; // Flag to check if the warning is shown
        let logoutShown = false; // Flag to check if the logout dialog is shown

        // Reset inactivity timer on user activity
        function resetInactivityTime() {
            inactivityTime = 0; // Reset the timer
            warningShown = false; // Allow the warning to show again
            logoutShown = false; // Allow the logout dialog to trigger again
            hideDialog('warningDialog'); // Hide the warning dialog if it's visible
            hideDialog('logoutDialog'); // Hide the logout dialog if it's visible
        }

        // Function to show a dialog
        function showDialog(dialogId, message) {
            const dialog = document.getElementById(dialogId);
            if (dialog) {
                dialog.style.display = 'block'; // Show the dialog
                dialog.querySelector('p').textContent = message; // Set the message
            }
        }

        // Function to hide a dialog
        function hideDialog(dialogId) {
            const dialog = document.getElementById(dialogId);
            if (dialog) {
                dialog.style.display = 'none'; // Hide the dialog
            }
        }

        // Check inactivity every second
        const checkInactivity = setInterval(() => {
            inactivityTime++;

            if (inactivityTime === 5 && !warningShown) {
                // Show the warning dialog at 5 seconds
                warningShown = true;
                showDialog('warningDialog', '$warningMessage');
            }

            if (inactivityTime >= 10 && !logoutShown) {
                // Hide the warning dialog and show the logout dialog at 10 seconds
                hideDialog('warningDialog'); // Ensure the warning dialog is hidden
                logoutShown = true;
                showDialog('logoutDialog', '$logoutMessage');
            }
        }, 1000); // Run every second
    </script>";

    // Inject HTML for the warning and logout dialogs
    echo "
    <!-- Warning Dialog -->
    <div id='warningDialog' style='display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border:2px solid black; z-index:1000; text-align:center; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);'>
        <p>$warningMessage</p>
        <button onclick='resetInactivityTime()'>OK</button>
    </div>

    <!-- Logout Dialog -->
    <div id='logoutDialog' style='display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border:2px solid black; z-index:1000; text-align:center; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);'>
        <p>$logoutMessage</p>
        <button onclick='window.location.href=\"$redirectURL\"'>OK</button>
    </div>";
}
?>
