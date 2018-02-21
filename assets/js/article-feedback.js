
	jQuery( document ).ready(function($) {
		jQuery(".m-feedback-prompt__social_thumbsup").on("click",function(e){
			e.preventDefault();
			jQuery(this).siblings('.m-feedback-prompt_form').removeClass('m-feedback-prompt__button--active');
			jQuery(this).toggleClass('m-feedback-prompt__button--active');
			jQuery(this).siblings('.m-feedback-prompt__form').removeClass('show');
			jQuery(this).siblings('.m-feedback-prompt__social').toggleClass("show")
			
		});
		jQuery(".m-feedback-prompt_form").on("click",function(e){
			e.preventDefault();
			
			jQuery(this).siblings('.m-feedback-prompt__social').removeClass('m-feedback-prompt__button--active');
			jQuery(this).toggleClass('m-feedback-prompt__button--active');
			jQuery(this).siblings('.m-feedback-prompt__social').removeClass('show');
			jQuery(this).siblings('.m-feedback-prompt__form').toggleClass("show");
			jQuery(this).siblings('.m-feedback-prompt__form').find("#contact-form").show();
			jQuery(this).siblings('.m-feedback-prompt__form').find('.thanks').removeClass('feedback-displayall').addClass('feedback-nodisplayall');
		});
		//Ajax Mail for Feedback

		jQuery("#contact-form").submit(function()	{
		if(jQuery("#mailinglistemail").val()=="") {
			jQuery("#contact-form #feedback-message").text("Please enter your email address.");
			return false;
		} else {
			var email = jQuery('#mailinglistemail').val();
			if(email.indexOf("@") == -1 || email.indexOf(".") == -1) {
				jQuery("#contact-form #feedback-message").text("Please enter a valid email address.");
				return false;
			} else {
				
				var message=jQuery('#feedbackmessage').val();
				var feedbackfullname=jQuery('#feedbackfullname').val();
				var currenturl=jQuery('#currenturl').val();
				var currenttitle=jQuery('#currenttitle').val();
				var data = {
					action: 'join_mailinglist',
					name:feedbackfullname,
					email: email,
					message:message,
					url:currenturl,
					title:currenttitle
				};
				jQuery("#mailinglistsubmit").hide();
				jQuery(".ajaxsave").show();
				jQuery.post(FeedbackAjax.ajaxurl, data,
				function(response){
					if(response=='success'){
					jQuery("#contact-form").hide();
					jQuery('.thanks').removeClass('feedback-nodisplayall');
					jQuery(".thanks").addClass('feedback-displayall');
					} else
					{
					jQuery("#contact-form #feedback-message").html(response);
					}
				});		
				return false;
			}
		} 
		
	});

});

	