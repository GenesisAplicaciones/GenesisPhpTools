<?php

namespace GenesisPhpTools\Comprobantes;

use GenesisPhpTools\Utils\Helpers;
use \stdClass;
use \SimpleXMLElement;

class Lector
{
    private $cfdi, $xml;
    public function __construct()
    {
    }

    /**
     * Permite de manera sencilla y customizable convertir en un arreglo los atributos de un nodo o de un subnodo (o grupo de subnodos) especificados con un selector xpath.
     * @param $nodo Objecto tipo SimpleXMLElement que representa un nodo del archivo xml.
     * @param $path Selector xpath para aplicar sobre el nodo especificado en el primer argumento. Si se omite o se pone en false, se leen los atributos del nodo del primer argumento.
     * @param $nodo_es_multiple Bandera para indicar si el nodo a leer tiene multiples resultados (para lo que se devolvera un array que a su vez contiene subarrays con los atributos de cada uno).
     */
    private function leer_atributos($nodo, $xpath = false, $nodo_es_multiple = false)
    {
        $atributos = [];
        // si se proporciona un $xpath, se intentará buscar ese xpath en el $nodo especificado
        if ($xpath) {
            if (is_array($nodo) && $nodo[0]) { // si el argumento $nodo es en realidad un array de nodos, el $xpath proporcionado se busca por defecto en el primer hijo
                $nodo_a_leer = $nodo[0]->xpath($xpath);
            } else if (is_object($nodo)) { // si el argumento $nodo es un objeto, se busca el $xpath proporcionado directamente sobre él.
                $nodo_a_leer = $nodo->xpath($xpath);
            } else { //si no, se regresa NULL, pues no será posible leer ningún atributo
                return [];
            }
        } else { // si no se especifica una ruta $xpath, el nodo a leer será el $nodo pasado como argumento
            $nodo_a_leer = $nodo;
        }

        if (!$nodo_a_leer) {
            return [];
        } // si no hay nodo a leer, entonces no hay atributos que leer, por lo que se sale de la funcion

        // si se envió $nodo_es_multiple = true se espera el nodo a leer tenga múltiples hijos cada uno con su set de atributos
        if ($nodo_es_multiple) {
            // entonces se lee cada uno de los sets de atributos y se regresa como un array de sets de atributos (donde a su vez cada set es un array)
            foreach ($nodo_a_leer as $i => $subnodo) {
                foreach ($subnodo->attributes() as $key => $value) {
                    $atributos[$i][$key] = Helpers::fix_utf8($value->__toString());
                }
            }
        } else {
            // si se envió $nodo_es_multiple = false, se espera que el nodo a leer tenga un solo hijo
            // entonces se leen los atributos de ese único hijo y se devuelve un array de atributos
            foreach ($nodo_a_leer[0]->attributes() as $key => $value) {
                $atributos[$key] = Helpers::fix_utf8($value->__toString());
            }
        }
        return $atributos ?: [];
    }

    public function leer_xml($cadena_xml)
    {
        $this->cfdi = new stdClass();
        //* Cuando es un string XML traido de la BD, hay que remplazar los siguientes caracteres
        /**
         * ? Poniendo temporalmente un error handler custom para que los warnings que se
         * ? generan abajo se vuelvan excepciones y pueda usarse un bloque try-catch
         */
        Helpers::set_strict_error_handler();
        try {
            $cadena_filtrada = str_replace(array("\\\quot;", "&#10;", "\\n", "\\r", "\\\""), array("\quot;", " ", "", "", '"'), utf8_decode($cadena_xml));
            $this->xml = simplexml_load_string($cadena_filtrada);
        } catch (\Throwable $th) {
            $cadena_filtrada = str_replace(array("\\\quot;", "&#10;", "\\n", "\\r", "\\\""), array("\quot;", " ", "", "", '"'), $cadena_xml);
            $this->xml = simplexml_load_string($cadena_filtrada);
        }
        restore_error_handler(); //? restaurando el error handler por defecto
        if ($this->xml) {
            //NODO COMPROBANTE
            $this->xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/3');
            $this->xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
            $this->leer_comprobante();
            $this->leer_cfdis_relacionados();
            $this->leer_emisor();
            $this->leer_receptor();
            $this->leer_conceptos();
            $this->leer_impuestos_generales();
            $this->leer_nodo_timbraxml();
            if ($this->xml->xpath('//cfdi:Comprobante/cfdi:Complemento')) {
                $this->cfdi->Complemento = new stdClass();
                $this->leer_impuestos_locales();
                $this->leer_comercio_exterior();
                $this->leer_ine();
                $this->leer_pagos();
                $this->leer_timbre_fiscal();
                $this->leer_parcialesconstruccion();
            }

            return $this->cfdi;
        }
        return FALSE;
    }

