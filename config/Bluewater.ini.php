<?php

/**
 * @file Bluewater.ini.php
 * @path config/Bluewater.ini.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines guarded bluewater ini framework defaults consumed as data by the configuration parser.
 */

declare(strict_types=1);
exit;
?>
; Bluewater core defaults. Override application-owned settings in /app/<app>/config.

[constants]
BW_VER = "8.0ai"
ARRAY_DELIM = ","
ALLOWED_URI_CHARS = "a-z 0-9~%.:_\-"
BW_ENV = "production"

[features]
AUTH = true
DATABASE = true
OPENAPI = true
VALIDATION = true
SESSIONS = false
LOGGING = true

[routing]
CACHE_MODE = "automatic"

[serialization]
DEFAULT = "application/json"
