<?php
declare(strict_types=1);

define('PROJECT_ROOT', dirname(__DIR__));
define('STORAGE_DIR', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'storage');
define('DATA_DIR', STORAGE_DIR . DIRECTORY_SEPARATOR . 'data');
define('BACKUP_DIR', STORAGE_DIR . DIRECTORY_SEPARATOR . 'backups');
define('APP_TIMEZONE', 'America/Sao_Paulo');
date_default_timezone_set(APP_TIMEZONE);

