<?php
 
namespace Meshesha\ArtisanMakeMvc\Tests;

use Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Meshesha\ArtisanMakeMvc\Tests\Traits\FilesAssert;


class MvcTestCase extends TestCase
{
    use FilesAssert;

    protected $migrationFile;
    
    
    protected static $initialized = FALSE;

    public function setUp(): void
    {
        parent::setUp();

        if(!File::exists(app_path('Models/TestPost.php')) ){
            File::put(app_path('Models/TestPost.php'), '<?php namespace App\Models; use Illuminate\Database\Eloquent\Model; class TestPost extends Model { protected $fillable = [\'title\', \'content\']; }');
            
            if (!self::$initialized) {
                    $this->createTeatPostMigration();
                self::$initialized = TRUE;
                
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
        File::delete(app_path('Models/TestPost.php'));

        $this->removeTestRoute();


        File::delete($this->migrationFile);
    }

    private function createTeatPostMigration()
    {
        

        try{
            Artisan::call('make:migration', [
                'name' => 'create_test_posts_table',
                '--create' => 'test_posts',
            ]);
            // $artisanOutput = Artisan::output();
        } catch (\Exception | Error $e) {
            // echo "create migration error: " . $e->getMessage() . "\n";
        }  catch(\Throwable $e) {
            // echo "create migration error: " . $e->getMessage() . "\n";
        }

        try{
            $test_posts_table = glob(database_path('migrations/*_create_test_posts_table.php'));
          
            $migrationFile = $test_posts_table[0];
            $this->migrationFile = $migrationFile;
            
            $migrationContents = file_get_contents($migrationFile);
            if(strpos($migrationContents,"table->string('title')") === false){
                $migrationContents = str_replace(
                    '$table->id();',
                    '$table->id();' . PHP_EOL . '$table->string(\'title\');' . PHP_EOL . '$table->text(\'content\');',
                    $migrationContents
                );

                file_put_contents($migrationFile, $migrationContents);
            }
        } catch(\Throwable $e) {
            // echo "adding new fields in migration error: " . $e->getMessage() . "\n";
        }
        try{
            Artisan::call('migrate');
            $artisanOutput = Artisan::output();

            if (in_array("Error", str_split($artisanOutput, 5))) {
                throw new Exception($artisanOutput);
            }
        } catch (Exception | Error $e) {
            // echo "migrate action error: " . $e->getMessage() . "\n";
        } catch(\Throwable $e) {
            // echo "migrate action error: " . $e->getMessage() . "\n";
        }
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
