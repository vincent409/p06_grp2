<?php
    $alphanumeric_pattern = "/^[a-zA-Z0-9\s]+$/"; // Allows letters and single spaces only between words
    $alphabet_pattern = "/^[a-zA-Z]+(?: [a-zA-Z]+)*$/"; // Allows letters and single spaces only between words
    $model_number_pattern = "/^[a-zA-Z0-9-_]+$/"; // Alphanumeric, dashes, and underscores
    $phonePattern = "/^[0-9]{8}$/"; // Phone number (8)
    function validateDate($date) {
        // Check if the date is in the format yyyy-mm-dd
        if (preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/", $date)) {
            // Extract the year, month, and day from the date
            list($year, $month, $day) = explode('-', $date);
    
            // Use PHP's checkdate function to validate the date
            if (checkdate($month, $day, $year)) {
                return true;  // Valid date
            } else {
                return "Invalid date: the date doesn't exist.";
            }
        } else {
            return "Invalid format: Date must be in yyyy-mm-dd format.";
        }
    }
    function validateEmail($email) {
        // Check if the email is in a valid format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;  // Valid email
        } else {
            return "Invalid email format.";  // Invalid email format
        }
    }    
?>