<?php

namespace Meshesha\ArtisanMakeMvc\Tests\Traits;

use Illuminate\Support\Facades\File;

trait FilesAssert
{
    
    public static function isViewExists($name)
    {
        self::assertFileExists(resource_path('views/'.$name));
    }

    public static function isViewNotExists($name)
    {
        self::assertFileDoesNotExist(resource_path('views/'.$name));
    }

    /**
     * Check if view file exists
     * @param  string  $name
     */
    public static function isControllerExists($name)
    {
        self::assertFileExists(app_path('Http/Controllers/'.$name));
    }

    /**
     * @param  string  $name
     */
    public static function isControllerNotExists($name)
    {
        self::assertFileDoesNotExist(app_path('Http/Controllers/'.$name));
    }

    public static function isRoutAdded($controllerName, $laravel_ver)
    {
        $route = self::gteRouteStr($controllerName, $laravel_ver);
        $route_content = File::get("routes/web.php");
        // self::assertStringMatchesFormatFile(base_path("routes/web.php"), $route);
        self::assertStringContainsString($route, $route_content);
        
    }

    public static function isRoutNotAdded($controllerName, $laravel_ver)
    {
        $route = self::gteRouteStr($controllerName, $laravel_ver);
        $route_content = File::get("routes/web.php");
        self::assertStringNotContainsString($route, $route_content);
        
    }

    public static function gteRouteStr($controllerName, $laravel_ver){

        $ctrl_name_class = "App\\Http\\Controllers\\".$controllerName. "::class";

        $route = "Route::resource('test_posts', $ctrl_name_class);";

        $mej_ver = (int) explode(".", $laravel_ver)[0];
        if($mej_ver < 8 && $mej_ver > 0) {

            $route = "Route::resource('test_posts', '$controllerName');";

        }

        return  $route;
    }

    

    public static function isfactoryExists($name)
    {
        self::assertFileExists(base_path('database/factories/'.$name));
    }

    public static function isFactoryNotExists($name)
    {
        self::assertFileDoesNotExist(base_path('database/factories/'.$name));
    }

    public static function isTestExists($name)
    {
        self::assertFileExists(base_path('tests/Feature/'.$name));
    }

    public static function isTestNotExists($name)
    {
        self::assertFileDoesNotExist(base_path('tests/Feature/'.$name));
    }


    public static function isPestTestExists($name)
    {
        self::assertFileExists(base_path('tests/Feature/'.$name));
    }

    public static function isPestTestNotExists($name)
    {
        self::assertFileDoesNotExist(base_path('tests/Feature/'.$name));
    }




    public static function getHisFileContent()
    {
        
        //get history file path
        $his_path = self::getHisFile();
        $his_contents = file_get_contents($his_path);
        return json_decode($his_contents);

    }

    
    public static function getHisFile()
    {
        return   __DIR__ .'/../../src/Generators/history/history.json';
    }


}