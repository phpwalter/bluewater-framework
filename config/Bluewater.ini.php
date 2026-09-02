<?php
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
