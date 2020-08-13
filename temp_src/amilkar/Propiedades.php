<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Propiedades extends CI_Controller {

	public function __construct(){
		parent::__construct();
		$this->load->model('Admin/Propiedades_model', 'propiedades');
    }
    
    public function reporte_pdf(){
        $filtros['busqueda'] = [];        
        $busqueda = [];
        if(!empty($_POST["referencia"])){
            $busqueda['where']['tp.id_propiedad'] = $_POST["referencia"];    
        }
        if(!empty($_POST["nombre"])){
            $busqueda['like']['tp.titulo'] = $_POST["nombre"];    
        }
        if(!empty($_POST["tipo_propiedad"])){
            $busqueda['where']['tp.tipo_propiedad'] = $_POST["tipo_propiedad"];    
        }
        if(!empty($_POST["clasificacion"])){
            $busqueda['where']['tp.clasificacion'] = $_POST["clasificacion"];    
        }
        if(!empty($_POST["tipo_publicacion"])){
            $busqueda['where']['tp.tipo_publicacion'] = $_POST["tipo_publicacion"];             
        }
        if(!empty($_POST["status"])){
            $busqueda['where']['tp.activo'] = $_POST["status"] == 'D' ? 1 : 0;                         
        }
        $filtros['orden'] = "tp.id_propiedad DESC";

        $filtros['busqueda'] = $busqueda;

        $tbody = '';
        $data = $this->propiedades->obtiene_propiedades($filtros);
        if($data){
            $tbody .= '<table cellspacing="1" cellpadding="2" border="0" style="text-align:left; color:#000000; font-size:8px;">';
            $cont_registro = 0;
            $tr_colors = [ "#E6E6E6", "#F2F2F2" ];            
            foreach ($data['datos']->result() as $key => $value) {
                $color_registro = is_int($cont_registro / 2);
                $tbody .= '<tr style="background-color:' . ($tr_colors[$color_registro ? 0 : 1]) . '">                                                
                                <td width="140px">'.str_pad($value->id_propiedad, 5, "0", STR_PAD_LEFT).'</td>
                                <td width="220px">'.$value->titulo.'</td>
                                <td style="text-align:right" width="80px">'. ( $value->tipo_propiedad == 'I' ? 'Inmueble' : 'Terreno' ) .'</td>
                                <td style="text-align:right" width="80px">'. ( $value->clasificacion == 'C' ? 'Ciudad' : 'Playa' ) .'</td>
                                <td style="text-align:right" width="90px">$ '. number_format( $value->precio, 2, '.', ',' ) .'</td>
                                <td style="text-align:right" width="70px">'. ( $value->tipo_publicacion == 'V' ? 'Venta' : 'Renta' ) .'</td>                                
                                <td style="text-align:right" width="90px">'. ( $value->activo ? 'Disponible' : 'No Disponible' ).'</td>  
                            </tr>';                
                $cont_registro++;        
            }                             
            $tbody.='</table>';
        }    

        $html_encabezado = '<table cellspacing="0" cellpadding="2" border="0" style="text-align:left; color:#000000;">
                                <tr>
                                    <td><h3>Reporte Propiedades</h3></td>
                                    <td style="text-align:right"><img src="' . base_url() . 'assets/img/logo_roye.jpg" width="60px" style="margin:0 auto;"/></td>                                  
                                </tr>
                            </table>
                            <table cellspacing="1" cellpadding="2" border="0" style="text-align:left; color:#FFFFFF; font-size:10px;">
                                <tr style="background-color:#AB3EAA">                                            
                                    <td width="140px">Referencia</td>
                                    <td width="220px">Nombre</td>
                                    <td style="text-align:right" width="80px">Tipo</td>
                                    <td style="text-align:right" width="80px">Clasificación</td>
                                    <td style="text-align:right" width="90px">Precio</td>
                                    <td style="text-align:right" width="70px">Publicación</td>                                
                                    <td style="text-align:right" width="90px">Status</td>                                    
                                </tr>
                            </table>';                             

        $filename = 'Reporte_Propiedades';                                
        $pdf_b64 = reporte_pdf('L', $html_encabezado, $tbody);     
        
        $opResult = array(
            'status' => 1,
            'nombre' => $filename,
            'data'=> "data:application/pdf;base64,".$pdf_b64            
        );
        
        exit (json_encode($opResult));
    }

    public function reporte_excel(){
        $data = $this->input->post('data');

        $columnas = [
            'REFERENCIA', 
            'NOMBRE', 
            'TIPO', 
            'CLASIFICACION', 
            'PRECIO', 
            'PUBLICACION',  
            'STATUS'            
        ];
        $encabezado = 'REPORTE PROPIEDADES';
       
        $filtros['busqueda'] = [];        
        $busqueda = [];
        if(!empty($_POST["referencia"])){
            $busqueda['where']['tp.id_propiedad'] = $_POST["referencia"];    
        }
        if(!empty($_POST["nombre"])){
            $busqueda['like']['tp.titulo'] = $_POST["nombre"];    
        }
        if(!empty($_POST["tipo_propiedad"])){
            $busqueda['where']['tp.tipo_propiedad'] = $_POST["tipo_propiedad"];    
        }
        if(!empty($_POST["clasificacion"])){
            $busqueda['where']['tp.clasificacion'] = $_POST["clasificacion"];    
        }
        if(!empty($_POST["tipo_publicacion"])){
            $busqueda['where']['tp.tipo_publicacion'] = $_POST["tipo_publicacion"];             
        }
        if(!empty($_POST["status"])){
            $busqueda['where']['tp.activo'] = $_POST["status"] == 'D' ? 1 : 0;                         
        }
        $filtros['orden'] = "tp.id_propiedad DESC";

        $filtros['busqueda'] = $busqueda;        

        $array_data = [];
        $data = $this->propiedades->obtiene_propiedades($filtros);
        if($data){
            foreach ($data['datos']->result() as $key => $value) {
                $array_data[] = [
                    str_pad($value->id_propiedad, 5, "0", STR_PAD_LEFT),
                    $value->titulo,
                    ( $value->tipo_propiedad == 'I' ? 'Inmueble' : 'Terreno' ),
                    ( $value->clasificacion == 'C' ? 'Ciudad' : 'Playa' ),
                    '$ '. number_format( $value->precio, 2, '.', ',' ),
                    ( $value->tipo_publicacion == 'V' ? 'Venta' : 'Renta' ),                             
                    ( $value->activo ? 'Disponible' : 'No Disponible' )
                ]; 
            }
        }
        $nombre_hoja = 'REPORTE PROPIEDADES';
        $filename = 'REPORTE_PROPIEDADES';
        $excel_b64 = reporte_excel($columnas, $encabezado, $array_data, $nombre_hoja, $filename);

        $opResult = array(
            'status' => 1,
            'nombre' => $filename,
            'data'=>"data:application/vnd.ms-excel;base64,".$excel_b64            
        );

        exit (json_encode($opResult));
    }
}