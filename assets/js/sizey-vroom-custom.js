jQuery(document).ready(function () {
    jQuery(".sizey-garment-id").select2({
        placeholder: {
            id: '-1', // the value of the option
            text: 'Select a garment'
        },
        allowClear: true
    }).on('change', function (e) {
        let data = jQuery(".sizey-garment-id option:selected").text();
        jQuery("#sizey-garment-name").val(data);
    });
});

/**To generate the tab */
jQuery(document).ready(function () {
    jQuery("#sizey-content").find("[id^='sizeytab']").hide(); // Hide all content
    jQuery("#sizey-tabs li:first").attr("id","current"); // Activate the first tab
    jQuery("#sizey-content #sizeytab1").fadeIn(); // Show first tab's content

    jQuery('#sizey-tabs a').click(function (e) {
        e.preventDefault();
        if (jQuery(this).closest("li").attr("id") == "current") { //detection for current tab
            return;
        } else {
            jQuery("#sizey-content").find("[id^='sizeytab']").hide(); // Hide all content
            jQuery("#sizey-tabs li").attr("id",""); //Reset id's
            jQuery(this).parent().attr("id","current"); // Activate this
            jQuery('#' + jQuery(this).attr('name')).fadeIn(); // Show content for the current tab
        }

        let tab = jQuery(this).attr("name");
        localStorage.setItem("tab", tab);
    });

    let currTab = localStorage.getItem("tab");
    jQuery('a[name="' + currTab + '"]').trigger("click");
});

function validateAPIKey(ele)
{
    let url='https://api.sizey.ai/api/apikey';
    let s_apikey='';
    s_apikey = jQuery(ele).val().trim();
    jQuery('#sizey-api-key').removeClass('success-highlight error-highlight');
    jQuery("#sizey-button-configuration").attr("disabled", true);
    jsondata = {"x-sizey-key": s_apikey};
    jQuery.ajax({
        url : url,
        cache : false,
        headers : jsondata,
        type : 'GET',
        contentType: "application/json",
        success : function (data) {
            jQuery('#sizey-api-key').addClass('success-highlight');
            jQuery("#sizey-button-configuration").removeAttr("disabled");

        },
        error : function (data, errorThrown) {
            jQuery('#sizey-api-key').addClass('error-highlight');

        }
    });
}
