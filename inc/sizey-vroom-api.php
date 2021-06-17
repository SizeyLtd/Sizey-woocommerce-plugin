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
	//printr($garments_data_to_return);
	return $garments_data_to_return;
}
