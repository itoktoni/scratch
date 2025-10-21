<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeCrud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {table : The table name to generate CRUD for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD operations for a given table with auto-detected fields';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $table = $this->argument('table');
        $groupCode = $table;
        $groupName = ucfirst($table);
        $groupIcon = 'bi bi-circle';
        $groupSort = 1;

        if (!Schema::hasTable($table)) {
            $this->error("Table '{$table}' does not exist.");
            return;
        }

        $this->info("Generating CRUD for table: {$table}");

        // Get table columns
        $columns = $this->getTableColumns($table);

        $this->info("Fields: " . implode(', ', array_keys($columns)));

        $this->info("Fields: " . implode(', ', array_keys($columns)));
        $modelName = Str::studly($table);
        $controllerName = $modelName . 'Controller';
        $moduleName = strtolower($modelName);

        // Create Model
        $this->createModel($modelName, $table, $columns);

        // Create Controller
        $this->createController($controllerName, $modelName, $table, $columns);

        // Create Views
        $this->createViews($moduleName, $columns);

        // Create Menu (using existing group 'app')
        $this->createMenu($table, 'app', $modelName, $controllerName);

        $this->info('CRUD generation completed successfully!');
        $this->info("Model: app/Models/{$modelName}.php");
        $this->info("Controller: app/Http/Controllers/{$controllerName}.php");
        $this->info("Views: resources/views/{$moduleName}/");
    }

    private function getTableColumns($table)
    {
        $columns = Schema::getColumnListing($table);
        $columnDetails = [];

        foreach ($columns as $column) {
            $columnInfo = DB::select("DESCRIBE {$table} {$column}")[0] ?? null;
            if ($columnInfo) {
                $columnDetails[$column] = [
                    'type' => $columnInfo->Type,
                    'nullable' => $columnInfo->Null === 'YES',
                    'default' => $columnInfo->Default,
                    'key' => $columnInfo->Key,
                ];
            }
        }

        return $columnDetails;
    }

    private function createModel($modelName, $table, $columns)
    {
        $fillable = [];
        $rules = [];
        $filterable = [];
        $sortable = [];

        foreach ($columns as $column => $details) {
            if (!in_array($column, ['id', 'created_at', 'updated_at'])) {
                $fillable[] = $column;

                // Generate validation rules
                $rule = [];
                if (!$details['nullable']) {
                    $rule[] = 'required';
                }

                // Add type-specific rules
                if (str_contains($details['type'], 'varchar') || str_contains($details['type'], 'text')) {
                    // String fields
                } elseif (str_contains($details['type'], 'int') || str_contains($details['type'], 'decimal') || str_contains($details['type'], 'double')) {
                    $rule[] = 'numeric';
                } elseif (str_contains($details['type'], 'date') || str_contains($details['type'], 'datetime')) {
                    $rule[] = 'date';
                } elseif (str_contains($details['type'], 'enum')) {
                    // Handle enum
                    preg_match("/enum\((.*)\)/", $details['type'], $matches);
                    if (isset($matches[1])) {
                        $options = str_replace("'", '', $matches[1]);
                        $rule[] = 'in:' . $options;
                    }
                }

                $rules[$column] = $rule;

                // Add to filterable and sortable
                if (!str_contains($details['type'], 'text')) {
                    $filterable[] = $column;
                }
                $sortable[] = $column;
            }
        }

        $primaryKey = 'id';
        $incrementing = true;
        $keyType = 'int';

        // Check if table has string primary key
        if (isset($columns['id']) && str_contains($columns['id']['type'], 'varchar')) {
            $primaryKey = 'id';
            $incrementing = false;
            $keyType = 'string';
        } elseif (isset($columns[$table . '_code'])) {
            $primaryKey = $table . '_code';
            $incrementing = false;
            $keyType = 'string';
        }

        $stub = File::get(app_path('Console/Commands/stubs/model.stub'));
        $content = str_replace([
            '{{modelName}}',
            '{{table}}',
            '{{primaryKey}}',
            '{{incrementing}}',
            '{{keyType}}',
            '{{fillable}}',
            '{{rules}}',
            '{{filterable}}',
            '{{sortable}}',
        ], [
            $modelName,
            $table,
            $primaryKey,
            $incrementing ? 'true' : 'false',
            $keyType,
            $this->formatArray($fillable),
            $this->formatRulesArray($rules),
            $this->formatArray($filterable),
            $this->formatArray($sortable),
        ], $stub);

        File::put(app_path("Models/{$modelName}.php"), $content);
    }

    private function createController($controllerName, $modelName, $table, $columns)
    {
        $stub = File::get(app_path('Console/Commands/stubs/controller.stub'));
        $content = str_replace([
            '{{controllerName}}',
            '{{modelName}}',
            '{{table}}',
            '{{modelName | camel}}',
        ], [
            $controllerName,
            $modelName,
            $table,
            Str::camel($modelName),
        ], $stub);

        File::put(app_path("Http/Controllers/{$controllerName}.php"), $content);
    }

    private function createViews($moduleName, $columns)
    {
        $viewPath = resource_path("views/{$moduleName}");
        File::makeDirectory($viewPath, 0755, true, true);

        // Create data.blade.php
        $this->createDataView($viewPath, $moduleName, $columns);

        // Create form.blade.php
        $this->createFormView($viewPath, $moduleName, $columns);

        // Create show.blade.php
        $this->createShowView($viewPath, $moduleName, $columns);
    }

    private function createDataView($viewPath, $moduleName, $columns)
    {
        $headers = [];
        $tableHeaders = [];
        $tableData = [];
        $filterFields = [];
        $filterOptions = ["'All Filter'"];

        foreach ($columns as $column => $details) {
            if (!in_array($column, ['id', 'created_at', 'updated_at'])) {
                $label = ucwords(str_replace('_', ' ', $column));
                $headers[] = "<x-th column=\"{$column}\" text=\"{$label}\" :model=\"\$data->first()\" />";
                $tableData[] = "<x-td field=\"{$column}\" :model=\"\$list\" />";

                // Add filter fields for first two columns or common searchable fields
                if (count($filterFields) < 2 && !str_contains($details['type'], 'text')) {
                    $filterFields[] = "<x-input name=\"{$column}\" type=\"text\" placeholder=\"Search by {$label}\" :value=\"request('{$column}')\" col=\"6\"/>";
                    $filterOptions[] = "'{$column}'";
                }
            }
        }

        $headersStr = implode("\n                                    ", $headers);
        $tableDataStr = implode("\n                                        ", $tableData);
        $filterFieldsStr = implode("\n                        ", $filterFields);
        $filterOptionsStr = "[" . implode(", ", $filterOptions) . "]";

        $stub = File::get(app_path('Console/Commands/stubs/data_view.stub'));
        $content = str_replace([
            '{{moduleName}}',
            '{{headers}}',
            '{{tableData}}',
            '{{colspan}}',
            '{{filterFields}}',
            '{{filterOptions}}',
        ], [
            $moduleName,
            $headersStr,
            $tableDataStr,
            count($headers) + 2, // +2 for checkbox and actions
            $filterFieldsStr,
            $filterOptionsStr,
        ], $stub);

        File::put("{$viewPath}/data.blade.php", $content);
    }

    private function createFormView($viewPath, $moduleName, $columns)
    {
        $formFields = [];

        foreach ($columns as $column => $details) {
            if (!in_array($column, ['id', 'created_at', 'updated_at'])) {
                $label = ucwords(str_replace('_', ' ', $column));
                $required = !$details['nullable'] ? 'required' : '';

                if (str_contains($details['type'], 'enum')) {
                    // Handle enum as select
                    preg_match("/enum\((.*)\)/", $details['type'], $matches);
                    if (isset($matches[1])) {
                        $options = explode(',', str_replace("'", '', $matches[1]));
                        $optionsStr = "['" . implode("', '", $options) . "']";
                        $formFields[] = "<x-select name=\"{$column}\" :options=\"{$optionsStr}\" {$required} />";
                    }
                } elseif (str_contains($details['type'], 'text')) {
                    $formFields[] = "<x-textarea name=\"{$column}\" rows=\"4\" {$required} />";
                } elseif (str_contains($details['type'], 'int') || str_contains($details['type'], 'decimal') || str_contains($details['type'], 'double')) {
                    $formFields[] = "<x-input type=\"number\" name=\"{$column}\" {$required} />";
                } elseif (str_contains($details['type'], 'date')) {
                    $formFields[] = "<x-input type=\"date\" name=\"{$column}\" {$required} />";
                } elseif (str_contains($details['type'], 'datetime')) {
                    $formFields[] = "<x-input type=\"datetime-local\" name=\"{$column}\" {$required} />";
                } elseif ($details['key'] === 'PRI' && !$details['nullable']) {
                    $formFields[] = "<x-input name=\"{$column}\" :attributes=\"isset(\$model) ? ['readonly' => true] : []\" hint=\"{$label} cannot be changed\" {$required} />";
                } else {
                    $formFields[] = "<x-input name=\"{$column}\" {$required} />";
                }
            }
        }

        $formFieldsStr = implode("\n\n            ", $formFields);

        $stub = File::get(app_path('Console/Commands/stubs/form_view.stub'));
        $content = str_replace([
            '{{moduleName}}',
            '{{formFields}}',
        ], [
            $moduleName,
            $formFieldsStr,
        ], $stub);

        File::put("{$viewPath}/form.blade.php", $content);
    }

    private function createShowView($viewPath, $moduleName, $columns)
    {
        $showFields = [];

        foreach ($columns as $column => $details) {
            if (!in_array($column, ['id', 'created_at', 'updated_at'])) {
                $label = ucwords(str_replace('_', ' ', $column));
                $showFields[] = "<x-input name=\"{$column}\" :model=\"\$model\" readonly />";
            }
        }

        $showFieldsStr = implode("\n            ", $showFields);

        $stub = File::get(app_path('Console/Commands/stubs/show_view.stub'));
        $content = str_replace([
            '{{moduleName}}',
            '{{showFields}}',
        ], [
            $moduleName,
            $showFieldsStr,
        ], $stub);

        File::put("{$viewPath}/show.blade.php", $content);
    }

    private function createOrUpdateGroup($groupCode, $groupName, $groupIcon, $groupSort)
    {
        $group = \App\Models\Group::where('group_code', $groupCode)->first();

        if (!$group) {
            \App\Models\Group::create([
                'group_code' => $groupCode,
                'group_name' => $groupName,
                'group_icon' => $groupIcon,
                'group_sort' => $groupSort,
            ]);
            $this->info("Created group: {$groupName}");
        } else {
            $group->update([
                'group_name' => $groupName,
                'group_icon' => $groupIcon,
                'group_sort' => $groupSort,
            ]);
            $this->info("Updated group: {$groupName}");
        }
    }

    private function createMenu($table, $groupCode, $modelName, $controllerName)
    {
        $menuCode = $table;
        $menuName = ucwords(str_replace('_', ' ', $table));

        $menu = \App\Models\Menu::where('menu_code', $menuCode)->first();

        if (!$menu) {
            \App\Models\Menu::create([
                'menu_code' => $menuCode,
                'menu_group' => $groupCode,
                'menu_name' => $menuName,
                'menu_controller' => "App\\Http\\Controllers\\{$controllerName}",
                'menu_action' => 'index',
                'menu_sort' => 1,
            ]);
            $this->info("Created menu: {$menuName}");
        } else {
            $menu->update([
                'menu_group' => $groupCode,
                'menu_name' => $menuName,
                'menu_controller' => "App\\Http\\Controllers\\{$controllerName}",
            ]);
            $this->info("Updated menu: {$menuName}");
        }
    }

    private function formatArray($array)
    {
        if (empty($array)) {
            return '[]';
        }

        $formatted = "[\n            '" . implode("',\n            '", $array) . "',\n        ]";
        return $formatted;
    }

    private function formatRulesArray($rules)
    {
        if (empty($rules)) {
            return '[]';
        }

        $formatted = "[\n";
        foreach ($rules as $field => $ruleArray) {
            $ruleStr = implode("', '", $ruleArray);
            $formatted .= "            '{$field}' => ['{$ruleStr}'],\n";
        }
        $formatted .= "        ]";
        return $formatted;
    }
}