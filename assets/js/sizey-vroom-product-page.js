function openRecommendationPopup(apikey, productId) {
    const storedMeasurementId = sessionStorage.getItem("sizey-measurement-id");
    let url = "https://recommendation.sizey.ai?apikey=" + apikey + "&productId=" + productId + "&measurementId=" +
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
            console.log(sessionStorage.getItem('unique-id'), sessionStorage.getItem('recommended_garment_size_'+productId), productId);
            call_realtime_vroom_button(unique_id, sizeyRecommendation, productId);

            //call_realtime_button(unique_id, JSON.stringify(sizeyRecommendation));
        }
        if (sizeyEvent.event === 'sizey-measurements') {
            let sizey_measurement_id = sizeyEvent.measurements;
            sessionStorage.setItem('sizey-measurement-id', sizey_measurement_id.measurementId );
        }


    };

}


function openSizeyPopupViaVroom(sizey_api_key='', productId='', product_id)
{
    if (sizey_api_key!=='' && productId!=='') {
        fetch('https://vroom-api.sizey.ai/products/' + productId, {headers: {'x-sizey-key': sizey_api_key}}).then(o => o.json()).then(product => {
            if(!product.sizeChart?.id) {
                // show some error?

            } else {
                openRecommendationPopup(sizey_api_key, productId);

            }
        })
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
