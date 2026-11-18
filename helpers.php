<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirects to a given location with a session message.
 *
 * @param string $location The URL to redirect to.
 * @param string $message The message to store in the session.
 * @param string $type The type of message ('message' for success, 'error' for errors).
 */
function redirect_with_message($location, $message, $type = 'message') {
    $_SESSION[$type] = $message;
    header("Location: " . $location);
    exit();
}
