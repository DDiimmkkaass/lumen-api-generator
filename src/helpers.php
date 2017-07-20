<?php

if (! function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     *
     * @param  string  $path
     * @return string
     */
    function app_path($path = '')
    {
        return app()->basePath($path);
    }
}

if (! function_exists('get_namespace')) {
    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    function get_namespace()
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        
        foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            foreach ((array) $path as $pathChoice) {
                if (realpath(app_path()) == realpath(base_path().'/'.$pathChoice)) {
                    return $namespace;
                }
            }
        }
        
        throw new RuntimeException('Unable to detect application namespace.');
    }
}