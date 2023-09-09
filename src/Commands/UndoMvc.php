<?php

namespace Meshesha\ArtisanMakeMvc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UndoMvc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mvc:undo {--show} {--delselect}';


    /**
     * {@inheritdoc}
     */
    protected $description = "Delete last controller, view and route added using 'make:mvc'";

    /**
     * Execute the command.
     */
    public function handle()
    {
        $laravel_ver = app()->version();


        //get history file path
        $his_path = $this->getHisFile();
        $his_contents = file_get_contents($his_path);

        // $this->line('his_contents : \n' . $his_contents . "\n");

        $his_json = json_decode($his_contents);


        // print_r($his_json);

        if($his_json == null || empty($his_json)) {
            $this->error("history file is empty!");
            return;
        }

        $show = $this->option('show');
        if($show) {
            // $his_ary = array_map(function($itm){
            //     return $itm->name;
            // },$his_json);
            foreach($his_json as $key => $his_item) {
                $this->info(($key + 1) . ' - ' . $his_item->name . " " . $his_item->datetime);
            }
            return;
        }

        $delselect = $this->option('delselect');
        if($delselect) {
            $his_ary = array_map(function ($his_item) {
                return $his_item->name . " " . $his_item->datetime;
            }, $his_json);

            $selected_to_del = $this->choice('Select which one to delete: ', $his_ary, head($his_ary));
            $to_del = explode(" ", $selected_to_del)[0];
            // $to_del_obj = array_filter($his_json, function ($his_item) use ($to_del) {
            //     return $his_item->name == $to_del;
            // });
            $to_del_obj = null;
            foreach($his_json as $his_item) {
                if($his_item->name == $to_del) {
                    $to_del_obj = $his_item;
                    break;
                }
            }
            // $this->line('to_del_obj: ');
            // print_r($to_del_obj);
            // return;
            if($this->deleteHis($to_del_obj)) {
                //$his_path
                // remove  $to_del from his fle - TODO
                $updated_his_obj = array_filter($his_json, function ($his_item) use ($to_del) {
                    return $his_item->name != $to_del;
                });

                $updated_his_str = json_encode(array_values($updated_his_obj));

                $save_his_stt = $this->saveHisFile($his_path, $updated_his_str);

            }

            return;
        }



        $last_add = array_pop($his_json);

        if($this->deleteHis($last_add)) {
            //$his_path
            $updated_his_str = json_encode(array_values($his_json));
            //save his fle

            $save_his_stt = $this->saveHisFile($his_path, $updated_his_str);


        }

        // $this->line("to delete :\n");

        // print_r($last_add);

        // $last_add = array_pop($his_json);

        // $this->line("to save :\n");

        // print_r($his_json);

    }

    private function getHisFile()
    {
        return   __DIR__ .'/../Generators/history/history.json';
    }

    private function saveHisFile($file_path, $file_content)
    {
        return file_put_contents($file_path, $file_content);

    }

    private function deleteHis($his_obj)
    {
        if($his_obj == null || $his_obj == "") {
            return false;
        }

        $title = $his_obj->name . " " . $his_obj->datetime;

        if ($this->confirm("Are you sure you want to delete all files created to '$title' ?")) {

            $is_err = false;

            if(isset($his_obj->controller)) {
                // delete controller file
                $ctrl_path = $his_obj->controller; //string

                if(File::exists($ctrl_path)) {
                    try {
                        File::delete($ctrl_path);
                        $this->info("$ctrl_path - deleted successfully.");
                    } catch(\Throwable $e) {
                        $this->error("Error delete file ($ctrl_path): ". $e->getMessage());
                        $is_err = true;
                    } catch (\RunTimeException $e) {
                        $this->error("Error delete file ($ctrl_path): ". $e->getMessage());
                        $is_err = true;
                    } catch(\Exception $e) {
                        $this->error("Error delete file ($ctrl_path): ". $e->getMessage());
                        $is_err = true;
                    }
                } else {
                    $this->error("The file ('$ctrl_path') does not exists!");
                }

                // $this->line("ctrl_path :$ctrl_path");

            }
            if(isset($his_obj->views)) {
                // delete views file
                $views_path_arr = $his_obj->views; //array
                foreach($views_path_arr as $views_path) {
                    if(File::exists($views_path)) {
                        try {
                            File::delete($views_path);
                            $this->info("$views_path - deleted successfully.");

                            $view_folder = substr($views_path, 0, strrpos($views_path, "/"));


                            if (strpos($view_folder, strtolower($his_obj->name)) !== false && File::exists($view_folder)) {

                                $files = File::allFiles($view_folder);

                                if (empty($files)) {
                                    if(File::deleteDirectory($view_folder)) {
                                        $this->info("The folder '$view_folder' deleted successfully.");
                                    }
                                }

                            }

                        } catch(\Throwable $e) {
                            $this->error("Error delete file ($views_path): ". $e->getMessage());
                            $is_err = true;
                        } catch (\RunTimeException $e) {
                            $this->error("Error delete file ($views_path): ". $e->getMessage());
                            $is_err = true;
                        } catch(\Exception $e) {
                            $this->error("Error delete file ($views_path): ". $e->getMessage());
                            $is_err = true;
                        }

                    } else {
                        $this->error("The file ('$views_path') does not exists!");
                    }
                }
                // $this->line("views_path_arr :" . implode("\n", $views_path_arr));

            }
            if(isset($his_obj->route)) {
                // remove route from file
                $route_str = $his_obj->route; //string
                // $this->line("route_str :$route_str");

                $route_content = File::get("routes/web.php");

                if(strpos($route_content, $route_str) !== false || strpos($route_content, $route_str) !== false) {

                    $route_content = str_replace($route_str, "", $route_content);
                    // if(File::put("routes/web.php", $route_content)) {
                    //     $this->info("Route removed successfully.");
                    // }
                    try {
                        File::put("routes/web.php", $route_content);
                        $this->info("Route removed successfully.");
                    } catch(\Throwable $e) {
                        $this->error("Error saving route: ". $e->getMessage());
                        $is_err = true;
                    } catch(\Exception $e) {
                        $this->error("Error saving route: ". $e->getMessage());
                        $is_err = true;
                    }


                }

            }

            if($is_err) {
                return false;
            }
            return true;
        }

        $this->error("Delete canceled!");
        return false;
    }


}
