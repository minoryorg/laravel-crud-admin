<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Artisan;
use File;
use Illuminate\Http\Request;
use Response;
use Session;
use View;

class ProcessController extends Controller
{
    /**
     * Display generator.
     *
     * @return Response
     */
    public function getGenerator()
    {
        return view('laravel-admin::generator');
    }

    /**
     * Process generator.
     *
     * @return Response
     */
    public function postGenerator(Request $request)
    {
        $commandArg = [];
        $commandArg['name'] = $request->crud_name;

        if ($request->has('fields')) {
            $fieldsArray = [];
            $validationsArray = [];
            $x = 0;
            foreach ($request->fields as $field) {
                if ($request->fields_index[$x] == 1) {
                    $indexesArray[] = $field;
                }
                
                if (!empty($request->fields_validations[$x])) {
                    $validationsArray[] = $field . '#' . $request->fields_validations[$x];
                }

                if ($request->fields_nullable[$x] == 1) {
                    $fieldsArray[] = $field . '#' . $request->fields_type[$x] . '#' . 'nullable';
                } else {
                    $fieldsArray[] = $field . '#' . $request->fields_type[$x];
                }

                $x++;
            }

            $commandArg['--fields'] = implode(';', $fieldsArray);
        }

        if (!empty($validationsArray)) {
            $commandArg['--validations'] = implode(';', $validationsArray);
        }
        
        if ($request->has('route')) {
            $commandArg['--route'] = $request->route;
        }

        if ($request->has('view_path')) {
            $commandArg['--view-path'] = $request->view_path;
        }

        if ($request->has('controller_namespace')) {
            $commandArg['--controller-namespace'] = $request->controller_namespace;
        }

        if ($request->has('model_namespace')) {
            $commandArg['--model-namespace'] = $request->model_namespace;
        }

        if ($request->has('route_group')) {
            $commandArg['--route-group'] = $request->route_group;
        }

        // Add Custom Fields
        if ($request->has('pagination')) {
            $commandArg['--pagination'] = $request->pagination;
        }

        if (!empty($indexesArray)) {
            $commandArg['--indexes'] = implode(',', $indexesArray);
        }
        
        if ($request->has('relationships')) {
            $commandArg['--relationships'] = $request->relationships;
        }

        try {
            Artisan::call('crud:generate', $commandArg);

            $menus = json_decode(File::get(base_path('resources/laravel-admin/menus.json')));

            $name = $commandArg['name'];
            $routeName = ($commandArg['--route-group']) ? $commandArg['--route-group'] . '/' . snake_case($name, '-') : snake_case($name, '-');

            $menus->menus = array_map(function ($menu) use ($name, $routeName) {
                if ($menu->section == 'Modules') {
                    array_push($menu->items, (object) [
                        'title' => $name,
                        'url' => '/' . $routeName,
                    ]);
                }

                return $menu;
            }, $menus->menus);

            File::put(base_path('resources/laravel-admin/menus.json'), json_encode($menus));

            Artisan::call('migrate');
        } catch (\Exception $e) {
            return Response::make($e->getMessage(), 500);
        }

        Session::flash('flash_message', 'Your CRUD has been generated. See on the menu.');

        return redirect('admin/generator');
    }
}
