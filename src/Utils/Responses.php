<?php

namespace GenesisPhpTools\Responses;

class Responses
{
    /**
     * Devuelve una respuesta en json de manera estandarizada.
     * @param mixed $status Puede ser TRUE para 'success', 'FALSE' para 'error, 'NULL' para 'info' o  o un string con un status específico.
     * @param string $status_code Código del status que se corresponde con un lang definido en codeigniter (usualmente en error_lang.php o mensajes_lang.php)
     * @param array $data Un arreglo con datos que se quieran adjuntar a la respuesta.
     * @param boolean $send_status_code Agregar el codigo de status a la respuesta. Puede servir para hacer validaciones en el JS.
     * 
     * @return string Cadena en formato json.
     */
    static function response_json($status, $status_code = null, $data = null, $send_status_code = false)
    {
        $mensaje_default = 'Operación finalizada';
        if ($status === FALSE) {
            $status = "error";
            $mensaje_default .= ' con errores.';
        }
        if ($status === TRUE) {
            $status = "success";
            $mensaje_default .= ' exitosamente!.';
        }
        if ($status === NULL) {
            $status = "info";
            $mensaje_default .= '.';
        }

        $mensaje = $status_code ?: $mensaje_default;

        $array = [
            'status' => $status,
            'mensaje' => $mensaje,
        ];
        if ($send_status_code) {
            $array['status_code'] = $status_code;
        }
        if ($data !== NULL && $data !== FALSE) {
            $array['data'] = $data;
        }

        header('Content-type:application/json;charset=utf-8');
        return json_encode($array);
    }

    /**
     * Devuelve una respuesta en json de manera estandarizada.
     * @param mixed $status Puede ser TRUE para 'success', FALSE para 'error, NULL para 'info' o un string con un status específico.
     * @param string $status_code Código del status que se corresponde con un lang definido en codeigniter (usualmente en error_lang.php o mensajes_lang.php)
     * @param array $data Un arreglo con datos que se quieran adjuntar a la respuesta.
     * @param boolean $send_status_code Agregar el codigo de status a la respuesta. Puede servir para hacer validaciones en el JS.
     * 
     * @return string Cadena en formato json.
     */
    static function return_json($status, $status_code = null, $data = null, $send_status_code = false)
    {
        exit(self::response_json($status, $status_code, $data, $send_status_code));
    }

    /**
     * Devuelve una respuesta en json de manera estandarizada.
     * @param array $data Un arreglo con datos que se quieran adjuntar a la respuesta.
     * @param mixed $status Puede ser TRUE para 'success', FALSE para 'error, NULL para 'info' o un string con un status específico.
     * @param string $status_code Código del status que se corresponde con un lang definido en codeigniter (usualmente en error_lang.php o mensajes_lang.php)
     * @param boolean $send_status_code Agregar el codigo de status a la respuesta. Puede servir para hacer validaciones en el JS.
     * 
     * @return string Cadena en formato json.
     */
    static function return_data($data, $status_code = null, $send_status_code = false)
    {
        $status = $data ? TRUE : FALSE;
        exit(self::response_json($status, $status_code ?: (!$data ? 'Sin resultados' : null), $data, $send_status_code));
    }
}
