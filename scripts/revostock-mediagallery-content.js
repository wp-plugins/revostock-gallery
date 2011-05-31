jQuery(document).ready( function($){
	container = $("div.revostock-mediagallery-container");
	
	$.post(revostockVars.the_url, {
			action: "revostock_mediagallery_fetch_items",
			revostock_mediagallery_request: revostock_mediagallery_request,
		}, function(data) {
			container.html( function(index, oldhtml){
				return data;
			});
		}
	);
});