<?php
function get_sizey_vroom_garment_data() {
	$url = 'https://vroom-api.sizey.ai/SXiKCZZxGrtnpbykpp0J/garments';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$results = curl_exec($ch);
	curl_close($ch);
	$garments_data = array();
	$garments_data_to_return = array();
	if ($results) {
		$garments_data = json_decode($results, true);
	}
	foreach ($garments_data as $garment_data) {
		$garments_data_to_return[$garment_data['id']] = $garment_data['name'];
	}

	return $garments_data_to_return;
}
/*
 * Get Photo URL and along with pose and path
 * */
function get_photourl_by_garment_id( $garment_id) {
	$modeldata = array();
	if ('' === $garment_id || null === $garment_id ) {
		return $modeldata;
	}
	$endpoint = 'https://vroom-api.sizey.ai/garments/' . $garment_id . '/dressed';
	$crl = curl_init();
	curl_setopt($crl, CURLOPT_URL, $endpoint);
	curl_setopt($crl, CURLOPT_FRESH_CONNECT, true);
	curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($crl);
	$response = json_decode($response, true);
	if (isset($response)) {
		foreach ($response as $individualDressedModel) {
			$modeldata[$individualDressedModel['id']]['photouri'] = $individualDressedModel['photoUri'];
			$modeldata[$individualDressedModel['id']][$individualDressedModel['pose']['_path']['segments'][0]] = $individualDressedModel['pose']['_path']['segments'][1];
			$modeldata[$individualDressedModel['id']][$individualDressedModel['pose']['_path']['segments'][2]] = $individualDressedModel['pose']['_path']['segments'][3];
		}
	}
	return $modeldata;
}


function call_post_api () {

}
