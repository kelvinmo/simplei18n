<?php

set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . '/../src/',
    dirname(__FILE__) . '/',
    get_include_path(),
)));

spl_autoload_register(function ($class) {
    $class = ltrim($class, '\\');

    if (strncmp('SimpleI18N\\', $class, 11) === 0) {
        $relative_class = substr($class, 11);
        $file = dirname(__FILE__) . '/../src/' . $relative_class . '.php';
        if (file_exists($file)) {
            include_once $file;
        }
    }
    return;
});

?>