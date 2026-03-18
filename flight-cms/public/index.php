<?php

// --- Bootstrap The Application ---
// Everything happens in bootstrap.php now
require dirname(__DIR__) . '/app/config/bootstrap.php';

// --- Run The Application ---
// Flight::start() will execute the matched route defined in bootstrap.php (or included route files)
Flight::start();