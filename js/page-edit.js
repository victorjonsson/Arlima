jQuery(document).ready(function($) {

    $('#arlima-search-lists').arlimaListSearch('#arlima-lists .arlima-list-link');

	$('#arlima-add-list-select').change(function(e) {
		$('#arlima-select-list').submit();
	});
});