    private function leer_comprobante()
    {
        $comprobante = $this->xml->xpath('//cfdi:Comprobante');
        foreach ($comprobante[0]->attributes() as $key => $value) {
            $this->cfdi->{$key} = Helpers::fix_utf8($value->__toString());
        }
    }
    private function leer_cfdis_relacionados()
    {
        $cfdis = $this->xml->xpath('//cfdi:Comprobante/cfdi:CfdiRelacionados');
        $atributos = array();
        if ($cfdis) {
            foreach ($cfdis[0]->attributes() as $key => $value) {
                $atributos[$key] = $value->__toString();
            }
            $cfdis_rel = $cfdis[0]->xpath('cfdi:CfdiRelacionado');
            if ($cfdis_rel) {
                foreach ($cfdis_rel as $key => $value) {
                    foreach ($value->attributes() as $key_att => $value_att) {
                        $atributos['CfdiRelacionado'][] = [$key_att => $value_att->__toString()];
                    }
                }
            }
            $this->cfdi->CfdiRelacionados =  (object) $atributos;
        }
    }
    private function leer_emisor()
    {
        $emisor = $this->xml->xpath('//cfdi:Comprobante/cfdi:Emisor');
        $atributos = array();
        foreach ($emisor[0]->attributes() as $key => $value) {
            $atributos[$key] = Helpers::fix_utf8($value->__toString());
        }
        $this->cfdi->Emisor = (object) $atributos;
    }

