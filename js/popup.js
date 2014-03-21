/*
*  This jquery script is used for popup alerts
*  the pending and upcoming activities is shown
*  script basically used for animation
*  to fadein and fadeoout popup
**/
// when course page is loaded and ready
$(document).ready(function() {
// to allow popup
var popup = true;
	if(popup == true){
	    // fadein the alert box
		$(".usp_ews_overlayEffect").fadeIn("slow");
		$(".usp_ews_popupContainer").fadeIn("slow");
		$(".usp_ews_popupclose").fadeIn("slow");
		
		// places the box on the centre of the page and its fixed
	    center();
		// to enable popup
		popup = true;
	}
	// clicking the cross icon hides the popup box
	$(".usp_ews_popupclose").click(function(){
		hidePopup();
	});
	
	// clicking outside the box closes the box
	$(".usp_ews_overlayEffect").click(function(){
		hidePopup();
	});

/*
*  function finds screens width and height
*  finds the popup box's width and height
*  uing css places the popup box fixed to the centre of the sceen
**/
function center(){
	var windowWidth = window.innerWidth;
	var windowHeight = window.innerHeight;
	var popupHeight = $(".usp_ews_popupContainer").height();
	var popupWidth = $(".usp_ews_popupContainer").width();
	$(".usp_ews_popupContainer").css({
		"position": "fixed",
		"top": windowHeight/2-popupHeight/2,
		"left": windowWidth/2-popupWidth/2
	});
}

// function used to hide the popp box
// basically fides out the box
function hidePopup(){
	if(popup==true){
		$(".usp_ews_overlayEffect").fadeOut("slow");
		$(".usp_ews_popupContainer").fadeOut("slow");
		$(".usp_ews_popupclose").fadeOut("slow");
		popup = false;
	}
}

} ,jQuery);


