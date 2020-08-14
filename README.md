# Genesis PHP Tools
Some libraries and helper functions commonly used by the Genesis Aplicaciones developer team across different solutions.

# Libraries included
## SpreadSheetTemplate.php
Quicky generate a spreadsheet document based on a common template with minimun configuration, but allowing you to customize several parameters, like:
+ owner_info
+ logo
+ logo_wide
+ logo_square
+ solution_name
+ title
+ subject
+ category
+ last_modified_by
+ table_color
+ color_title_bg
+ color_title_font
+ color_headers_bg
+ color_table_border
+ memory_limit

For the table data, you can pass the raw data array from a database query and especify excluded columns and edit the names by overwriting all or only renaming the ones needed.
Also, when generating the file you cant force the download directly or you can return the bin file and do what you want whith it (like a base64_encode).
### Examples

Minimal effort setup:
```php
// this way you get the report with the same data you pass and force the download
$template = new GenesisPhpTools\SpreadSheetTemplate(); // Initialice the class with the default configuration
$template->generate_file(
    "usuarios", //the report name
    $datos_reporte, //report data
    true, // force the download of the file
);
```
Result:
![](assets\example_minimal.png)

Advanced usage:
```php
// this way you get the report with excluded and renamed columns, a logo, custom color and correct file metadata

$doc_config = [
    'owner_info' => $this->session->razon_social,
    'logo' => 'assets/panel/img/logo.png',
    'solution_name' => 'TimbraXML',
    'table_color' => 'FF0C77C2'
];

$columns_config = [ 
    "excluded" => ['id_cuenta', 'id_usuario'] ,
    "renamed" => [
        "control_access" => "Acceso al portal",
        "panel_access" => "Nivel de acceso",
        "vista" => "Tipo de facturacion"
    ]
];

$template = new GenesisPhpTools\SpreadSheetTemplate($doc_config); // Initialice the class with the default configuration
$my_file = $template->generate_file(
    "usuarios", //the report name
    $datos_reporte, //report data
    false, // don't force the download of the file, so you can manipulate it
    $columns_config,
);

return_data(base64_encode($my_file)); // return the data like you want, in this case in base64 inside a json response
```
Result:
![](assets\example_advanced.png)