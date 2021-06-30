function openSizeyVroomPopup(sizey_api_key='',  product_id, garment_id)
{
    sizey_api_key = sizey_api_key.trim();
    product_id = product_id.trim();
    sessionStorage.setItem( 'sizey-product-id', product_id );
    let unique_id = generateuuid();
    if (!sessionStorage.getItem('unique-id')) {
        sessionStorage.setItem('unique-id', unique_id )
    }
    if (sizey_api_key!='') {
        let wnd = window.open("https://recommendation.sizey.ai/?apikey=" + sizey_api_key +"&measureOnly=true", 'popup', 'width=800,height=800,scrollbars=yes,resizable=yes');

        window.onmessage = function (e) {
            let sizeyEvent = e.data;
            console.log(sizeyEvent.measurement);
            sessionStorage.setItem("event_data", JSON.stringify(sizeyEvent.measurement));
            if (sizeyEvent.event === 'sizey-measurements') {
                let sizey_measurement_id = sizeyEvent.measurements;
                sessionStorage.setItem( 'sizey-measurement-id', sizey_measurement_id.measurementId );
                sessionStorage.setItem( 'gender', sizey_measurement_id.gender );
                endPointURL = "https://vroom-api.sizey.ai/my-avatar?measurementId="+sizey_measurement_id.measurementId+"&gender="+sizey_measurement_id.gender;
                let jsonData={
                    "sessionId": sessionStorage.getItem('unique-id'),
                    "productId":  product_id,
                    "measurementId": sessionStorage.getItem('sizey-measurement-id')
                }
                callRecommendationAPIForVroom("https://analytics-api-dot-sizey-ai.appspot.com/recommendation", jsonData, sizey_api_key);
                callGetAPI(endPointURL, garment_id);

            }
        };
    }
    return false;
}
const changeAvatarActionUsingJs = (id) => ({
    action: "CHANGE_AVATAR",
    payload: { id },
});
const changeGarmentActionUsingJs = (id, avatar) => ({
action: "CHANGE_GARMENT",
payload: {id, avatar}
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
                    garment_id,
                    model_id
                ),
                "*"
            );

}


function openSizeyPopupViaVroom(sizey_api_key='', chartId='', product_id)
{
    if (sizey_api_key!=='' && chartId!=='') {
        const storedMeasurementId = sessionStorage.getItem("sizey-measurement-id");
        let url = "https://recommendation.sizey.ai/?apikey=" + sizey_api_key + "&chartId=" + chartId + "&measurementId=" +
            storedMeasurementId;
        let wnd = window.open(url, 'popup', 'width=800,height=800,scrollbars=yes,resizable=yes');
        window.onmessage = function (e) {
            let sizeyEvent = e.data;
            if (sizeyEvent.event === "sizey-recommendation") {
                let sizeyRecommendation = sizeyEvent.recommendation;
                let unique_id = generateuuid();
                if (!sessionStorage.getItem('unique-id')) {
                    sessionStorage.setItem('unique-id', unique_id )
                }

                if(!sessionStorage.getItem("sizey-recommendation_" + product_id)) {
                    sessionStorage.setItem("sizey-recommendation_" + product_id, JSON.stringify(sizeyRecommendation) )
                }


                let jsonData={
                    "sessionId": sessionStorage.getItem('unique-id'),
                    "productId":  product_id,
                    "measurementId": sessionStorage.getItem('sizey-measurement-id')
                }
                callRecommendationAPIForVroom("https://analytics-api-dot-sizey-ai.appspot.com/recommendation", jsonData, sizey_api_key);
                //call_realtime_button(unique_id, JSON.stringify(sizeyRecommendation));
            }
            if (sizeyEvent.event === 'sizey-measurements') {
                let sizey_measurement_id = sizeyEvent.measurements;
                sessionStorage.setItem('sizey-measurement-id', sizey_measurement_id.measurementId );
            }


        };
    }
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
