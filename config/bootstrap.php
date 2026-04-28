<?php

if (!defined('XSL_SECPREF_NONE')) {
    define('XSL_SECPREF_NONE', 0);
}

if (!defined('XSL_SECPREF_READ_FILE')) {
    define('XSL_SECPREF_READ_FILE', 2);
}

if (!defined('XSL_SECPREF_WRITE_FILE')) {
    define('XSL_SECPREF_WRITE_FILE', 4);
}

if (!defined('XSL_SECPREF_CREATE_DIRECTORY')) {
    define('XSL_SECPREF_CREATE_DIRECTORY', 8);
}

if (!defined('XSL_SECPREF_READ_NETWORK')) {
    define('XSL_SECPREF_READ_NETWORK', 16);
}

if (!defined('XSL_SECPREF_WRITE_NETWORK')) {
    define('XSL_SECPREF_WRITE_NETWORK', 32);
}

if (!defined('XSL_SECPREF_DEFAULT')) {
    define('XSL_SECPREF_DEFAULT', XSL_SECPREF_NONE);
}