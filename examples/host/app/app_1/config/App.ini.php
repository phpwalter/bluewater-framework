<?php

/**
 * @file App.ini.php
 * @path examples/host/app/app_1/config/App.ini.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines guarded app ini example-application settings consumed as data by the configuration parser.
 */

declare(strict_types=1);
exit;
?>
[constants]
BW_ENV = "development"

[application]
APP_NAMESPACE = "Apps\\App1"

[features]
AUTH = true
DATABASE = true
OPENAPI = true
VALIDATION = true
SESSIONS = false
LOGGING = true

[auth]
API_KEY = "demo-key"
JWT_SECRET = "change-this-in-production"
JWT_ISSUER = "bluewater-example"
JWT_AUDIENCE = "app_1"
