<?php
/* Start the session only once */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
