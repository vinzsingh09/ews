/*
* this yui script used to show and hide information
* of each activity in the progress bar as the user
* hovers over and moves out the activity in the progress bar
*/
M.block_usp_ews = {};

M.block_usp_ews.showdetails = {
    wwwroot: '',
    preLoadArray: new Array(),
    tickIcon: new Image(),
    crossIcon: new Image(),
    displayDate: 1,

    init: function (YUIObject, root, modulesInUse, date) {
        var i;

        // Remember the web root
        this.wwwroot = root;

        // Rember whether the now indicator is displayed (also hides date)
        this.displayDate = date;

        // Preload icons for modules in use
        for(i=0; i<modulesInUse.length; i++) {
            this.preLoadArray[i] = new Image();
            this.preLoadArray[i].src = M.util.image_url('icon', modulesInUse[i]);
        }
        this.tickIcon.src = M.util.image_url('tick', 'block_usp_ews');
        this.crossIcon.src = M.util.image_url('cross', 'block_usp_ews');
    },
    // shows the module's name, type in mouse moves in that cel
	// if its completed or not and excepted date/time that is the due date/time
    showInfo: function (mod, type, id, name, message, dateTime, instanceID, userID, icon) {

        // Dynamically update the content of the information window below the progress bar
        var content  = '<a href="'+this.wwwroot+'/mod/'+mod+'/view.php?id='+id+'">';
            content += '<img src="'+M.util.image_url('icon', mod)+'" alt="Module icon" class="moduleIcon" />';
            content += name+'</a><br />'+type+' '+message+'&nbsp;';
            content += '<img src="'+M.util.image_url(icon, 'block_usp_ews')+'" alt="Cross or tick icon" /><br />';
            if (this.displayDate) {
                content += M.str.block_usp_ews.time_expected+': '+dateTime+'<br />';
            }
        document.getElementById('usp_ewsBarInfo'+instanceID+'user'+userID).innerHTML = content;
    },
		
	// hides the information of that activity as the mouse is out of that cel
	cancelInfo: function (instanceID, userID){
		content = 'Mouse over block for info.';
		document.getElementById('usp_ewsBarInfo'+instanceID+'user'+userID).innerHTML = content;
	}
};
