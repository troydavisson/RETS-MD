function lookupWindow(resource, lookupname) {
	window.open(this_page + '?action=lookup&resource='+resource+'&lookupname='+lookupname, 'lookupValues', 'scrollbars=yes,width=550,height=400');
}

function objectWindow(resource) {
	window.open(this_page + '?action=objects&resource='+resource, 'objectValues', 'scrollbars=yes,width=550,height=400');
}

function getMetadataDetails() {
	var cat_pulldown = document.getElementById('details_for_pulldown');
	var selIndex = cat_pulldown.selectedIndex;
	var lookupvalue = cat_pulldown.options[selIndex].value;
	if (lookupvalue != "") {
		window.location = this_page + '?details_for='+lookupvalue+'#md-details';
	}
	return true;
}
