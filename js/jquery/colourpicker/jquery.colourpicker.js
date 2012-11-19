jQuery.fn.colourPicker = function (conf) {
	// Config for plug
	var config = jQuery.extend({
		id:			'jquery-colour-picker',	// id of colour-picker container
		ico:		'ico.gif',				// SRC to colour-picker icon
		title:		'Pick a colour',		// Default dialogue title
		inputBG:	true,					// Whether to change the input's background to the selected colour's
		speed:		200,					// Speed of dialogue-animation
		openTxt:	'Open colour picker'
	}, conf);

	// Inverts a hex-colour
	var hexInvert = function (hex) {
		var r = hex.substr(0, 2);
		var g = hex.substr(2, 2);
		var b = hex.substr(4, 2);

		return 0.212671 * r + 0.715160 * g + 0.072169 * b < 0.5 ? 'ffffff' : '000000'
	};

	// Add the colour-picker dialogue if not added
	var colourPicker = jQuery('#' + config.id);

	if (!colourPicker.length) {
		colourPicker = jQuery('<div id="' + config.id + '"></div>').appendTo(document.body).hide();

		// Remove the colour-picker if you click outside it (on body)
		jQuery(document.body).click(function(event) {
			if (!(jQuery(event.target).is('#' + config.id) || jQuery(event.target).parents('#' + config.id).length)) {
				colourPicker.hide(config.speed);
			}
		});
	}

	// For every select passed to the plug-in
	return this.each(function () {
		// Insert icon and input
		var select	= jQuery(this);
		//var icon	= jQuery('<a href="#"><img src="' + config.ico + '" alt="' + config.openTxt + '" /></a>').insertAfter(select);
		var icon 	= jQuery('<div style="height:16px;width:16px;border:1px solid #ddd;cursor:pointer;display:inline-block;background:#000;vertical-align:middle"></div>').insertAfter(select);
		var input	= jQuery('<input type="hidden" name="' + select.attr('name') + '" value="' + select.val() + '" />').insertAfter(select);
		var loc		= '';

		// Build a list of colours based on the colours in the select
		jQuery('option', select).each(function () {
			var option	= jQuery(this);
			var hex		= option.val();
			var title	= option.text();

			loc += '<li><a href="#" title="' 
					+ title 
					+ '" rel="' 
					+ hex 
					+ '" style="background: #' 
					+ hex 
					+ '; colour: ' 
					+ hexInvert(hex) 
					+ ';">' 
					+ title 
					+ '</a></li>';
		});

		// Remove select
		select.remove();

		// If user wants to, change the input's BG to reflect the newly selected colour
		if (config.inputBG) {
			input.change(function () {
				input.css({background: '#' + input.val(), color: '#' + hexInvert(input.val())});
			});

			input.change();
		}

		// When you click the icon
		icon.click(function () {
			// Show the colour-picker next to the icon and fill it with the colours in the select that used to be there
			var iconPos	= icon.offset();
			var heading	= config.title ? '<h2>' + config.title + '</h2>' : '';

			colourPicker.html(heading + '<ul>' + loc + '</ul>').css({
				position: 'absolute', 
				left: iconPos.left + 'px', 
				top: iconPos.top + 'px'
			}).show(config.speed);

			// When you click a colour in the colour-picker
			jQuery('a', colourPicker).click(function () {
				// The hex is stored in the link's rel-attribute
				var hex = jQuery(this).attr('rel');

				input.val(hex);

				// If user wants to, change the input's BG to reflect the newly selected colour
				if (config.inputBG) {
					input.css({background: '#' + hex, color: '#' + hexInvert(hex)});
				}
				
				icon.css({background: '#' + hex, color: '#' + hexInvert(hex)});

				// Trigger change-event on input
				input.change();

				// Hide the colour-picker and return false
				colourPicker.hide(config.speed);

				return false;
			});

			return false;
		});
	});
};