    private function leer_receptor()
    {
        $receptor = $this->xml->xpath('//cfdi:Comprobante/cfdi:Receptor');
        $atributos = array();
        foreach ($receptor[0]->attributes() as $key => $value) {
            $atributos[$key] = Helpers::fix_utf8($value->__toString());
        }
        $this->cfdi->Receptor = (object) $atributos;
    }
    private function leer_conceptos()
    {
        $conceptos = $this->xml->xpath('//cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto');
        foreach ($conceptos as $key => $value) {
            $atributos = array();
            foreach ($value->attributes() as $key_att => $value_att) {
                $atributos[$key_att] = $value_att->__toString();
            }

            $traslados = $value->xpath('cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado');
            if ($traslados) {
                $atributos['Traslados'] = $this->leer_traslados_concepto($value);
            }
            $retenciones = $value->xpath('cfdi:Impuestos/cfdi:Retenciones/cfdi:Retencion');
            if ($retenciones) {
                $atributos['Retenciones'] = $this->leer_retenciones_concepto($value);
            }
            $atributos['InformacionAduanera'] = $this->leer_atributos($value, 'cfdi:InformacionAduanera', true);
            $arreglo_conceptos[] = $atributos;
        }
        $this->cfdi->Conceptos = (object) $arreglo_conceptos;
    }
    private function leer_traslados_concepto($concepto)
    {
        $traslados = $concepto->xpath('cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado');

        if ($traslados) {
            foreach ($traslados as $key => $value) {
                $atributos = array();
                foreach ($value->attributes() as $key_att => $value_att) {
                    $atributos[$key_att] = $value_att->__toString();
                }
                $arreglo_traslados[] = $atributos;
            }
            return $arreglo_traslados;
        }
        return NULL;
    }
    private function leer_retenciones_concepto($concepto)
    {
        $retenciones = $concepto->xpath('cfdi:Impuestos/cfdi:Retenciones/cfdi:Retencion');
        if ($retenciones) {
            foreach ($retenciones as $key => $value) {
                $atributos = array();
                foreach ($value->attributes() as $key_att => $value_att) {
                    $atributos[$key_att] = $value_att->__toString();
                }
                $arreglo_retenciones[] = $atributos;
            }
            return $arreglo_retenciones;
        }
    }
    private function leer_impuestos_generales()
    {
        $impuestos = $this->xml->xpath('//cfdi:Comprobante/cfdi:Impuestos');
        if ($impuestos) {
            $atributos = array();
            foreach ($impuestos[0]->attributes() as $key => $value) {
                $atributos[$key] = $value->__toString();
            }
            $traslados = $impuestos[0]->xpath('cfdi:Traslados/cfdi:Traslado');
            if ($traslados) {
                foreach ($traslados as $key => $value) {
                    $atributos_traslados = array();
                    foreach ($value->attributes() as $key_att => $value_att) {
                        $atributos_traslados[$key_att] = $value_att->__toString();
                    }
                    $atributos['Traslados'][] = (object) $atributos_traslados;
                }
            }
            $retenciones = $impuestos[0]->xpath('cfdi:Retenciones/cfdi:Retencion');
            if ($retenciones) {
                foreach ($retenciones as $key => $value) {
                    $atributos_retenciones = array();
                    foreach ($value->attributes() as $key_att => $value_att) {
                        $atributos_retenciones[$key_att] = $value_att->__toString();
                    }
                    $atributos['Retenciones'][] = (object) $atributos_retenciones;
                }
            }
            $this->cfdi->Impuestos = (object) $atributos;
        }
    }
    private function leer_impuestos_locales()
    {
        $this->xml->registerXPathNamespace('implocal', 'http://www.sat.gob.mx/implocal');
        $impuestos_locales = $this->xml->xpath('//cfdi:Comprobante/cfdi:Complemento/implocal:ImpuestosLocales');
        $atributos = [];
        if ($impuestos_locales) {
            foreach ($impuestos_locales[0]->attributes() as $key => $value) {
                $atributos[$key] = $value->__toString();
            }
            $traslados_locales = $impuestos_locales[0]->xpath('implocal:TrasladosLocales');
            if ($traslados_locales) {
                foreach ($traslados_locales as $key => $value) {
                    foreach ($value->attributes() as $key_att => $value_att) {
                        $atributos_traslados[$key_att] = $value_att->__toString();
                    }
                    $atributos['Traslados'][] = $atributos_traslados;
                }
            }

            $retenciones_locales = $impuestos_locales[0]->xpath('implocal:RetencionesLocales');
            if ($retenciones_locales) {
                foreach ($retenciones_locales as $key => $value) {
                    foreach ($value->attributes() as $key_att => $value_att) {
                        $atributos_retenciones[$key_att] = $value_att->__toString();
                    }
                    $atributos['Retenciones'][] = $atributos_retenciones;
                }
            }
            $this->cfdi->Complemento->ImpuestosLocales = (object) $atributos;
        }
    }
    private function leer_comercio_exterior()
    {
        $this->xml->registerXPathNamespace('cce11', 'http://www.sat.gob.mx/ComercioExterior11');
        // cargando el nodo principal
        $comercio_exterior = $this->xml->xpath('//cfdi:Comprobante/cfdi:Complemento/cce11:ComercioExterior');
        if ($comercio_exterior) {
            $atributos = [];
            // leyendo los atributos del nodo principal
            $atributos = $this->leer_atributos($comercio_exterior);
            // leyendo el subnodo ComercioExterior->Emisor
            $atributos['Emisor'] = $this->leer_atributos($comercio_exterior, 'cce11:Emisor');
            $atributos['Emisor']['Domicilio'] =  $this->leer_atributos($comercio_exterior, 'cce11:Emisor/cce11:Domicilio');
            // leyendo el subnodo ComercioExterior->Receptor->Domicilio
            $atributos['Receptor'] = $this->leer_atributos($comercio_exterior, 'cce11:Receptor');
            $atributos['Receptor']['Domicilio'] = $this->leer_atributos($comercio_exterior, 'cce11:Receptor/cce11:Domicilio');
            // leyendo el subnodo ComercioExterior->Destinatario
            // Pueden haber multiples destinatarios que a su vez pueden tener multiples domicilios
            foreach ($comercio_exterior[0]->xpath('cce11:Destinatario') as $i => $destinatario) {
                $atributos['Destinatario'][$i] = $this->leer_atributos($destinatario);
                $atributos['Destinatario'][$i]['Domicilio'] = $this->leer_atributos($destinatario, 'cce11:Domicilio', true);
            }
            // leyendo el subnodo ComercioExterior->Propietario
            $atributos['Propietario'] = $this->leer_atributos($comercio_exterior, 'cce11:Propietario', true);
            // leyendo el subnodo ComercioExterior->Mercancias
            foreach ($comercio_exterior[0]->xpath('cce11:Mercancias/cce11:Mercancia') as $i => $mercancia) {
                $atributos['Mercancias'][$i] = $this->leer_atributos($mercancia);
                $atributos['Mercancias'][$i]['DescripcionesEspecificas'] = $this->leer_atributos($mercancia, 'cce11:DescripcionesEspecificas', true);
            }
            $this->cfdi->Complemento->ComercioExterior = (object) $atributos;
        }
    }
    private function leer_ine()
    {
        $this->xml->registerXPathNamespace('ine', 'http://www.sat.gob.mx/ine');
        $ine = $this->xml->xpath('//cfdi:Comprobante/cfdi:Complemento/ine:INE');
        $atributos = [];
        if ($ine) {
            $atributos = $this->leer_atributos($ine);
            if ($entidades = $ine[0]->xpath('ine:Entidad')) {
                foreach ($entidades as $i => $entidad) {
                    $atributos['Entidad'][$i] = $this->leer_atributos($entidad);
                    $atributos['Entidad'][$i]['Contabilidad'] = $this->leer_atributos($entidad, 'ine:Contabilidad', true);
                }
            }
            $this->cfdi->Complemento->Ine = (object) $atributos;
        }
    }
    private function leer_parcialesconstruccion()
    {
        $this->xml->registerXPathNamespace('servicioparcial', 'http://www.sat.gob.mx/servicioparcialconstruccion');
        // cargando el nodo principal
        $parcialesconstruccion = $this->xml->xpath('//cfdi:Comprobante/cfdi:Complemento/servicioparcial:parcialesconstruccion');
        if ($parcialesconstruccion) {
            $atributos = [];
            // leyendo los atributos del nodo principal
            foreach ($parcialesconstruccion[0]->attributes() as $key => $value) {
                $atributos[$key] = $value->__toString();
            }
            // cargando el subnodo de inmueble
            $inmueble =  $parcialesconstruccion[0]->xpath('servicioparcial:Inmueble');
            foreach ($inmueble[0]->attributes() as $key => $value) {
                $atributos[$key] = $value->__toString();
            }
            $this->cfdi->Complemento->ParcialesConstruccion = (object) $atributos;
        }
    }
    private function leer_timbre_fiscal()
    {
        $timbre_fiscal = $this->xml->xpath('//cfdi:Comprobante/cfdi:Complemento/tfd:TimbreFiscalDigital');
        if ($timbre_fiscal) {
            $atributos = array();
            foreach ($timbre_fiscal[0]->attributes() as $key => $value) {
                $atributos[$key] = $value->__toString();
            }
            $this->cfdi->Complemento->TimbreFiscalDigital = (object) $atributos;
        }
    }
    private function leer_nomina()
    {
        $this->xml->registerXPathNamespace('nomina12', 'http://www.sat.gob.mx/nomina12');
        $nomina = $this->xml->xpath('//cfdi:Comprobante/cfdi:Complemento/nomina12:Nomina');
        if ($nomina) {
            foreach ($nomina[0]->attributes() as $key => $value) {
                $atributos[$key] = $value->__toString();
            }
            $emisor = $nomina[0]->xpath('nomina12:Emisor');
            if ($emisor) {
                foreach ($emisor[0]->attributes() as $key => $value) {
                    $atributos['Emisor'][$key] = $value->__toString();
                }
                $entidad_sncf = $emisor[0]->xpath('nomina12:EntidadSNCF');
                if ($entidad_sncf) {
                    foreach ($entidad_sncf[0]->attributes() as $key => $value) {
                        $atributos['Emisor']['EntidadSNCF'][$key] = $value->__toString();
                    }
                }
            }

            $receptor = $nomina[0]->xpath('nomina12:Receptor');
            foreach ($receptor[0]->attributes() as $key => $value) {
                $atributos['Receptor'][$key] = $value->__toString();
            }

            $subcontratacion = $receptor[0]->xpath('nomina12:SubContratacion');
            if ($subcontratacion) {
                foreach ($subcontratacion as $key => $value) {
                    foreach ($value->attributes() as $key_att => $value_att) {
                        $atributos['Receptor']['SubContratacion'][$key][$key_att] = $value_att->__toString();
                    }
                }
            }

            $percepciones = $this->leer_nomina_percepiones($nomina[0]);
            if ($percepciones) {
                $atributos['Percepciones'] = $percepciones;
            }


            $deducciones = $nomina[0]->xpath('nomina12:Deducciones');
            if ($deducciones) {
                foreach ($deducciones[0]->attributes() as $key => $value) {
                    $atributos['Deducciones'][$key] = $value->__toString();
                }
                $deduccion = $nomina[0]->xpath('nomina12:Deduccion');
                foreach ($deduccion as $key => $value) {
                    foreach ($value->attributes() as $key_att => $value_att) {
                        $atributos['Deducciones']['Deduccion'][$key][$key_att] = $value_att;
                    }
                }
            }
            $otros_pagos = $nomina[0]->xpath('nomina12:OtrosPagos');
            if ($otros_pagos) {
                foreach ($otros_pagos[0]->attributes() as $key => $value) {
                    $atributos['OtrosPagos'][$key] = $value->__toString();
                }
                $otro_pago = $otros_pagos[0]->xpath('nomina12:OtroPago');
                foreach ($otro_pago as $key => $value) {
                    foreach ($otro_pago->attributes() as $key_att => $value_att) {
                        $atributos['OtrosPagos']['OtroPago'][$key][$key_att] = $value_att->__toString();
                    }
                    $subsidio = $value->xpath('nomina12:SubsidioAlEmpleo');
                    if ($subsidio) {
                        foreach ($subsidio->attributes() as $key_att => $value_att) {
                            $attributes['OtrosPagos']['OtroPago'][$key]['SubsidioAlEmpleo'][$key_att] = $value_att->__toString();
                        }
                    }
                    $compensacion = $value->xpath('nomina12:CompensacionSaldosAFavor');
                    if ($compensacion) {
                        foreach ($compensacion->attributes() as $key_att => $value_att) {
                            $attributes['OtrosPagos']['OtroPago'][$key]['CompensacionSaldosAFavor'][$key_att] = $value_att->__toString();
                        }
                    }
                }
            }
            $incapacidadades = $nomina[0]->xpath('nomina12:Incapacidades');
            if ($incapacidadades) {
                $incapacidadad = $incapacidadades[0]->xpath('nomina12:Incapacidad');
                foreach ($incapacidadad as $key => $value) {
                    foreach ($value->attributes() as $key_att => $value_att) {
                        $atributos['Incapacidades']['Incapacidad'][$key][$key_att] = $value_att->__toString();
                    }
                }
            }
            $this->cfdi->Complemento->Nomina = (object) $atributos;
        }
    }

