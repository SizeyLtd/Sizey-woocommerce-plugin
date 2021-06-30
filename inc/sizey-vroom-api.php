<?php

function get_vroom_sizey_chart_data() {
	$sizey_api_key = get_option('vroom-sizey-api-key');
	$url = 'https://recommendation-api.sizey.ai/sizecharts';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'x-sizey-key: "' . $sizey_api_key . '"'
	));
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$results = curl_exec($ch);
	curl_close($ch);
	$size_chart_data =array();

	if ($results) {
		$size_chart_data = json_decode($results, true);
	}

	return $size_chart_data;
}


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

function get_all_garment_specific_sizes() {
	$url = 'https://vroom-api.sizey.ai/garments/';
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
	$garment_specific_size =array();
	foreach ($garments_data as $garment_data) {
			$garmentSpecificSizes = $garment_data['sizes'];
		if (isset($garmentSpecificSizes) && count($garmentSpecificSizes) > 0) {
			foreach ($garmentSpecificSizes as $individualGarmentSpecificSize) {
				$garment_specific_size[] = $individualGarmentSpecificSize['id'] . '-:-' . $individualGarmentSpecificSize['name'];
			}
		}
	}
	return array_unique($garment_specific_size);
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
