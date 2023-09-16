<?php
 
namespace Meshesha\ArtisanMakeMvc\Tests;

use Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Meshesha\ArtisanMakeMvc\Tests\Traits\FilesAssert;


class MvcTestCase extends TestCase
{
    use FilesAssert;
    use RefreshDatabase;

    protected $migrationFile;
    // protected $dbmigrationFile;

    protected static $initialized = FALSE;
    


    public function setUp(): void
    {
        parent::setUp();
        if(!File::exists(app_path('Models/TestPost.php'))){
            // echo "Models/TestPost.php not exists\n";
            $this->fail('Test failed because the condition did not match.');
            return;
        }

        if(File::exists(app_path('Models/TestPost.php') )){
            // echo "Models/TestPost.php not exists\n";
            $app_model = app("App\\Models\\TestPost");

            $db_table_name = $app_model->getTable();

            $db_primary_key = $app_model->getKeyName();

            $columns_arr =  Schema::getColumnListing($db_table_name);
            // print_r($columns_arr);

            if(!in_array("title", $columns_arr)){   
                $this->fail('Test failed because the condition did not match (table not contain columns).');
                return;
            }
        }
        
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // Clean up view files after each test method
        File::deleteDirectory(resource_path('views/test_posts'));
        File::deleteDirectory(resource_path('views/diffrent_test_folder'));
        File::delete(app_path('Http/Controllers/TestPostController.php'));

        $this->removeTestRoute();


    }


    public function removeTestRoute()
    {
        $route = $this->getRouteStr();
        $route_content = File::get("routes/web.php");
        $route_content = str_replace($route, "", $route_content);
        File::put("routes/web.php", $route_content);
    }

    
    public function getRouteStr(){

        $laravel_ver = app()->version();

        $ctrl_name_class = "";

        $route = "\n\n// test_posts:\nRoute::resource('test_posts', App\\Http\\Controllers\\TestPostController::class);";

        $mej_ver = (int) explode(".", $laravel_ver)[0];

        if($mej_ver < 8) {

            $route = "\n\n// test_posts:\nRoute::resource('test_posts', 'TestPostController');";

        }

        return  $route;
    }
}
