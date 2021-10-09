function getCookie(cname) {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function setCookie(cname, cvalue, exdays) {
    const d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function openRecommendationPopup(apikey, productId) {
    const storedMeasurementId = sessionStorage.getItem("sizey-measurement-id");
    let url = "https://recommendation.sizey.ai?apikey=" + apikey + "&productId=" + productId + "&measurementId=" +
        storedMeasurementId;
    let wnd = window.open(url, 'popup', 'width=800,height=800,scrollbars=yes,resizable=yes');
    window.onmessage = function (e) {
        let sizeyEvent = e.data;
        if (sizeyEvent.event === "sizey-recommendation") {
            let sizeyRecommendation = sizeyEvent.recommendation;

            if (!sessionStorage.getItem("sizey-recommendation_" + productId)) {
                sessionStorage.setItem("sizey-recommendation_" + productId, JSON.stringify(sizeyRecommendation))
            }

            let unique_id = sessionStorage.getItem('unique-id');

            let jsonData = {
                "sessionId": unique_id,
                "productId": productId,
                "measurementId": sessionStorage.getItem('sizey-measurement-id')
            }

            if (window.vroom) {
                window.vroom.contentWindow.postMessage({ action: "CHANGE_GARMENT", payload: { id: productId, size: sizeyRecommendation.size, colorway: '', scale: 1 } }, "*");
            }

            callRecommendationAPIForVroom("https://analytics-api-dot-sizey-ai.appspot.com/recommendation", jsonData, apikey);
            call_realtime_vroom_button(unique_id, sizeyRecommendation, productId);
        }
        if (sizeyEvent.event === 'sizey-measurements') {
            let sizey_measurement_id = sizeyEvent.measurements;
            sessionStorage.setItem('sizey-measurement-id', sizey_measurement_id.measurementId);
        }


    };

}


function openSizeyPopupViaVroom(sizey_api_key = '', productId = '', product_id) {
    if (sizey_api_key !== '' && productId !== '') {
        fetch('https://vroom-api.sizey.ai/products/' + productId, { headers: { 'x-sizey-key': sizey_api_key } }).then(o => o.json()).then(product => {
            if (!product.sizeChart?.id) {
                // show some error?

            } else {
                openRecommendationPopup(sizey_api_key, productId);

            }
        })
    }
}

function callRecommendationAPIForVroom(endpointURL, jSONdata, sizey_key) {

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


jQuery(document).ready(function () {
    if (!sessionStorage.getItem('unique-id')) {
        let unique_id = uuidv4();
        sessionStorage.setItem('unique-id', unique_id)
        setCookie('unique-id', sessionStorage.getItem('unique-id'), 1);
    }


    jQuery('.single_add_to_cart_button').click(function () {
        var size = jQuery('#pa_size').val();
        var product_id = getCookie("current-page-product-id");
        var session_id = getCookie("unique-id");
        var sizey_api_key = getCookie("sizey-api-key");
        is_recommmended = 0;

        var jSONdata = {
            "sessionId": session_id,
            "productId": product_id,
            "isRecommended": is_recommmended
        }
        endpointURL = "https://analytics-api-dot-sizey-ai.appspot.com/addtocart";
        jQuery.ajax({
            type: "POST",
            contentType: 'application/json',
            headers: {
                'x-sizey-key': sizey_api_key,
            },
            url: endpointURL,
            data: JSON.stringify(jSONdata),
            async: false,
            success: function (response) {
                return true;
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            },

        });

    });

});