(function($) {
    console.log(hub_obj);
    $('#hub_comment_field').mentiony({
		onDataRequest: function (mode, keyword, onDataRequestCompleteCallback) {
	  
		  var data = hub_obj.users_array;
	  
		  data = jQuery.grep(data, function( item ) {
			  return item.name.toLowerCase().indexOf(keyword.toLowerCase()) > -1;
		  });
	  
		  // Call this to populate mention.
		  onDataRequestCompleteCallback.call(this, data);
		}
	});
    $('.save_notification_count').on('click', function (e) {
        e.preventDefault();
        console.log(hub_obj.ajaxurl);
        
		$.ajax({
			type: "POST",
			url: hub_obj.ajaxurl,
			data: {
				action: 'reset_notification_count'
			},
			success: function(msg){
				console.log(msg);
				$('.notification_number').html(0);
			}
		});
    })
})( jQuery );