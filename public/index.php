<?php

/**
 * brighteen - bright student, active teen
 * Public Directory Front Controller
 */

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../routes/web.php';

// Dispatch Router
\App\Http\Router::dispatch();
