var api_key = '2jv9d97u8yyujvyzh4zeacrs';

// Toggle showing or hiding of advanced settings
function showadv(elem) {
	if(elem.checked) {
		jQuery('#widgets-right #etsywidget_adv').show();
	}
	else {
		jQuery('#widgets-right #etsywidget_adv').hide();
	}
}

function sourceChanged(elem, etsyidelem, selectelem) {
	if(jQuery(elem).val() == "store") {
		jQuery('#widgets-right #etsysourcestore #etsystoresections').hide();
		jQuery('#widgets-right #etsysourcestore #etsystoresectionerror').hide();
		jQuery('#widgets-right #etsysourcestore #loadingsections').show();
		jQuery('#widgets-right #etsysourcestore').show();
		
		getSections(etsyidelem, selectelem);
		

	}
	else {
		jQuery('#widgets-right #etsysourcestore').hide();
	}
}

function getSections(etsyidelem, selectelem)
{
	// get the etsy username and form URL
	etsyid=jQuery('#'+etsyidelem).val();
	etsyURL = "http://openapi.etsy.com/v2/public/shops/"+etsyid+"/sections.js?api_key="+api_key;

	// clear the existing options
	jQuery('#'+selectelem).find('option').remove();
	jQuery('#widgets-right #etsysourcestore #etsystoresections').find('input').val("0:All Sections");
	
	// do the request
	jQuery.ajax({
		url: etsyURL,
		dataType: 'jsonp',
		success: function(data) {
		    if (data.ok) {
		    	jQuery('#'+selectelem).append(jQuery("<option></option>").attr("value",0).text("All Sections"));
		    	sections = "0:All Sections"; 
		        if (data.count > 0) {

		            jQuery.each(data.results, function(i,item) {                
		                // if the section has items add it to the list
						if (item.active_listing_count > 0 ) {
							jQuery('#'+selectelem).append(jQuery("<option></option>").attr("value",item.shop_section_id).text(item.title));	
							sections += "|" + item.shop_section_id + ":" + item.title;
						}
		            });
		            jQuery('#widgets-right #etsysourcestore #loadingsections').hide();
		            jQuery('#widgets-right #etsysourcestore #etsystoresections').find("input").val(sections);
					jQuery('#widgets-right #etsysourcestore #etsystoresections').show();
	
					
		        } else {
		            alert('No results');
		        }
		    } else {
		        jQuery('#widgets-right #etsysourcestore #etsystoresectionerror').text(data.error);
		        jQuery('#widgets-right #etsysourcestore #loadingsections').hide();
				jQuery('#widgets-right #etsysourcestore #etsystoresections').hide();	
				jQuery('#widgets-right #etsysourcestore #etsystoresectionerror').show();
		    }
		}
	});


}