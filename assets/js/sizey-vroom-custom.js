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
    jQuery("#vroom-sizey-content").find("[id^='tab2']").hide(); // Hide all content
    jQuery("#vroom-sizey-tabs li:first").attr("id","current"); // Activate the first tab
    jQuery("#vroom-sizey-content #vroomsizeytab1").fadeIn(); // Show first tab's content
    // jQuery("#vroom-sizey-content #tab2").fadeIn(); // Show first tab's content

    jQuery('#vroom-sizey-tabs a').click(function (e) {
        e.preventDefault();
        if (jQuery(this).closest("li").attr("id") == "current") { //detection for current tab
            return;
        } else {
            jQuery("#vroom-sizey-content").find("[id^='vroomsizeytab']").hide(); // Hide all content
            jQuery("#vroom-sizey-tabs li").attr("id",""); //Reset id's
            jQuery(this).parent().attr("id","current"); // Activate this
            jQuery('#' + jQuery(this).attr('name')).fadeIn(); // Show content for the current tab
        }

        let tab = jQuery(this).attr("name");
        localStorage.setItem("vroomtab", tab);
    });

    let currTab = localStorage.getItem("vroomtab");
    jQuery('a[name="' + currTab + '"]').trigger("click");
});
//Tab Generation completed


function validateAPIKey(ele)
{
    let url='https://api.sizey.ai/api/apikey';
    let s_apikey='';
    s_apikey = jQuery(ele).val().trim();
    jQuery('#vroom-sizey-api-key').removeClass('success-highlight error-highlight');
    jQuery("#vroom-sizey-button-configuration").attr("disabled", true);
    jsondata = {"x-sizey-key": s_apikey};
    jQuery.ajax({
        url : url,
        cache : false,
        headers : jsondata,
        type : 'GET',
        contentType: "application/json",
        success : function (data) {
            jQuery('#vroom-sizey-api-key').addClass('success-highlight');
            jQuery("#vroom-sizey-button-configuration").removeAttr("disabled");

        },
        error : function (data, errorThrown) {
            jQuery('#vroom-sizey-api-key').addClass('error-highlight');

        }
    });
}


function validatevroomsizesumbit()
{
    if (window.confirm("Are you sure, you want to change the global store's size attribute")) {
        document.getElementById('vroom-boutique-attribute').submit();
    } else {
        return false;
    }
}

function submitvroomform()
{
    document.getElementById('vroom-sizey-dropdown').submit();
}


jQuery(document).ready(function () {
    jQuery('.sizey-global-configuration').select2();
});
