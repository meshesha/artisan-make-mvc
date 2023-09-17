<?php
 
namespace Meshesha\ArtisanMakeMvc\Tests;


use Meshesha\ArtisanMakeMvc\Tests\MvcTestCase;
use App\Models\TestPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class MvcTest extends MvcTestCase
{
    public function test_creating_all_restful_views_controller_and_adding_route()
    {

        $this->artisan('make:mvc TestPost -A false');

        $this->isViewExists('test_posts/index.blade.php');
        $this->isViewExists('test_posts/show.blade.php');
        $this->isViewExists('test_posts/create.blade.php');
        $this->isViewExists('test_posts/edit.blade.php');
        $this->isControllerExists('TestPostController.php');
        $this->isRoutAdded('TestPostController',app()->version());
    }

    public function test_creating_all_restful_without_controller()
    {

        $this->artisan('make:mvc TestPost -C false -A false');

        $this->isViewExists('test_posts/index.blade.php');
        $this->isViewExists('test_posts/show.blade.php');
        $this->isViewExists('test_posts/create.blade.php');
        $this->isViewExists('test_posts/edit.blade.php');
        $this->isControllerNotExists('TestPostController.php');
        $this->isRoutAdded('TestPostController',app()->version());
    }

    public function test_creating_all_restful_without_controller_and_without_adding_route()
    {

        $this->artisan('make:mvc TestPost -C false -R false -A false');

        $this->isViewExists('test_posts/index.blade.php');
        $this->isViewExists('test_posts/show.blade.php');
        $this->isViewExists('test_posts/create.blade.php');
        $this->isViewExists('test_posts/edit.blade.php');
        $this->isControllerNotExists('TestPostController.php');
        $this->isRoutNotAdded('TestPostController',app()->version());
    }

    public function test_creating_controller_and_add_route_but_without_all_restful_views()
    {

        $this->artisan('make:mvc TestPost -W false -A false');

        $this->isViewNotExists('test_posts/index.blade.php');
        $this->isViewNotExists('test_posts/show.blade.php');
        $this->isViewNotExists('test_posts/create.blade.php');
        $this->isViewNotExists('test_posts/edit.blade.php');
        $this->isControllerExists('TestPostController.php');
        $this->isRoutAdded('TestPostController',app()->version());
    }

    public function test_creating_controller_without_all_restful_views_and_without_adding_route()
    {

        $this->artisan('make:mvc TestPost -W false -R false -A false');

        $this->isViewNotExists('test_posts/index.blade.php');
        $this->isViewNotExists('test_posts/show.blade.php');
        $this->isViewNotExists('test_posts/create.blade.php');
        $this->isViewNotExists('test_posts/edit.blade.php');
        $this->isControllerExists('TestPostController.php');
        $this->isRoutNotAdded('TestPostController',app()->version());
    }
    

    public function test_adding_route_only()
    {

        $this->artisan('make:mvc TestPost -W false -C false -A false');

        $this->isViewNotExists('test_posts/index.blade.php');
        $this->isViewNotExists('test_posts/show.blade.php');
        $this->isViewNotExists('test_posts/create.blade.php');
        $this->isViewNotExists('test_posts/edit.blade.php');
        $this->isControllerNotExists('TestPostController.php');
        $this->isRoutAdded('TestPostController',app()->version());
    }

    public function test_creating_noting()
    {

        $this->artisan('make:mvc TestPost -W false -R false -C false -A false');

        $this->isViewNotExists('test_posts/index.blade.php');
        $this->isViewNotExists('test_posts/show.blade.php');
        $this->isViewNotExists('test_posts/create.blade.php');
        $this->isViewNotExists('test_posts/edit.blade.php');
        $this->isControllerNotExists('TestPostController.php');
        $this->isRoutNotAdded('TestPostController',app()->version());
    }

    public function test_creating_all_restful_views_in_diffrent_folder()
    {

        $this->artisan('make:mvc TestPost -F diffrent_test_folder -R false -C false -A false');

        $this->isViewExists('diffrent_test_folder/index.blade.php');
        $this->isViewExists('diffrent_test_folder/show.blade.php');
        $this->isViewExists('diffrent_test_folder/create.blade.php');
        $this->isViewExists('diffrent_test_folder/edit.blade.php');
        $this->isControllerNotExists('TestPostController.php');
        $this->isRoutNotAdded('TestPostController',app()->version());
    }

    

    public function test_creating_all_restful_views_controller_and_adding_route_and_undo_all_actions()
    {
        $this->artisan('make:mvc TestPost');

        $his_json_arr = $this->getHisFileContent();
        $last_add = array_pop($his_json_arr);
        $title = $last_add->name . " " . $last_add->datetime;
        $excpAsk = "Are you sure you want to delete all files created to '$title' ?";
            
        $this->artisan('mvc:undo')
            ->expectsQuestion($excpAsk, 'yes');

        $this->isViewNotExists('test_posts/index.blade.php');
        $this->isViewNotExists('test_posts/show.blade.php');
        $this->isViewNotExists('test_posts/create.blade.php');
        $this->isViewNotExists('test_posts/edit.blade.php');
        $this->isControllerNotExists('TestPostController.php');
        $this->isRoutNotAdded('TestPostController',app()->version());
    }


    public function test_creating_all_restful_views_controller_and_adding_route_and_check_index_route_is_present()
    {
        $this->artisan('make:mvc TestPost --addtohistory=false');

        $this->refreshApplication();

        $this->isViewExists('test_posts/index.blade.php');
        $this->isViewExists('test_posts/show.blade.php');
        $this->isViewExists('test_posts/create.blade.php');
        $this->isViewExists('test_posts/edit.blade.php');
        $this->isControllerExists('TestPostController.php');
        $this->isRoutAdded('TestPostController',app()->version());
        
        $response = $this->get('test_posts');

        $response->assertStatus(200);


    }


    public function test_creating_all_restful_views_controller_and_adding_route_and_check_create_route_is_present()
    {
        $this->artisan('make:mvc TestPost --addtohistory=false');

        $this->refreshApplication();

        $this->isViewExists('test_posts/index.blade.php');
        $this->isViewExists('test_posts/show.blade.php');
        $this->isViewExists('test_posts/create.blade.php');
        $this->isViewExists('test_posts/edit.blade.php');
        $this->isControllerExists('TestPostController.php');
        $this->isRoutAdded('TestPostController',app()->version());
        
        $response = $this->get('/test_posts/create');

        $response->assertStatus(200);


    }

    public function test_creating_all_restful_views_controller_and_adding_route_and_check_add_data_using_post()
    {
        $this->artisan('make:mvc TestPost --addtohistory=false');

        $this->refreshApplication();

        $this->isViewExists('test_posts/index.blade.php');
        $this->isViewExists('test_posts/show.blade.php');
        $this->isViewExists('test_posts/create.blade.php');
        $this->isViewExists('test_posts/edit.blade.php');
        $this->isControllerExists('TestPostController.php');
        $this->isRoutAdded('TestPostController',app()->version());
        

        $response = $this->post('/test_posts', [
            'title' => 'test title',
            'content' => 'test body'
        ]);

        $response->assertStatus(302); //302 - redirect

    }

    public function test_creating_all_restful_views_controller_and_adding_route_and_check_add_data_using_post_and_ckeck_data_prsent_in_index()
    {
        $this->artisan('make:mvc TestPost --addtohistory=false');

        $this->refreshApplication();

        $this->isViewExists('test_posts/index.blade.php');
        $this->isViewExists('test_posts/show.blade.php');
        $this->isViewExists('test_posts/create.blade.php');
        $this->isViewExists('test_posts/edit.blade.php');
        $this->isControllerExists('TestPostController.php');
        $this->isRoutAdded('TestPostController',app()->version());
        

        $response = $this->post('/test_posts', [
            'title' => 'test title 1234',
            'content' => 'test body data present in index table'
        ]);

        $response->assertStatus(302); //302 - redirect
        $response = $this->get('/test_posts');
        $response->assertStatus(200);
        $response->assertSee("test body data present in index table");

    }

    public function test_creating_all_restful_views_controller_and_adding_route_and_check_updating_data()
    {
        $this->artisan('make:mvc TestPost --addtohistory=false');

        $this->refreshApplication();

        $this->isViewExists('test_posts/index.blade.php');
        $this->isViewExists('test_posts/show.blade.php');
        $this->isViewExists('test_posts/create.blade.php');
        $this->isViewExists('test_posts/edit.blade.php');
        $this->isControllerExists('TestPostController.php');
        $this->isRoutAdded('TestPostController',app()->version());
    

        $last_post = TestPost::latest("id")->first();
        // dd($last_post);
        
        $update_post_test = [
            'title' => 'test title 1234 - updated test',
            'content' => 'test to update post'
        ];
        
        $response = $this->put("/test_posts/{$last_post->id}" , $update_post_test);
        $response->assertStatus(302); //302 - redirect

        $this->assertDatabaseHas('test_posts', $update_post_test);

    }


    public function test_creating_all_restful_views_controller_and_adding_route_and_check_delete_record()
    {
        $this->artisan('make:mvc TestPost --addtohistory=false');

        $this->refreshApplication();

        $this->isViewExists('test_posts/index.blade.php');
        $this->isViewExists('test_posts/show.blade.php');
        $this->isViewExists('test_posts/create.blade.php');
        $this->isViewExists('test_posts/edit.blade.php');
        $this->isControllerExists('TestPostController.php');
        $this->isRoutAdded('TestPostController',app()->version());
        
        $last_post = TestPost::latest("id")->first();


        $response = $this->delete("/test_posts/{$last_post->id}");
        $response->assertStatus(302); //302 - redirect

        $this->assertDatabaseMissing('test_posts', ['id' => $last_post->id]);

    }

    public function test_end_delete_test_table()
    {
        //clear test table
        // DB::table('test_posts')->truncate();
        Schema::dropIfExists('test_posts');
        $this->assertFalse(Schema::hasTable('test_posts'));

    }
}
