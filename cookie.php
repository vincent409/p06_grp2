<?php
function manageCookieAndRedirect($redirectURL = "/p06_grp2/index.php", $warningMessage = "You have been idle for 50 minutes. Click OK to stay logged in.", $logoutMessage = "You have been idle for 1 hour. Click OK to log out.") {
    // Start the session if it's not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Inject JavaScript for activity tracking, warning, and logout
    echo "<script>
        let inactivityTime = 0;
        let warningShown = false; // Flag to check if the warning is shown
        let logoutShown = false; // Flag to check if the logout dialog is shown

        // Reset inactivity timer only if no warning or logout dialog is displayed
        function resetInactivityTime() {
            if (!warningShown && !logoutShown) {
                inactivityTime = 0; // Reset the timer
            }
        }

        // Event listeners for resetting the timer on user activity
        document.addEventListener('mousemove', resetInactivityTime);
        document.addEventListener('keydown', resetInactivityTime);

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

            if (inactivityTime === 3000 && !warningShown) {
                // Show the warning dialog at 50 minutes
                warningShown = true;
                showDialog('warningDialog', '$warningMessage');
            }

            if (inactivityTime >= 3600 && !logoutShown) {
                // Hide the warning dialog and show the logout dialog at 1 hour
                hideDialog('warningDialog'); // Ensure warning dialog is hidden
                logoutShown = true;
                showDialog('logoutDialog', '$logoutMessage');

                // Set a cookie to indicate that logout has occurred
                document.cookie = 'logout_occurred=true; path=/';
            }
        }, 1000); // Run every second

        // Check if the logout cookie exists and redirect immediately if present
        window.addEventListener('DOMContentLoaded', () => {
            if (document.cookie.includes('logout_occurred=true')) {
                // Clear the cookie and redirect
                document.cookie = 'logout_occurred=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC;';
                window.location.href = '$redirectURL';
            }
        });
    </script>";

    // Inject HTML for the warning and logout dialogs
    echo "
    <!-- Warning Dialog -->
    <div id='warningDialog' style='display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border:2px solid black; z-index:1000; text-align:center; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);'>
        <p>$warningMessage</p>
        <button onclick='resetWarning()'>OK</button>
    </div>

    <!-- Logout Dialog -->
    <div id='logoutDialog' style='display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border:2px solid black; z-index:1000; text-align:center; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);'>
        <p>$logoutMessage</p>
        <button onclick='window.location.href=\"$redirectURL\"'>OK</button>
    </div>

    <script>
        // Function to reset the timer when the user interacts with the warning dialog
        function resetWarning() {
            inactivityTime = 0; // Reset the timer
            warningShown = false; // Allow the warning to show again after the next 50 minutes
            hideDialog('warningDialog'); // Hide the warning dialog
        }
    </script>";
}
?>
