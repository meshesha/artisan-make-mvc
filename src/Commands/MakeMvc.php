<?php

namespace Meshesha\ArtisanMakeMvc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
// use Schema;

use Doctrine\DBAL\Connection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// use Illuminate\Support\Str;

use Meshesha\ArtisanMakeMvc\Generators\Generator;

class MakeMvc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:mvc {model}
                                {--W|incviews=true}
                                {--F|viewfolder=} 
                                {--H|includehidden=true} 
                                {--C|inccontroller=true} 
                                {--R|incroute=true}';


    /**
     * {@inheritdoc}
     */
    protected $description = 'Create controller, view and route from model';

    /**
     * Execute the command.
     */
    public function handle()
    {
        $laravel_ver = app()->version();


        $model = $this->argument('model');

        //check model

        $model_path = $this->getModelPath($model);
        if($model_path === false) {
            $this->error("the model '$model' not found!");
            return;
        }

        //get model data : db table name and columns name
        $includehidden = ($this->option('includehidden') == "true") ? true : false;
        $incctrlr = ($this->option('inccontroller') == "true") ? true : false;
        $incroute = ($this->option('incroute') == "true") ? true : false;
        $incviews = ($this->option('incviews') == "true") ? true : false;

        $model_data = $this->getModelData($model_path, $includehidden);

        // $cols_types_arr = $model_data["columnInfo"];
        // print_r($cols_types_arr); 
        // return;

        if(empty($model_data)) {
            $this->error('error getting table columns names , make sure db table exists and running artisan migrate command');
            return;
        }

        $model_data["model_name"] = $model;

        $viewfolder = $this->option('viewfolder');

        if($viewfolder == null && isset($model_data["db_table_name"])) {
            $viewfolder = $model_data["db_table_name"];
        }

        $view_path = $this->getViewPath($viewfolder);
        // $this->line('view_path : ' . $view_path . "\n");
        $controller_data = $this->setControllerData($model, $incctrlr);

        $generator = new Generator($this, $laravel_ver, $incctrlr, $incviews, $incroute);
        $generator->makeMvc($model_data, $view_path, $controller_data);

    }


    private function getViewPath($viewfolder)
    {
        $paths_arr = app('view.finder')->getPaths();

        $app_path = base_path();


        $paths_arr = array_map(function ($path) use ($app_path, $viewfolder) {
            $rl_path = str_replace("\\", "/", substr($path, strlen($app_path)) . (($viewfolder != null) ? "\\".$viewfolder : ""));

            if(substr($rl_path, 0, 1) == "/") {
                // if stasr with '/'
                $rl_path = substr($rl_path, 1);
            }

            return $rl_path;
        }, $paths_arr);

        if (count($paths_arr) === 1) {
            return head($paths_arr);
        }

        return $this->choice('Where do you want to create the view(s)?', $paths_arr, head($paths_arr));
    }


    // https://stackoverflow.com/a/60310985/8512438

    private function getAllModels()
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $models = [];
        foreach ((array)data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            $models = array_merge(collect(File::allFiles(base_path($path)))
                ->map(function ($item) use ($namespace) {
                    $path = $item->getRelativePathName();
                    return sprintf(
                        '\%s%s',
                        $namespace,
                        strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                    );
                })
                ->filter(function ($class) {
                    $valid = false;
                    if (class_exists($class)) {
                        $reflection = new \ReflectionClass($class);
                        $valid = $reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class) &&
                            !$reflection->isAbstract();
                    }
                    return $valid;
                })
                ->values()
                ->toArray(), $models);
        }
        return $models;
    }

    private function getModelPath($model)
    {
        $models_arr = $this->getAllModels();

        if(empty($models_arr)) {
            return false;
        }

        foreach($models_arr as $_model) {
            $model_name = substr($_model, strrpos($_model, "\\") + 1);
            if($model_name == $model) {
                return $_model;
            }
        }

        return false;

    }


    private function getModelData($model_path, $include_hidden = false)
    {
        

        if(substr($model_path, 0, 1) == "\\") {
            // if stasr with '\'
            $model_path = substr($model_path, 1);
        }

        $app_model = app($model_path);

        $db_table_name = $app_model->getTable();

        $db_primary_key = $app_model->getKeyName();

        $columnInfo = [];


        try {
            $columns_arr =  Schema::getColumnListing($db_table_name);

            try {
                //get db table column info -  Method 1
                // require doctrine/dbal (composer require doctrine/dbal)

                // Get the table columns using the Schema Builder
                $columns = Schema::getConnection()->getDoctrineSchemaManager()->listTableColumns($db_table_name);

                // $columnDetails = [];

                foreach ($columns as $column) {
                    $columnName = $column->getName();
                    $columnType = $column->getType()->getName();
                    $columnLength = $column->getLength();
                    $columnDefault = $column->getDefault();
                    $notnull = $column->getNotnull();


                    $columnInfo[$columnName] = [
                        'name' => $columnName,
                        'type' => strtolower($columnType),
                        'length' => $columnLength,
                        'default' => $columnDefault,
                        'nullable' => ($notnull) ? "no" : "yes",
                    ];
                }

                // print_r($columns);
            } catch(\Throwable $e) {
                // $this->error('error (method 1)'. $e->getMessage());

            } catch(\Exception $e) {
                // $this->error('error (method 1)'. $e->getMessage());
            }



            if(empty($columnInfo)) {
                try {
                    //get db table column info -  Method 2
                    $columns = DB::select('SHOW COLUMNS FROM ' . $db_table_name);
                    foreach ($columns as $column) {
                        // Parse the "Type" field to get the type and length
                        preg_match('/^([a-zA-Z]+)(?:\(([0-9]+)\))?/', $column->Type, $matches);

                        $columnType = $matches[1];
                        $columnLength = isset($matches[2]) ? (int)$matches[2] : null;

                        $columnInfo[$column->Field] = [
                            'name' => $column->Field,
                            'type' => strtolower($columnType),
                            'length' => $columnLength,
                            'default' => $column->Default,
                            'nullable' => strtolower($column->Null),
                        ];
                    }
                } catch(\Throwable $e) {
                    // $this->error('error (method 2)'. $e->getMessage());

                }

                // print_r($columnInfo);
            }

            //For Sqlite db (sqlite don't have 'SHOW COLUMNS FROM table_name')
            if(empty($columnInfo)) {
                try {
                    //get db table column info -  Method 3
                    $columns = DB::select('pragma table_info(' . $db_table_name . ')');

                    foreach ($columns as $column) {
                        $columnName = $column->name;
                        $columnInfo[$columnName] = [
                            'name' => $columnName,
                            'type' => strtolower($column->type),
                            'length' => null,
                            'default' => $column->dflt_value,
                            'nullable' => ($column->notnull) ? "no" : "yes",
                        ];
                    }
                } catch(\Throwable $e) {
                    // $this->error('error (method 3)'. $e->getMessage());

                }

                // print_r($columnInfo);
            }



        } catch(\Illuminate\Database\QueryException $e) {
            return [];
        }


        if(!$include_hidden) {
            $hidden_columns_arr = $app_model->getHidden();
            $columns_arr = array_diff($columns_arr, $hidden_columns_arr);
        }

        if(isset($app_model->make_mvc_hidden)) {
            $mvc_hidden_col = $app_model->make_mvc_hidden;
            if(is_array($mvc_hidden_col) && count($mvc_hidden_col) > 0) {
                $columns_arr = array_diff($columns_arr, $mvc_hidden_col);
            }

        }

        return [
            "model_path" => $model_path,
            "db_table_name" => $db_table_name,
            "columns" => $columns_arr,
            "columnInfo" => $columnInfo,
            "primary_key" => $db_primary_key
        ];

    }


    private function setControllerData($model, $is_create_ctrl)
    {
        $sub_folder = "";
        if(strpos($model, "/") !== false) {
            $name_arr = explode("/", $model);
            $model  =  array_pop($name_arr);
            $sub_folder = implode("/", $name_arr);
            if(trim($sub_folder) != "") {
                $sub_folder .= "/";
            }

        }
        $model = ucfirst($model);
        $ctrl_pos = stripos($model, "controller");
        if($ctrl_pos === false) {
            $controller_name = $model . 'Controller';
        } else {
            $fist_part_str = substr($model, 0, strlen($model) - 10); //strlen("controller") = 10
            $second_part_str = ucfirst(substr($model, $ctrl_pos));

            $controller_name = $fist_part_str . $second_part_str;

        }
        $controller_name_path = $this->setControllerFilePath($sub_folder . $controller_name);
        if($this->isControllerFileExist($controller_name_path) && $is_create_ctrl) {
            $this->error("controller '$controller_name' already exists!");

            if ($this->confirm('Do you wish to change controller name?', true)) {
                $new_controller_name = $this->ask('Enter new controller name?');
                if($new_controller_name == null || trim($new_controller_name) == "") {
                    $this->error("illegal name! controller will not be created ");
                    return [];
                }

                if (!preg_match('/[^A-Za-z0-9\/]/', $new_controller_name)) { // '/[^a-z\d]/i' should also work.
                    // string contains only english letters & digits
                    return $this->setControllerData($new_controller_name);
                } else {

                    $this->error("contains illegal char.!controller will not be created ");
                    return [];

                }

            }

        }

        return [
            'name' => $controller_name,
            'path' => $controller_name_path
        ];
    }

    private function setControllerFilePath($controllerName)
    {
        return "app/Http/Controllers/{$controllerName}.php";
    }

    private function isControllerFileExist($controllerNamePath)
    {
        return File::exists($controllerNamePath);
    }


}
