<?php
/**
 * generates controller file , view file and add route
 */

namespace Meshesha\ArtisanMakeMvc\Generators;

// use App;
use Illuminate\Support\Facades\App;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class Generator
{
    protected $config;

    protected $laravel_ver;

    protected $inc_controller;
    protected $inc_views;
    protected $inc_route;

    protected $views_arr = ['index', 'create', 'edit', 'show'];

    /**
     * Command instance
     * @var Command
     */
    protected $cmd;

    /**
     * Create a new Generator instance.
     * @param Command $cmd
     */
    public function __construct(Command $cmd, $laravel_ver, $inc_ctrlr, $incviews, $inc_route)
    {
        // parent::__construct();
        $this->cmd = $cmd;

        $config_arr = [
            'template' => App::get('config')->get('ArtisanMakeMvc.template', 'default'),
            'extends' => App::get('config')->get('ArtisanMakeMvc.extends', "@extends('layouts.app')"),
            'section' => App::get('config')->get('ArtisanMakeMvc.section', "@section('content')"),
            'endsection' => App::get('config')->get('ArtisanMakeMvc.endsection', "@endsection"),
            'add_route' => App::get('config')->get('ArtisanMakeMvc.add_route', true),
        ];

        $this->config = $config_arr;
        $this->laravel_ver = $laravel_ver;

        $this->inc_controller = $inc_ctrlr;
        $this->inc_views = $incviews;
        $this->inc_route = $inc_route;


    }
    /**
     * @param  array  $model_param
     * @param  string  $view_path
     * @param  array  $controller_param
     */
    public function makeMvc(array $model_param, string $view_path, array $controller_param)
    {
        $create_controller = false;
        $create_views = false;
        $add_rout = false;

        if(!empty($controller_param) && $this->inc_controller) {
            $create_controller = $this->createController($model_param, $controller_param);
        }

        if(trim($view_path) != '' && $this->inc_views) {
            $create_views = $this->createViews($model_param, $view_path, $controller_param);
        }

        //add route
        if($this->config['add_route'] && $this->inc_route) {
            $add_rout = $this->addRoute($model_param, $controller_param);

        }
        if($create_controller || $create_views || $add_rout) {
            $his_stt = $this->addToHis($model_param["model_name"], $create_controller, $create_views, $add_rout);
        }

    }

    private function createDestFileFromStubFile($stub_path, $stub_var_arr, $dest_path)
    {


        $stub_contents = file_get_contents($stub_path);

        foreach ($stub_var_arr as $search => $replace) {
            $stub_contents = str_replace('$'.$search.'$', $replace, $stub_contents);
        }


        if (File::exists($dest_path)) {
            $this->cmd->error("File : {$dest_path} already exits");

            return false;

        }

        try {
            File::put($dest_path, $stub_contents);
            $this->cmd->info("File : {$dest_path} created successfully.");
            return $dest_path;
        } catch (FileNotFoundException $e) {
            $this->cmd->error("Error :" . $e->getMessage());

        } catch (\Exception $e) {

            $this->cmd->error("Error :" . $e->getMessage());

        }

        return false;


    }



    private function createController($model, $controller)
    {
        $ctrl_path = $controller["path"];

        $ctrl_stub_file = $this->getControllerStubPath();
        // echo "ctrl_stub_file: $ctrl_stub_file";

        $stub_variables = $this->getStubVariables("controller", $model, "", $controller);


        return $this->createDestFileFromStubFile($ctrl_stub_file, $stub_variables, $ctrl_path);

    }
    /**
     * Return the stub file path
     * @return string
     *
     */
    private function getControllerStubPath()
    {
        return   __DIR__ .'/stubs/controller/controller.stub';
    }

    private function getStoreUpdatePlaceHolder($act_type, $cols, $model_name_lower, $primary_key, $includePrimaryKey = false)
    {
        if(empty($cols)) {
            return '';
        }

        $str = "\n";
        foreach($cols as $col) {
            if($includePrimaryKey || $col != $primary_key) {
                if($act_type == "v_to_m") {
                    $str .= "       \$$model_name_lower->$col = \$$col; \n";
                } elseif($act_type == "r_to_v") {
                    $str .= "       \$$col = \$request->input('$col'); \n";

                }
            }
        }

        return $str;
    }



    /**
     * Create views
     */

    private function createViews($model, $view_path, $controller)
    {
        //check view path (folder) exist , create if not

        $is_create_folder = $this->createViewFolder($view_path);
        if(!$is_create_folder) {
            return false;
        }

        $views_arr = $this->views_arr;

        $add_paths_arr = [];

        foreach($views_arr as $view_name) {
            $stub_file_path = $this->getViewStubPath($view_name);
            if(!$stub_file_path) {
                $this->cmd->error("Error: stub file not found for '$view_name'!");
                continue;
            }

            $full_view_path = $view_path . "/" . $view_name . ".blade.php";

            $stub_variables = $this->getStubVariables($view_name, $model, $view_path, $controller);

            if(!empty($stub_variables)) {
                $is_created =  $this->createDestFileFromStubFile($stub_file_path, $stub_variables, $full_view_path);
                if($is_created !== false) {
                    $add_paths_arr[] = $is_created;
                }
            }

        }

        if(!empty($add_paths_arr)) {
            return $add_paths_arr;
        }

        return false;



    }

    private function createViewFolder($view_path)
    {
        if(!File::isDirectory($view_path)) {
            try {
                File::makeDirectory($view_path, 0777, true, true);
                return true;
            } catch(\Exception $e) {

                $this->cmd->error("Error crating '$view_path' directory:" . $e->getMessage());
                return false;
            }
        }
        return true;
    }

    private function getViewStubPath($view_name)
    {
        $template = $this->config["template"];

        $path =  __DIR__ ."/stubs/views/{$template}/{$view_name}.stub";
        if(!File::exists($path)) {
            return false;
        }

        return $path;

    }

    private function getStubVariables($type, $model, $view_path, $controller)
    {

        $model_path = $model["model_path"];
        $primary_key = $model["primary_key"];
        $table_name = $model["db_table_name"];
        $model_name = $model["model_name"];

        $ctrl_name = $controller["name"];
        $ctrl_path = $controller["path"];

        $extends = $this->config["extends"];
        $section = $this->config["section"];
        $section_end = $this->config["endsection"];


        $model_name_lower = strtolower($model_name);
        $tbl_name_to_title = ucfirst(str_replace("_", " ", $table_name));


        switch($type) {
            case "controller":
                $request_to_var = $this->getStoreUpdatePlaceHolder("r_to_v", $model["columns"], $model_name_lower, $primary_key, true);
                $var_to_model = $this->getStoreUpdatePlaceHolder("v_to_m", $model["columns"], $model_name_lower, $primary_key, false);
                return  [
                    'USE_MODAL'         => 'use ' .$model_path,
                    'CONTROLLER_NAME'   => $ctrl_name,
                    'MODAL_NAME'        => $model_name,
                    'MODAL_NAME_LOWER'  => $model_name_lower,
                    'TABLE_NAME'        => $table_name,
                    'REQUEST_INPUT_TO_VAR' => $request_to_var,
                    'VARTOMODEL'        => $var_to_model,
                ];
                break;
            case "index":
                $tbl_th = $this->getTableTh("th", $model, true);
                $tbl_td = $this->getTableTh("td", $model, true);

                return [
                    "EXTEND_LAYOUTS"    => $extends,
                    "YIELD_SECTION"     => $section,
                    "HEAD_TITLE"        => $tbl_name_to_title,
                    "TABLE_NAME"        => $table_name,
                    "MODAL_NAME_LOWER"  => $model_name_lower,
                    "TABLETH"           => $tbl_th,
                    "TABLEBODYTD"       => $tbl_td,
                    "PRIMARY_KEY_NAME"  => $primary_key,
                    "YIELD_SECTION_END" => $section_end,
                ];
                break;
            case "show":
                $show_content = $this->getShowContent($model);
                return [
                    "EXTEND_LAYOUTS"    => $extends,
                    "YIELD_SECTION"     => $section,
                    "HEAD_TITLE"        => $tbl_name_to_title,
                    "TABLE_NAME"        => $table_name,
                    "SHOW_CONTENT"      => $show_content,
                    "MODAL_NAME_LOWER"  => $model_name_lower,
                    "PRIMARY_KEY_NAME"  => $primary_key,
                    "YIELD_SECTION_END" => $section_end,

                ];
                break;
            case "create":
                $cu_content = $this->getContent("create", $model);
                return [
                    "EXTEND_LAYOUTS"    => $extends,
                    "YIELD_SECTION"     => $section,
                    "HEAD_TITLE"        => $tbl_name_to_title,
                    "TABLE_NAME"        => $table_name,
                    "CREATE_CONTENT"    => $cu_content,
                    "YIELD_SECTION_END" => $section_end,
                ];
                break;
            case "edit":
                $cu_content = $this->getContent("update", $model);
                return [
                    "EXTEND_LAYOUTS"    => $extends,
                    "YIELD_SECTION"     => $section,
                    "HEAD_TITLE"        => $tbl_name_to_title,
                    "TABLE_NAME"        => $table_name,
                    "UPDATE_CONTENT"    => $cu_content,
                    "MODAL_NAME_LOWER"  => $model_name_lower,
                    "PRIMARY_KEY_NAME"  => $primary_key,
                    "YIELD_SECTION_END" => $section_end,
                ];
                break;
        }
        return [];
    }

    private function getTableTh($type, $model, $includePrimaryKey = false)
    {
        $cols = $model["columns"];
        $primary_key = $model["primary_key"];

        $tmpl = $this->config["template"];



        if(empty($cols)) {
            return '';
        }

        $str = "\n";


        foreach($cols as $col) {
            if($includePrimaryKey || $col != $primary_key) {
                if($type == "th") {
                    $col = $this->removeSpecialChar(ucfirst($col));
                    $numOfTabs = 3; //default template
                    if($tmpl == "bootstrap4") {
                        $numOfTabs = 6;
                    }
                    $tabs = $this->generateTabs($numOfTabs);
                    // $str .= "           <th>$col</th>\n";
                    $str .= "$tabs<th>$col</th>\n";
                } elseif($type == "td") {

                    $model_name_lower = strtolower($model["model_name"]);
                    $numOfTabs = 4; //default template
                    if($tmpl == "bootstrap4") {
                        $numOfTabs = 7;
                    }

                    $tabs = $this->generateTabs($numOfTabs);
                    // $str .= "               <td>{{ \$$model_name_lower->" . $col . "}} </td>\n";
                    $str .= "$tabs<td>{{ \$$model_name_lower->" . $col . "}} </td>\n";

                }

            }
        }

        return $str;


    }

    private function getShowContent($model, $includePrimaryKey = true)
    {
        $cols = $model["columns"];
        $primary_key = $model["primary_key"];

        $tmpl = $this->config["template"];


        if(empty($cols)) {
            return '';
        }

        $str = "\n";

        $div_wrp_attr = 'style="display:flex;flex-wrap:wrap;margin-top:0.5rem;"';
        $lbl_attr = 'style="flex:0 0 auto;width:16.66666667%;"';
        $input_div_attr = 'style="flex:0 0 auto;width:83.33333333%;"';
        $input_attr = 'style="display:block;width:100%;padding:0.375rem 0.75rem;appearance:none;border:1px solid #dee2e6;border-radius:0.375rem;"';

        if($tmpl == "bootstrap4") {
            $div_wrp_attr = 'class="row mt-2"';
            $lbl_attr = 'class="col-2"';
            $input_div_attr = 'class="col-10"';
            $input_attr = 'class="border w-100 p-1 rounded"';
        }


        foreach($cols as $col) {
            if($includePrimaryKey || $col != $primary_key) {

                $col_lbl = $this->removeSpecialChar(ucfirst($col));
                $model_name_lower = strtolower($model["model_name"]);

                $numOfTabs = 1; //default template
                if($tmpl == "bootstrap4") {
                    $numOfTabs = 6;
                }
                $tabs = $this->generateTabs($numOfTabs);

                // $str .= "$tabs<p><span>$col_lbl :</span>$tabs<span>{{ \$$model_name_lower->" . $col . "}}</span></p>\n";


                // default
                $tabs_lvl1 = $this->generateTabs(2);
                $tabs_lvl2 = $this->generateTabs(3);
                $tabs_lvl3 = $this->generateTabs(4);


                if($tmpl == "bootstrap4") {
                    // bootstrap4
                    $tabs_lvl1 = $this->generateTabs(3);
                    $tabs_lvl2 = $this->generateTabs(4);
                    $tabs_lvl3 = $this->generateTabs(5);
                }

                $str .= "$tabs_lvl1<div $div_wrp_attr>\n";
                $str .= "$tabs_lvl2<label $lbl_attr>$col_lbl :</label>\n";
                $str .= "$tabs_lvl2<div $input_div_attr>\n";
                $str .= "$tabs_lvl3<div $input_attr>{{ \$$model_name_lower->$col}}</div>\n";
                $str .= "$tabs_lvl2</div>\n";
                $str .= "$tabs_lvl1</div>\n";


            }
        }

        return $str;
    }

    private function getContent($action_type, $model, $includePrimaryKey = false)
    {
        $cols = $model["columns"];
        $primary_key = $model["primary_key"];
        $columnInfo = (isset($model["columnInfo"])) ? $model["columnInfo"] : [];
        $tmpl = $this->config["template"];

        if(empty($cols)) {
            return '';
        }

        $str = "\n";


        foreach($cols as $col) {
            if($includePrimaryKey || $col != $primary_key) {

                $col_lbl = $this->removeSpecialChar(ucfirst($col));
                $model_name_lower = strtolower($model["model_name"]);
                $val = "";

                //add if required , max/min - length - TODO

                $div_wrp_attr = 'style="display:flex;flex-wrap:wrap;margin-top:0.5rem;"';
                $lbl_attr = 'style="flex:0 0 auto;width:16.66666667%;"';
                $input_div_attr = 'style="flex:0 0 auto;width:83.33333333%;"';
                $input_attr = 'style="display:block;width:100%;padding:0.375rem 0.75rem;appearance:none;border:1px solid #dee2e6;border-radius:0.375rem;"';

                if($tmpl == "bootstrap4") {
                    $div_wrp_attr = 'class="form-group row mt-2"';
                    $lbl_attr = 'class="col-2"';
                    $input_div_attr = 'class="col-10"';
                    $input_attr = 'class="form-control"';
                }

                if(empty($columnInfo)) {
                    if($action_type == "update") {
                        $val = "value=\"{{\$$model_name_lower->$col}}\"";
                    }
                    // $tabs = $this->generateTabs(2);
                    // $str .= "$tabs<p><span>$col_lbl :</span>  <span><input type=\"text\" name=\"$col\" $val /></span></p>\n";

                    // $str .= "<div $div_wrp_attr>
                    //             <label $lbl_attr>$col_lbl :</label>
                    //             <div $input_div_attr>
                    //                 <input type=\"text\" name=\"$col\" $input_attr  $val />
                    //             </div>
                    //         </div>\n";
                    // default
                    $tabs_lvl1 = $this->generateTabs(2);
                    $tabs_lvl2 = $this->generateTabs(3);
                    $tabs_lvl3 = $this->generateTabs(4);


                    if($tmpl == "bootstrap4") {
                        // bootstrap4
                        $tabs_lvl1 = $this->generateTabs(4);
                        $tabs_lvl2 = $this->generateTabs(5);
                        $tabs_lvl3 = $this->generateTabs(6);
                    }

                    $str .= "$tabs_lvl1<div $div_wrp_attr>\n";
                    $str .= "$tabs_lvl2<label $lbl_attr>$col_lbl :</label>\n";
                    $str .= "$tabs_lvl2<div $input_div_attr>\n";
                    $str .= "$tabs_lvl3<input type=\"text\" name=\"$col\" $input_attr  $val />\n";
                    $str .= "$tabs_lvl2</div>\n";
                    $str .= "$tabs_lvl1</div>\n";

                } else {
                    if($action_type == "update") {
                        $val = "value=\"{{\$$model_name_lower->$col}}\"";
                    }

                    $col_type = $columnInfo[$col]['type'];
                    $col_length = $columnInfo[$col]['length'];
                    $col_default = $columnInfo[$col]['default'];
                    $col_is_nullable = $columnInfo[$col]['nullable'];
                    $html_elem = "";
                    if($col_type == "text") {
                        if($action_type == "update") {
                            $val = "{{\$$model_name_lower->$col}}";
                        }

                        $html_elem = "<textarea name=\"$col\" $input_attr>$val</textarea>";
                    } elseif($col_type == "date") {
                        $html_elem = "<input type=\"date\" name=\"$col\" $input_attr $val />";
                    } elseif($col_type == "datetime" || $col_type == "timestamp") {
                        if($action_type == "update") {
                            $val = "value=\"{{(\$$model_name_lower->$col != null)?date('Y-m-d\TH:i:s',strtotime(\$$model_name_lower->$col)):''}}\"";
                        }

                        $html_elem = "<input type=\"datetime-local\" name=\"$col\" $input_attr $val />";
                    }
                    // elseif($col_type == "int" || $col_type == "bigInt" || ...) {
                    //     //number input  TODO
                    // }
                    else {
                        $html_elem = "<input type=\"text\" name=\"$col\" $input_attr $val />";

                    }
                    // $tabs = $this->generateTabs(2);
                    // $str .= "$tabs<p><span>$col_lbl :</span>  <span>$html_elem</span></p>\n";

                    // default
                    $tabs_lvl1 = $this->generateTabs(3);
                    $tabs_lvl2 = $this->generateTabs(4);
                    $tabs_lvl3 = $this->generateTabs(5);


                    if($tmpl == "bootstrap4") {
                        // bootstrap4
                        $tabs_lvl1 = $this->generateTabs(4);
                        $tabs_lvl2 = $this->generateTabs(5);
                        $tabs_lvl3 = $this->generateTabs(6);
                    }

                    $str .= "$tabs_lvl1<div $div_wrp_attr>\n";
                    $str .= "$tabs_lvl2<label $lbl_attr>$col_lbl :</label>\n";
                    $str .= "$tabs_lvl2<div $input_div_attr>\n";
                    $str .= "$tabs_lvl3{$html_elem}\n";
                    $str .= "$tabs_lvl2</div>\n";
                    $str .= "$tabs_lvl1</div>\n";

                }
            }
        }

        return $str;
    }

    /**
     * Add route to routes/web.php
     */

    private function addRoute($model, $controller)
    {
        $route_name = $model["db_table_name"];
        $ctrl_path = $controller["path"];
        $ctrl_name = $controller["name"];
        $ctrl_path = ucfirst($ctrl_path);
        $ctrl_path = str_replace("/", "\\", $ctrl_path);
        $ctrl_path = str_replace(".php", "::class", $ctrl_path);

        $ctrl_name_class = $ctrl_name."::class";

        $route = "Route::resource('$route_name', $ctrl_path);";
        $short_route = "Route::resource('$route_name', $ctrl_name_class);";

        $lara_ver = $this->laravel_ver;
        $mej_ver = (int) explode(".", $lara_ver)[0];

        if($mej_ver < 8) {

            $route = "Route::resource('$route_name', '$ctrl_name');";

        }

        $route_content = File::get("routes/web.php");

        if(strpos($route_content, $route) === false && strpos($route_content, $short_route) === false) {
            $new_route = "\n\n// $route_name:\n$route";
            $added_route = $route_content . $new_route;

            if(File::put("routes/web.php", $added_route)) {
                //create backup to old route ($route_content) - TODO
                // $this->backupRouteFile($route_content);
                $this->cmd->info("Route '\\$route_name' add successfully.");
                return $new_route;
            }
            $this->cmd->error("error adding route '\\$route_name'!");
            return false;
        }

        $this->cmd->error("Route '\\$route_name' already exists.");

        return false;

    }

    private function removeSpecialChar($str)
    {
        return preg_replace('/[^a-zA-Z0-9]/s', ' ', $str);
    }



    private function generateTabs($numTabs)
    {
        $tabs = '';

        for ($i = 0; $i < $numTabs; $i++) {
            $tabs .= "\t"; // Add a tab character to the string
        }

        return $tabs;
    }

    /**
     * Add to his file
     */
    private function addToHis($model_name, $create_controller, $create_views, $route)
    {
        //get history file path
        $his_path = $this->getHisFile();

        $his_contents = file_get_contents($his_path);

        $his_json = json_decode($his_contents);

        $obj = new \stdClass();

        $obj->name = $model_name;
        $obj->datetime = date("d-m-Y h:i:s");

        if($create_controller !== false) {
            $obj->controller = $create_controller;
        }

        if($create_views !== false) {
            $obj->views = $create_views;
        }
        if($route !== false) {
            $obj->route = $route;
        }

        $his_json[] = $obj;
        $his_str = json_encode($his_json);

        // write to history file

        return $this->writHisContent($his_path, $his_str);

    }

    private function getHisFile()
    {
        return   __DIR__ .'/history/history.json';
    }

    private function writHisContent($his_file_path, $his_content)
    {
        return file_put_contents($his_file_path,$his_content);
    }
}