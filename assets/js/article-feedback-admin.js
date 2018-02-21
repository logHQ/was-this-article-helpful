    jQuery(function($){
    	//Color Picker
    jQuery('#ssthumbsup,#ssthumbsdown').wpColorPicker();
    jQuery("#fader").on("input",function () {
    		//jQuery('#fontsize').css('font-size',jQuery(this).val() + "em");
            jQuery('#fontsize').html(jQuery(this).val() + "em");
            jQuery('#fader').val(jQuery(this).val());
    });

    });