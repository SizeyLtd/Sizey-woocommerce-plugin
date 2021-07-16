function openSizeyVroomPopup(sizey_api_key='',  product_id, garment_id, sizey_data)
{
    sizey_data = JSON.parse(decodeURIComponent(sizey_data));
    sizey_api_key = sizey_api_key.trim();
    product_id = product_id.trim();
    sessionStorage.setItem( 'sizey-product-id', product_id );
    let unique_id = uuidv4();
    if (!sessionStorage.getItem('unique-id')) {
        sessionStorage.setItem('unique-id', unique_id )
    }
    if (sizey_api_key!='') {
        let wnd = window.open("https://recommendation.sizey.ai/?apikey=" + sizey_api_key +"&measureOnly=true", 'popup', 'width=800,height=800,scrollbars=yes,resizable=yes');

        window.onmessage = function (e) {
            let sizeyEvent = e.data;
            if (sizeyEvent.event === 'sizey-measurements') {
                let recommendationjson = {};
                recommendationjson['bodyMeasurements'] = sizeyEvent.measurements;
                recommendationjson['sizeChart'] = sizey_data;
                sessionStorage.setItem("event_measurement_data", JSON.stringify(recommendationjson));

                let sizey_measurement_id = sizeyEvent.measurements;
                sessionStorage.setItem( 'sizey-measurement-id', sizey_measurement_id.measurementId );
                sessionStorage.setItem( 'gender', sizey_measurement_id.gender );

                endPointURL = "https://vroom-api.sizey.ai/my-avatar?measurementId="+sizey_measurement_id.measurementId+"&gender="+sizey_measurement_id.gender;
                let jsonData={
                    "sessionId": sessionStorage.getItem('unique-id'),
                    "productId":  product_id,
                    "measurementId": sessionStorage.getItem('sizey-measurement-id')
                }
                getSizesFromRecommendation("https://recommendation-api.sizey.ai/recommendations", recommendationjson, sizey_api_key, product_id);


                callRecommendationAPIForVroom("https://analytics-api-dot-sizey-ai.appspot.com/recommendation", jsonData, sizey_api_key);



                callGetAPI(endPointURL, garment_id);
                call_realtime_vroom_button(sessionStorage.getItem('unique-id'), sessionStorage.getItem('recommended_garment_size_'+product_id), product_id);

            }
        };
    }
    return false;
}
const changeAvatarActionUsingJs = (id) => ({
    action: "CHANGE_AVATAR",
    payload: { id },
});
const changeGarmentActionUsingJs = (id) => ({
action: "CHANGE_GARMENT",
payload: {id}
});
function loadModelinIframeUsingMainJs(garment_id) {
		let model_id = sessionStorage.getItem('model-id');
		if(!model_id) {
			model_id = 'f_sz_mid_n38';
		}
			document.getElementById("vroom_iframe").contentWindow.postMessage(
                changeAvatarActionUsingJs(
                    model_id,
                ),
                "*"
            );
			document.getElementById("vroom_iframe").contentWindow.postMessage(
                changeGarmentActionUsingJs(
                    garment_id
                ),
                "*"
            );

}

function openRecommendationPopup(apikey, chartId, productId) {
    const storedMeasurementId = sessionStorage.getItem("sizey-measurement-id");
    let url = "https://recommendation.sizey.ai/?apikey=" + apikey + "&chartId=" + chartId + "&measurementId=" +
        storedMeasurementId;
    let wnd = window.open(url, 'popup', 'width=800,height=800,scrollbars=yes,resizable=yes');
    window.onmessage = function (e) {
        let sizeyEvent = e.data;
        if (sizeyEvent.event === "sizey-recommendation") {
            let sizeyRecommendation = sizeyEvent.recommendation;
            let unique_id = uuidv4();
            if (!sessionStorage.getItem('unique-id')) {
                sessionStorage.setItem('unique-id', unique_id )
            }

            if(!sessionStorage.getItem("sizey-recommendation_" + productId)) {
                sessionStorage.setItem("sizey-recommendation_" + productId, JSON.stringify(sizeyRecommendation) )
            }

            let jsonData={
                "sessionId": sessionStorage.getItem('unique-id'),
                "productId":  productId,
                "measurementId": sessionStorage.getItem('sizey-measurement-id')
            }
            callRecommendationAPIForVroom("https://analytics-api-dot-sizey-ai.appspot.com/recommendation", jsonData, apikey);
            //call_realtime_button(unique_id, JSON.stringify(sizeyRecommendation));
        }
        if (sizeyEvent.event === 'sizey-measurements') {
            let sizey_measurement_id = sizeyEvent.measurements;
            sessionStorage.setItem('sizey-measurement-id', sizey_measurement_id.measurementId );
        }


    };

}


function openSizeyPopupViaVroom(sizey_api_key='', garmentId='', product_id)
{
    if (sizey_api_key!=='' && garmentId!=='') {

        fetch('https://vroom-api.sizey.ai/garments/' + garmentId).then(o => o.json()).then(garment => {
            if(!garment.sizeChartId) {
                // show some error?

            } else {
                openRecommendationPopup(sizey_api_key, garment.sizeChartId, product_id);

            }
        })
    }
}

function getSizesFromRecommendation(endpointURL, jSONdata, sizey_key, product_id) {
    jSONdata = JSON.stringify(jSONdata);
    jQuery.ajax({
        type: "POST",
        url: endpointURL,
        data: jSONdata,
        success: function (response) {
            jsondata = response;
            let score = 0;
            size = '';
            for (i=0; i < jsondata.length; i++) {
                if(jsondata[i]['score'] > score) {
                    score = jsondata[i]['score'];
                    size = jsondata[i]['size'];
                }
            }
            sessionStorage.setItem('recommended_garment_size_'+product_id, size);
            return size;

        },
        error: function (jqXHR, textStatus, errorThrown) {
        },
        contentType: 'application/json',
        headers: {
            'x-sizey-key': sizey_key,

        },

    });

}

function callRecommendationAPIForVroom(endpointURL, jSONdata, sizey_key)
{

    jSONdata = JSON.stringify(jSONdata);
    jQuery.ajax({
        type: "POST",
        url: endpointURL,
        data: jSONdata,
        success: function (response) {
            return true;
        },
        error: function (jqXHR, textStatus, errorThrown) {
        },
        contentType: 'application/json',
        headers: {
            'x-sizey-key': sizey_key,

        },

    });
}


function callGetAPI(endpointURL, garment_id)
{
    jQuery.ajax({
        type: "GET",
        url: endpointURL,
        success: function (response) {
            sessionStorage.setItem( 'model-id', response.id );
            loadModelinIframeUsingMainJs(garment_id)
            return true;
        },
        error: function (jqXHR, textStatus, errorThrown) {
        },
        contentType: 'application/json',


    });
}

jQuery(window).load(function(){


});