    private function leer_nomina_percepiones($nodo_nomina)
    {
        $percepciones = $nodo_nomina->xpath('nomina12:Percepciones');
        if ($percepciones) {
            foreach ($percepciones[0]->attributes() as $key => $value) {
                $atributos[$key] = $value->__toString();
            }

            $percepcion = $percepciones[0]->xpath('nomina12:Percepcion');
            foreach ($percepcion as $key => $value) {
                foreach ($value as $key_att => $value_att) {
                    $atributos['Percepcion'][$key][$key_att] = $value_att->__toString();
                }
                $acciones_titulos = $value[0]->xpath('nomina12:AccionesOTitulos');
                if ($acciones_titulos) {
                    foreach ($acciones_titulos[0]->attributes() as $key_att => $value_att) {
                        $atributos['Percepcion'][$key]['AccionesOTitulos'][$key_att] = $value_att->__toString();
                    }
                }
                $horas_extra = $value[0]->xpath('nomina12:HorasExtra');
                if ($horas_extra) {
                    foreach ($horas_extra as $key_nodo => $value_nodo) {
                        foreach ($value_nodo->attributes() as $key_att => $value_att) {
                            $atributos['Percepcion'][$key]['HorasExtra'][$key_nodo][$key_att] = $value_att->__toString();
                        }
                    }
                }
            }
            $jubilacion_pension = $percepciones[0]->xpath('nomina12:JubilacionPensionRetiro');
            if ($jubilacion_pension) {
                foreach ($jubilacion_pension[0]->attributes() as $key => $value) {
                    $atributos['JubilacionPensionRetiro'][$key] = $value->__toString();
                }
            }
            $separacion = $percepciones[0]->xpath('nomina12:SeparacionIndemnizacion');
            if ($separacion) {
                foreach ($percepciones[0]->attributes() as $key => $value) {
                    $attributes['SeparacionIndemnizacion'][$key] = $value->__toString();
                }
            }
            return $atributos;
        }
        return FALSE;
    }
    private function leer_pagos()
    {
        $this->xml->registerXPathNamespace('pago10', 'http://www.sat.gob.mx/Pagos');
        $pagos = $this->xml->xpath('//cfdi:Comprobante/cfdi:Complemento/pago10:Pagos');
        if ($pagos) {
            $atributos = new stdClass();
            foreach ($pagos[0]->attributes() as $key => $value) {
                $atributos->{$key} = $value->__toString();
            }
            $pago = $pagos[0]->xpath('pago10:Pago');
            $atributos->Pago = [];
            foreach ($pago as $key => $value) {
                $atributos->Pago[$key] = new stdClass();
                foreach ($value->attributes() as $key_att => $value_att) {
                    $atributos->Pago[$key]->{$key_att} = $value_att->__toString();
                }
                $dr = $this->leer_documentos_relacionados($pago[$key]);
                if ($dr) {
                    $atributos->Pago[$key]->DoctoRelacionado = $dr;
                }
            }
            $this->cfdi->Complemento->Pagos = (object) $atributos;
        }
    }

