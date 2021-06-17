function openSizeyVroomPopup(sizey_api_key='',  product_id)
{
    if(sessionStorage.getItem('sizey-measurement-id')!='' && sessionStorage.getItem('sizey-measurement-id')!=undefined ) {
        endPointURL = "https://vroom-api.sizey.ai/my-avatar?measurementId="+sessionStorage.getItem('sizey-measurement-id')+"&gender=male";
        callGetAPI(endPointURL);
        return true;
    }
    sessionStorage.setItem( 'sizey-product-id', product_id );
    if (sizey_api_key!=='') {

        let url = "https://recommendation.sizey.ai/?apikey=" + sizey_api_key +"&measureOnly=true";
        let wnd = window.open(url, 'popup', 'width=800,height=800,scrollbars=yes,resizable=yes');
        window.onmessage = function (e) {
            let sizeyEvent = e.data;
            if (sizeyEvent.event === 'sizey-measurements') {
                let sizey_measurement_id = sizeyEvent.measurements;
                sessionStorage.setItem( 'sizey-measurement-id', sizey_measurement_id.measurementId );
                endPointURL = "https://vroom-api.sizey.ai/my-avatar?measurementId="+sizey_measurement_id.measurementId+"&gender=male";
                callGetAPI(endPointURL);
            }
        };
    }
}


function callRecommendationAPI(endpointURL, jSONdata, sizey_key)
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


function callGetAPI(endpointURL)
{
    jQuery.ajax({
        type: "GET",
        url: endpointURL,
        success: function (response) {
            sessionStorage.setItem( 'model-id', response.id );
            sessionStorage.setItem( 'model-id', 'sizey_male_mid_normal_46' );

            return true;
        },
        error: function (jqXHR, textStatus, errorThrown) {
        },
        contentType: 'application/json',


    });
}

jQuery(window).load(function(){


});
