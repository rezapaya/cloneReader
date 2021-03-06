<?php
function array_to_select() {

	$args = func_get_args();

	$return = array();

	switch(count($args)) :

		case 3 :
			foreach ($args[0] as $itteration) :
				if (is_object($itteration))
					$itteration = (array)$itteration;
				$return[$itteration[$args[1]]] = $itteration[$args[2]];
			endforeach;
			break;

		case 2 :
			foreach ($args[0] as $key => $itteration) :
				if (is_object($itteration))
					$itteration = (array)$itteration;
				$return[$key] = $itteration[$args[1]];
			endforeach;
			break;

		case 1 :
			foreach ($args[0] as $itteration) :
				$return[$itteration] = $itteration;
			endforeach;
			break;

		default :
			return FALSE;
			break;
	endswitch;

	return $return;
}

// TODO: mover esto de aca!
function pr($value) {
	print_r($value);
}

function vd($value) {
	var_dump($value);
}

function errorForbidden() {
	$CI = &get_instance();
	$CI->load->library('../controllers/error');
	$CI->error->forbidden();	
}

function error404() {
	$CI = &get_instance();
	$CI->load->library('../controllers/error');
	$CI->error->error404();	
}

function loadViewAjax($code, $result = null) {
	$CI = &get_instance();
	
	return $CI->load->view('ajax', array(
		'code'		=> $code, 
		'result' 	=> $result != null ? $result : validation_errors() 
	));	
}

function formatCurrency($value, $currencyName = DEFAULT_CURRENCY_NAME) { 
	return $currencyName.' '.number_format($value, 2, ',', '.'); // TODO: desharckodaear!
}