    private function leer_documentos_relacionados($pago)
    {
        $dr = $pago->xpath('pago10:DoctoRelacionado');
        if ($dr) {
            $atributos = [];
            foreach ($dr as $key => $value) {
                $atributos[$key] = new stdClass();
                foreach ($value->attributes() as $key_att => $value_att) {
                    $atributos[$key]->{$key_att} = $value_att->__toString();
                }
            }
            return $atributos;
        }
        return FALSE;
    }

    /**
     * Sirve para leer el nodo timbra automaticamente al ejecutar la funcion leer_xml().
     * Y tambien se puede usar independientemente para leer el xml del nodo timbra que se guarda aparte en la BD al timbrar.
     */
    public function leer_nodo_timbraxml($cadena_xml_nodo_timbra = null)
    {
        // si se manda una cadena xml como parametro, se lee esa cadena
        $xml_obj_lectura = $cadena_xml_nodo_timbra ? simplexml_load_string($cadena_xml_nodo_timbra) : $this->xml;
        // se busca el nodo timbra en el objeto del xml
        $nodo_timbra = $xml_obj_lectura->xpath('//TIMBRAXML');
        if (!$nodo_timbra) {
            return NULL;
        } // si no hay nodo timbra, no hacer nada
        // creando array donde se guardaran los datos del del nodo TIMBRAXML
        $NODO_TIMBRAXML = [];
        // leyendo observaciones
        $nodo_observacion = $nodo_timbra[0]->xpath('//TIMBRAXML/observacion');
        $cadena_observaciones = $nodo_observacion ? (string)$nodo_observacion[0] : null; // si es nulo, no guardar nada;
        $NODO_TIMBRAXML['observacion'] = $cadena_observaciones;
        $this->cfdi->Observaciones = $cadena_observaciones; // guardandolo tambien aqui porque de aqui se lee en el frontend como lo hizo Carlos
        // leyendo datos de la empresa
        $nodo_empresa = $nodo_timbra[0]->xpath('//TIMBRAXML/empresa');
        $NODO_TIMBRAXML['empresa'] = $this->leer_atributos($nodo_empresa);
        // guardando todo en el objeto TIMBRAXML
        $OBJ_TIMBRAXML = (object)$NODO_TIMBRAXML;
        $this->cfdi->TIMBRAXML = $OBJ_TIMBRAXML;
        return $OBJ_TIMBRAXML; // retornando el $OBJ_TIMBRAXML para cuando se use la funcion independientemente.
    }

