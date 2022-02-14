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
})( jQuery );