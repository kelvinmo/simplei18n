<?php

set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . '/../src/',
    dirname(__FILE__) . '/',
    get_include_path(),
)));

chdir(dirname(__FILE__) . '/');

spl_autoload_register(function ($class) {
    $prefix = 'SimpleI18N\\';
    $base_dir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        include_once $file;
    }
});

?>