    public function leer_retencion(String $xml_str)
    {
        // declarando valores por defecto a usarse
        $atributos_basicos = [
            'Raiz' => [
                // Atributos constantes con valor por defecto
                'Version' => '1.0',
                'Sello' => '0',
                'Cert' => '0',
                'NumCert' => '00000000000000000000',
                //
                'FolioInt' => '',
                'FechaExp' => '',
                'CveRetenc' => '',
                'DescRetenc' => '',
            ],
            'Emisor' => [
                'RFCEmisor' => '',
                'NomDenRazSocE' => '',
                'CURPE' => '',
            ],
            'Receptor' => [
                'Nacionalidad' => '',
            ],
            'Receptor:Nacional' => [
                'RFCRecep' => '',
                'NomDenRazSocR' => '',
                'CURPR' => '',
            ],
            'Receptor:Extranjero' => [
                'NumRegIdTrib' => '',
                'NomDenRazSocR' => '',
            ],
            'Periodo' => [
                'MesIni' => '',
                'MesFin' => '',
                'Ejerc' => '',
            ],
            'Totales' => [
                'montoTotOperacion' => '',
                'montoTotGrav' => '',
                'montoTotExent' => '',
                'montoTotRet' => '',
            ],
        ];

        $dividendos = [
            'Raiz' => [
                // Atributos constantes
                'Version' => '1.0',
                //
            ],
            'DividOUtil' => [
                'CveTipDivOUtil' => '',
                'MontISRAcredRetMexico' => '',
                'MontISRAcredRetExtranjero' => '',
                'MontRetExtDivExt' => '',
                'TipoSocDistrDiv' => '',
                'MontISRAcredNal' => '',
                'MontDivAcumNal' => '',
                'MontDivAcumExt' => '',
            ],
            'Remanente' => [
                'ProporcionRem' => '',
            ],
        ];

        $enajenacion = [
            'Raiz' => [
                // Atributos constantes
                'Version' => '1.0',
                //
                'ContratoIntermediacion' => '',
                'Ganancia' => '',
                'Perdida' => '',
            ],
        ];

        $timbrefiscaldigital = [
            'Raiz' => [
                'selloCFD' => '',
                'noCertificadoSAT' => '',
                'selloSAT' => '',
                'version' => '',
                'UUID' => '',
                'FechaTimbrado' => '',
            ],
        ];

        // creando la clase a retornar
        $retencion = new stdClass();
        // cuando es un string XML traido de la BD, hay que remplazar los siguientes caracteres
        $xml_str = str_replace(array("\\\quot;", "&#10;", "\\n", "\\r", "\\\""), array("\quot;", " ", "", "", '"'), $xml_str);
        $xml = new SimpleXMLElement($xml_str);
        // si no se pudo cargar el xml, regresar false
        if (!$xml) {
            return FALSE;
        }
        // registrando namespaces a utilizar
        $xml->registerXPathNamespace('retenciones', 'http://www.sat.gob.mx/esquemas/retencionpago/1');
        $xml->registerXPathNamespace('dividendos', 'http://www.sat.gob.mx/esquemas/retencionpago/1/dividendos');
        $xml->registerXPathNamespace('enajenaciondeacciones', 'http://www.sat.gob.mx/esquemas/retencionpago/1/enajenaciondeacciones');
        $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
        // leyendo nodo raiz
        $retencion = (object)array_merge( // usando array_merge para que cuando algo no esté definido en los atributos leidos, se usen los atributos por defecto del primer array
            $atributos_basicos["Raiz"],
            $this->leer_atributos($xml, '//retenciones:Retenciones')
        );
        // leyendo subnodos principales
        $retencion->Emisor = (object)array_merge(
            $atributos_basicos["Emisor"],
            $this->leer_atributos($xml, '//retenciones:Emisor')
        );
        $retencion->Receptor = (object)array_merge(
            $atributos_basicos["Receptor"],
            $this->leer_atributos($xml, '//retenciones:Receptor')
        );
        $retencion->Receptor->Nacional = (object)array_merge(
            $atributos_basicos["Receptor:Nacional"],
            $this->leer_atributos($xml, '//retenciones:Nacional')
        );
        $retencion->Receptor->Extranjero = (object)array_merge(
            $atributos_basicos["Receptor:Extranjero"],
            $this->leer_atributos($xml, '//retenciones:Extranjero')
        );
        $retencion->Periodo = (object)array_merge(
            $atributos_basicos["Periodo"],
            $this->leer_atributos($xml, '//retenciones:Periodo')
        );
        $retencion->Totales = (object)array_merge(
            $atributos_basicos["Totales"],
            $this->leer_atributos($xml, '//retenciones:Totales')
        );
        // leyendo complementos
        $retencion->Complemento = new StdClass();
        // leyendo dividendos
        if ($xpath_dividendos = $xml->xpath('//dividendos:Dividendos')) {
            $retencion->Complemento->Dividendos = (object)array_merge(
                $dividendos["Raiz"],
                $this->leer_atributos($xpath_dividendos)
            );
            $retencion->Complemento->Dividendos->DividOUtil = (object)array_merge(
                $dividendos["DividOUtil"],
                $this->leer_atributos($xpath_dividendos, '//dividendos:DividOUtil')
            );
            $retencion->Complemento->Dividendos->Remanente = (object)array_merge(
                $dividendos["Remanente"],
                $this->leer_atributos($xpath_dividendos, '//dividendos:Remanente')
            );
        } else {
            $retencion->Complemento->Dividendos = NULL;
        }
        // leyendo enajenacion de acciones
        if ($xpath_enajenacion = $xml->xpath('//enajenaciondeacciones:EnajenaciondeAcciones')) {
            $retencion->Complemento->EnajenaciondeAcciones = (object)array_merge(
                $enajenacion["Raiz"],
                $this->leer_atributos($xpath_enajenacion)
            );
        } else {
            $retencion->Complemento->EnajenaciondeAcciones = NULL;
        }
        // leyendo nodo  de acciones
        if ($xpath_timbrefiscaldigital = $xml->xpath('//tfd:TimbreFiscalDigital')) {
            $retencion->Complemento->TimbreFiscalDigital = (object)array_merge(
                $timbrefiscaldigital["Raiz"],
                $this->leer_atributos($xpath_timbrefiscaldigital)
            );
        } else {
            $retencion->Complemento->TimbreFiscalDigital = NULL;
        }

        // retornando la retencion leida
        return $retencion;
    }
}
