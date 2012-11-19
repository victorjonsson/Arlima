/********************* Extensions, variables that need to be declared outside document.ready ************************************/

//makes the Contains-function case insensitive
jQuery.expr[':'].Contains = function(a, i, m) { 
  return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0; 
};


//String function, self explanatory
String.prototype.startsWith = function(str) {
    return this.match("^"+str)==str;
};


//turns a form/collection into a key->value json object
jQuery.fn.serializeObject = function(){
    var o = {};
    var a = this.serializeArray();
    jQuery.each(a, function() {
        if (o[this.name]) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};


var qtip_args_article = {
   position: {
	  my: 'right top',
	  at: 'center left',
	  viewport: jQuery(window)
   },
   style: { classes: 'ui-tooltip-shadow ui-tooltip-light ui-tooltip-480'}
};


function tinyMCEInstance() {
    // In html mode!
    if( !tinyMCE.activeEditor || tinyMCE.activeEditor.isHidden() ) {
        return {
            getContent : function() {
                return jQuery('#tinyMCE').val();
            },
            setContent : function(content) {
                jQuery('#tinyMCE').val( content );
            }
        }
    }
    else {
        return tinyMCE.activeEditor;
    }
}


/********************* END Extensions, variables that need to be declared outside document.ready ************************************/

jQuery(document).ready(function($) {

	$.ctrl = function(key, callback, args) {
		$(document).keydown(function(e) {
			if(!args) args=[]; // IE barks when args is null
			if(e.keyCode == key.charCodeAt(0) && (e.ctrlKey || e.metaKey)) {
				callback.apply(this, args);
				return false;
			}
		});
	};

	//keypresses in chrome
	if($.browser.safari) {
		$.ctrl('S', function() {
			if( $('.listitem.edited').length > 0 ) {
				var container = $('.listitem.edited').closest('.arlima-list-container');
				if( $(container).hasClass('unsaved') ) $('.arlima-save-list', container).trigger('click');
			}
		});
		
		$.ctrl('P', function() {
			$('#arlima-toggle-preview').click();
		});
		
				
		$(document).keyup(function(e) {
			var key = e.keyCode ? e.keyCode : e.which;
			if (key == 27) {  
				e.preventDefault();
				$('#arlima-preview').hide();
			}
			
			if (key == 80 && e.shiftKey && (e.ctrlKey || e.metaKey)) {
				$('#arlima-toggle-preview').click();
				$('#arlima-preview-active-list').click();
			}
			
		});
		
	//firefox etc
	}else{
	
		$(document).keypress(function(e) {
			var key = e.keyCode ? e.keyCode : e.which;
			if (key == 112 && (e.ctrlKey || e.metaKey)) {
				e.preventDefault();
				$('#arlima-toggle-preview').click();
			}
			if (key == 115 && (e.ctrlKey || e.metaKey)) {
				e.preventDefault();
				if( $('.listitem.edited').length > 0 ) {
					var container = $('.listitem.edited').closest('.arlima-list-container');
					if( $(container).hasClass('unsaved') ) $('.arlima-save-list', container).trigger('click');
				}	
			}
			if (key == 27) {  
				e.preventDefault();
				$('#arlima-preview').hide();
			}  
		});
	}

	arlimaGetTemplates();
	arlimaSetupUserPanel();

    if(typeof loadArlimListOnLoad != 'undefined') {
        // todo: move this to a callback into getTemplates
        setTimeout(function() {
            arlimaBuildListWidget(loadArlimListOnLoad, $("#arlima-container-area"));
        }, 1000);
    }
	
	$('html').click(function(e) {
		$('.arlima-list-version-select').hide();
		$('.arlima-list-version-info').show();
	});

	
	$('.fancybox').fancybox( {
		speedIn		:	300,
		speedOut	: 	300,
		titlePosition :	'over'
	});
	
	
	$('#arlima-article-image-scissors-popup').fancybox( {
		autoDimensions :	true,
		speedIn		:	300,
		speedOut	: 	300,
		titlePosition :	'over',
		onComplete	:	arlimaCompleteScissorsFancybox,
		onClosed	:	arlimaCloseScissorsFancybox,
		onStart		:	arlimaStartScissorsFancybox
	});

	
	$('#arlima-edit-article-options-streamer-color').colourPicker({
		ico:    '', 
		title:    false
	});

	
	window.onbeforeunload = function(){ 
		if($('.unsaved').length > 0)
			return ArlimaJS.lang.unsaved;
	};

	
	/* QTIP */
	var qtip_args = {
	   position: {
		  my: 'left top',
		  at: 'center right'
	   },
	   style: { classes: 'ui-tooltip-tipsy ui-tooltip-blue'}
	};
	$('[title].tooltip').qtip(qtip_args);	

	
	$("#arlima-edit-article-title-fontsize-slider").slider({
		value:18,
		min: 8,
		max: 100,
		slide: function( event, ui ) {
			$( "#arlima-edit-article-title-fontsize" ).val( ui.value );
			arlimaUpdateListItem();
		}
	});
	
	$(".dragger").draggable({
		helper:'clone',
		handle:'.handle',
		connectToSortable:'.arlima-list',
		revert:'invalid'
	}); 

    // Refresh imported lists  every 90 second
    setInterval(function() {
        $('.arlima-list.imported', '#arlima-container-area').each(function(idx, list) {
            // don't refresh if we're looking at the list
            var $list = $(this);
            if($list.find('.edited').length == 0) {
                arlimaRefreshList($list);
            }
        });
    }, 90000);

    /**
     * Copies all data from one element to another
     * @param {jQuery} $newElement
     * @param {jQuery} $originalElement
     */
    function copyArticleData($newElement, $originalElement) {
        $newElement.data( $originalElement.data() );

        // don't forget to copy all data for child articles as well
        var $children = $newElement.find('ul li');
        if($children.length > 0) {
            var $origChildren = $originalElement.find('ul li');
            for(var i = ($children.length-1); i >= 0; i--) {
                $children.eq(i).data( $origChildren.eq(i).data() );
            }
        }
    }

    function updateListWidgetVersionInfo($lists, $container, json) {
        $('.arlima-version-id', $lists).val(json.version.id);
        $('.arlima-list-version-info', $container).html('v ' + json.version.id);
        $('.arlima-list-version-info', $container).attr('title', json.versioninfo);
        $('.arlima-list-version-ddl', $container).html('');
        $.each( json.versions, function( idx, version ) {
            $('.arlima-list-version-ddl', $container).append($("<option></option>").val(version).text('v ' + version + '  '));
        });
    }

	$(document).on('init-list-container', '.arlima-list-container', function() {

		var $container = $(this);
		var $lists = $(".arlima-list:first:not(.imported)", $container);
		var $importedLists = $(".arlima-list.imported", $container);

		$container.resizable({
			containment: 'parent'
		});

		$container.draggable({
			containment: 'parent',
			snap: true,
			handle: '.arlima-list-header'
		});

        var listOptions = {
            items: 'li',
            listType: 'ul',
            maxLevels: 2,
            opacity: .6,
            tabSize: 30,
            tolerance: 'pointer',
            connectWith: ['.arlima-list:not(.imported)'],
            distance: 15,
            placeholder: 'arlima-listitem-placeholder',
            forcePlaceholderSize: true,
            toleranceElement: '> div',
            helper: function(e,$li) {
                if(e.ctrlKey || e.metaKey) {
                    var $helper = $($li.clone().insertAfter($li));
                    copyArticleData($helper, $li);
                    if($helper.hasClass('edited'))
                        $helper.removeClass('edited');
                    $helper.effect("highlight", {}, 300);
                }
                return $li.clone();
            },
            receive: function(event, ui) {
                var $draggedItem = $(ui.item);
                var hasDragClass = $draggedItem.hasClass('dragger');
                var hasUIDragClass = $draggedItem.hasClass('ui-draggable'); // coming from imported list
                if(hasDragClass || hasUIDragClass) {
                    var itemClass = hasDragClass ? 'dragger':'ui-draggable';
                    var $newItem = $(this).find('.'+itemClass);
                    var artdata = $draggedItem.data('article');
                    $newItem.data('article', artdata);
                    $newItem.removeClass( itemClass );

                    // Update item title as long as it does not come from an imported list
                    if(hasDragClass)
                        $('.arlima-listitem-title', $newItem).html(artdata.title);

                    // change in editor
                    if(hasUIDragClass) {
                        console.log('sdsd');
                        setTimeout(function() {
                            $newItem.trigger('click');
                        }, 500);
                    }
                }
            },
            update: function(event, ui) {
                $(ui.item).effect("highlight", {}, 500);
                $container.addClass('unsaved');
                $('.arlima-save-list', $container).show('normal');
                arlimaUpdateListItem();
            }
        };

		$lists.nestedSortable(listOptions);
        $lists.disableSelection();
        $importedLists.disableSelection();
        $importedLists.find('li').draggable({
            sender:'importedlist',
            helper : 'clone',
            handle:'.handle',
            connectToSortable:'.arlima-list',
            revert:'invalid',
            zIndex: 40,
            start: function(e, ui) {
                copyArticleData(ui.helper, $(e.currentTarget));
                ui.helper.width($(e.currentTarget).width());
            }
        });

        $(".arlima-save-list", $container).click( function(e) {
		
			if($('.listitem.edited', $container).length > 0) { 
				arlimaUpdateListItem();
			}
			
			var $loader = $('.ajax-loader', $(this).parent());
			$loader.show();

			var articles = {};
			
			var alid = $('.arlima-list-id', $lists).val();
			var version = $('.arlima-version-id', $lists).val();

			var data = {
				action: 'arlima_check_for_later_version',
				alid: alid,
				version: version,
				_ajax_nonce : ArlimaJS.arlimaNonce
			};

			$.post(ajaxurl, data, function(json) {
				if(json) {
					if(!confirm(ArlimaJS.lang.laterVersion + ' \r\n ' + json.versioninfo + '\r\n' + ArlimaJS.lang.overWrite)) {
						$loader.hide();
						return false;
					}
				}
				
				if($('.streamer-extra', $container).length > 1) {
					if(!confirm( ArlimaJS.lang.severalExtras + '\r\n' +  ArlimaJS.lang.overWrite)) {
						$loader.hide();
						return false;
					}
				}
				
				$(">li", $lists).each( function (i, item) {
					articles[i] = arlimaSerializeArticle( $(item) );
				});
				
				var data = {
					action: "arlima_save_list",
					alid: alid,
					articles: articles,
					_ajax_nonce : ArlimaJS.arlimaNonce
				};
			
				$.post(ajaxurl, data, function(json) {
                    updateListWidgetVersionInfo($lists, $container, json);
					$loader.hide();
					$container.removeClass('unsaved');
					$('.arlima-save-list', $container).hide('normal');
				}, 'json');

			}, 'json');

		});

        // http://www.razzed.com/2009/03/16/chrome-problems-window-focus-workaround/
        var arlimaPreviewWindow = false;

		$(".arlima-preview-list", $container).click(function(e) {

			var previewpage = $('.arlima-list-previewpage', $lists).val();

			if( !previewpage || previewpage == '' ) {
				alert('Den här listan har ingen preview-sida');
				return false;
			}
			
			arlimaUpdateListItem();
			
			var loader = $('.ajax-loader', $(this).parent());
			$(loader).show();

			var articles = {};
			
			var alid = $('.arlima-list-id', $lists).val();

			$(">li", $lists).each( function (i, item) {
				var article = arlimaSerializeArticle( $(item) );
				articles[i] = article;
			});
			
			var data = {
				action: "arlima_save_list",
				alid: alid,
				articles: articles,
				preview: true,
				_ajax_nonce : ArlimaJS.arlimaNonce
			};

            if( arlimaPreviewWindow ) {
                arlimaPreviewWindow.close();
            }

			$.post(ajaxurl, data, function(json) {
				$(loader).hide();
				var url = ArlimaJS.baseurl + '/';
                var binder = previewpage.indexOf('?') > -1 ? '&':'?';
                url += previewpage == '/' ? (binder + ArlimaJS.preview_query_arg) : '' + previewpage + binder + ArlimaJS.preview_query_arg;
                arlimaPreviewWindow = window.open(url, 'arlima-window', 'toolbar=1,scrollbars=1' );
                if(arlimaPreviewWindow) // may be blocked by browser
                    arlimaPreviewWindow.focus();
			}, 'json');
		});
		
		
		$(".arlima-list-version-ddl", $container).change( function(e) {
			arlimaRefreshList($lists, $(this).val());
			$container.addClass('unsaved');
			$('.arlima-save-list', $container).show('normal');
		});


		$(".arlima-list-container-remove", this).click( function(e) {
			e.preventDefault();
			if($container.hasClass('unsaved')) {
				if(! confirm(ArlimaJS.lang.changesBeforeRemove)) {
					return false;
				}
			}
			$container.slideUp('fast', function() {
				$container.remove();	
			});
		});


		$(".arlima-refresh-list", this).click( function(e) {
			arlimaRefreshList($lists);
			arlimaRefreshList($importedLists);
		});
		
		
		$('.arlima-list-version').click( function(e) {
			e.stopPropagation();
			$('.arlima-list-version-info', this).hide();
			$('.arlima-list-version-select', this).show();
		});
		
	});
	
	$(document).on('click', '.arlima-list .listitem div', function (e) {
        
		var $listitem = $(this).parent('.listitem');
		var $list = $(this).closest('.arlima-list');
        var $editorBlocker = $('.teaser-editor-blocker');
        var isImported = $list.hasClass('imported');
        var $editor = $('#arlima-edit-article');

        // Block edit of article teaser
        if(isImported && $editorBlocker.length == 0) {
            setTimeout(function() {
                var offset = $('#arlima-toplinks').offset();
                $('<div></div>')
                    .css({
                        position: 'absolute',
                        top :  offset.top +'px',
                        left : offset.left +'px',
                        background : 'rgba(255,255,255, .4)',
                        height: $editor.outerHeight() +'px',
                        width : $editor.outerWidth()+10,
                        zIndex: 9999
                    })
                    .appendTo('body')
                    .addClass('teaser-editor-blocker');
            }, $editor.find('.inside').is(':visible') ? 0:400);
        }
        else if(isImported && $editorBlocker.length == 1) {
            setTimeout(function() {
                $editorBlocker.css('height', $editor.outerHeight() +'px');
            }, 500);
        }
        else if(!isImported && $editorBlocker.length > 0) {
            $editorBlocker.remove();
        }

        $('.listitem.edited').removeClass('edited');
        $('.arlima-list-container.active').removeClass('active');

        $listitem.addClass('edited');
		$listitem.closest('.arlima-list-container').addClass('active');
		
		arlimaResetEditForm();
		
		var article = $listitem.data('article');
		var editform = $("#arlima-edit-article-form");
		var previewtemplate = $('.arlima-list-previewtemplate', $list).val();
		
		if(!article.title_fontsize) article.title_fontsize = 24;
		
		if(article.post_id == 0) article.post_id = null;
		
		if(article.post_id) {
			$('#tinyMCE-add_media', editform).attr('href', 'media-upload.php?post_id=' + article.post_id + '&type=image&TB_iframe=1&send=true');
			$('#arlima-article-connected-post', editform).html('<a href="post.php?post=' + article.post_id + '&action=edit" target="_blank">' + article.post_id + '</a>');
		}else{
			$('#tinyMCE-add_media', editform).attr('href', 'media-upload.php?type=image&TB_iframe=1&send=true');
			$('#arlima-article-connected-post-change').hide();
			$('#arlima-article-post_id').show();
		}

        tinyMCEInstance().setContent(article.text);

		$.each(article, function(key, value) {
			$("[name='" + key + "']", editform).not(':radio').val(value);
		});
		
		$('#arlima-edit-article-title-fontsize-slider').slider( "value", article.title_fontsize );
		
		if(article.options) {
			$.each(article.options, function(key, value) {
				if( $("[name='options-" + key + "']", editform).length > 0 ) $("[name='options-" + key + "']", editform).val(value);
			});
			if(article.options.streamer) $("[name='options-streamer']", editform).prop('checked', true);
			if(article.options.streamer_color) 
				$('#arlima-edit-article-options-streamer-text div', editform).css('background', '#' + article.options.streamer_color);
			else
				$('#arlima-edit-article-options-streamer-text div', editform).css('background', '#000');
			if(article.options.hiderelated) $("[name='options-hiderelated']", editform).prop('checked', true);

		}
		
		if(article.image_options) {
			arlimaUpdateMainImage( article.image_options ); 
		}else{
			arlimaUpdateMainImage( ); 
		}
	
		if($(editform).parent('.inside').css('display') == 'none') $(editform).parent('.inside').slideDown('100');
		
		arlimaSetStreamerFormState();
		arlimaUpdatePreview();
	});

	$(document).on('click', '.arlima-listitem-remove', function(e) {
		e.stopPropagation(); 
		var listitem = $(this).closest('.listitem');
		if( !confirm(ArlimaJS.lang.wantToRemove + $('span', listitem).text() + ArlimaJS.lang.fromList) ) return;
		
		if( $(listitem).hasClass('edited') ) {
			$('#arlima-edit-article .inside').slideToggle(200);
			arlimaResetEditForm();
			arlimaUpdateListItem();
		}
		$(listitem).fadeOut('fast', function(){
			var list = $(listitem).closest('.arlima-list');
			var container = $(listitem).closest('.arlima-list-container');
			$(container).addClass('unsaved');
			$('.arlima-save-list', container).show('normal');
			$(this).remove();
		});
	});
		
	// meybe not needed
	$(document).on('mouseover mouseout', '.arlima-list:not(.imported) .listitem div', function (e) {
		var listitem = $(this).parent('li');
		if(!e.shiftKey && !e.ctrlKey && !e.metaKey) return false;
		if ( e.type == "mouseover" ) {
			if(e.shiftKey) {
				$('#arlima-preview').show(0, function() {
					arlimaUpdatePreview(listitem);
				});
			}
		} else {
			$('#arlima-preview').hide(0);
		}
	});
	
	$('#arlima-edit-article-options-streamer-image-list img').click( function(e) {
		$("[name='options-streamer_image']").val($(this).attr('alt'));
		arlimaSetStreamerFormState();
		arlimaUpdateListItem();
		$.fancybox.close();
	});

	
	$('#arlima-edit-article-form').change( function(e) {
		if( $(e.target).attr('name') == 'image_align' || $(e.target).attr('id') == 'arlima-article-image-size' ) return false;
		arlimaSetStreamerFormState();
		arlimaUpdateListItem();
	});
	

	$('#arlima-edit-article #arlima-edit-article-remove').click( function(e) {
		$('#arlima-edit-article .inside').slideToggle(200);
		arlimaResetEditForm();
		arlimaUpdateListItem();
		$('.listitem.edited').fadeOut('fast', function(){
			$(this).remove();
		});
	});


	$('#arlima-search-lists').keyup(function(e) {
		if (e.keyCode == '13') {
			e.preventDefault();
		}
		var searchstring = $(this).val();
		if(searchstring.length > 0) {
			$("#arlima-lists ul").show(0);
			$("#arlima-lists .arlima-list-link:Contains('" + searchstring + "')").show(0);
			$("#arlima-lists .arlima-list-link").not(":Contains('" + searchstring + "')").hide(0);
		}else{
			$("#arlima-lists .arlima-list-link").hide(0);
			$("#arlima-lists ul").hide(0);
		}
	});
	
	
	$('#arlima-edit-article-title-fontsize').blur(function() {
		$('#arlima-edit-article-title-fontsize-slider').slider( "value", $(this).val() );
	});

	
	$('#arlima-lists ul li').click(function(e) {
		$('#arlima-search-lists').val('');
		$('#arlima-lists ul').children().hide();
		var alid = $(this).attr('alid');
		var div = $("#arlima-container-area");
		
		arlimaBuildListWidget(alid, div);
	});
		
		
	$("#arlima-add-list-btn").click( function() {
		var alid = $("#arlima-add-list-select").val();
		var div = $("#arlima-container-area");
		
		arlimaBuildListWidget(alid, div);
	});


	$("#arlima-save-setup-btn").click( function() {
		var loader = $('#save-setup-loader');
		$(loader).show();

		var lists = {};

		$('.arlima-list-container', '#arlima-container-area').each( function (i, item) {
			var alid = $('.arlima-list-id', item).val();
			var list = {};
			list.alid = alid;
			list.top = $(item).css('top');
			list.left = $(item).css('left');
			list.width = $(item).css('width');
			list.height = $(item).css('height');
			lists[i] = list;
		});

		var data = {
			action: "arlima_save_list_setup",
			lists: lists,
			_ajax_nonce : ArlimaJS.arlimaNonce
		};
	
		$.post(ajaxurl, data, function(html) {
			$(loader).hide();
		});
	});


	$(".arlima-refresh-all-lists", this).click( function(e) {
		e.preventDefault();
		$('.arlima-list', '#arlima-container-area').each(function(idx, list) {
			arlimaRefreshList(list);
		});
	});


	$('#arlima-posts-form').submit( function(e) {
		e.preventDefault();
		
		arlimaGetPosts(0);
	});


	$('.handlediv').click( function() {
		$(this).parent().find('.inside').slideToggle(200);
	});

	
	$('#arlima-toggle-preview').click( function() {
		$('#arlima-preview').toggle(0, function() {
			arlimaUpdatePreview();
		});
	});
	
	
	$('#arlima-article-image-options input').click( function(e) {
		arlimaUpdateMainImage();
		arlimaUpdateListItem();
	});
	
		
	$('#arlima-article-image-options select').change( function(e) {
		arlimaUpdateMainImage();
		arlimaUpdateListItem();
	});
	
	
	$('#arlima-article-image-remove').click( function(e) {
		arlimaDeleteMainImage();
		arlimaUpdateListItem();
	});
	
	
	$('#arlima-article-image-disconnect').click( function(e) {
		e.preventDefault();
		var data = {
			action: 'arlima_duplicate_image',
			attachid: $('#arlima-article-image-attach_id').val(),
			_ajax_nonce : ArlimaJS.arlimaNonce
		};
		$.post(ajaxurl, data, function(json) {
			var args = {attach_id: json.attach_id, html: json.html, connected: 'false' };
			arlimaUpdateMainImage( args );
			arlimaUpdateListItem();
		}, 'json');
		
	});

	
	$('#arlima-article-connected-post-change').click( function(e) {
		e.preventDefault();
		$(this).hide();
		$('#arlima-article-connected-post').html('');
		$('#arlima-article-post_id').show().focus();
	});
	
	
	//$('#arlima-article-post_id').blur( function(e) {
	$('#arlima-article-post_id').bind( 'save', function(e) {
		e.preventDefault();
		var postid = $(this).val();
		if(postid == '') {
			$('#arlima-edit-article-url').val('');
			return;
		}
		if(isNaN(postid)) {
			$(this).addClass('error');
			return false;
		}
		var data = {
			action: 'arlima_get_post_url',
			postid: postid,
			_ajax_nonce : ArlimaJS.arlimaNonce
		};
		
		$('#arlima-article-connected-post').html('<img src="' + ArlimaJS.imageurl + 'ajax-loader-trans.gif" />');
		$('#arlima-article-post_id').hide();
		$.post(ajaxurl, data, function(json) {
			if(json.url) {
				$('#arlima-article-post_id').removeClass('error').hide();
				$('#arlima-article-connected-post').html('<a href="post.php?post=' + postid + '&action=edit" target="_blank">' + postid + '</a>');
				$('#arlima-article-connected-post-change').show();
				$('#arlima-edit-article-url').val(json.url);
			}else{
				$('#arlima-article-connected-post').html('');
				$('#arlima-article-post_id').addClass('error').show();
				return false;
			}
		}, 'json');
		
	});
	
	$('#arlima-article-post_id').keypress(function(e) {
		var key = e.keyCode ? e.keyCode : e.which;
		if (key == 13) {
			e.preventDefault();
			$(this).trigger('save');
			return false;
		}
	});
	
	
	$(document).on('click', '.arlima-get-posts-paging', function(e) {
		e.preventDefault();
		arlimaGetPosts($(this).attr('alt'));
	});
	
	
	//$('#arlima-article-attachments-browse').click(function(e) {
	$('#arlima-article-image').click(function(e) {
		e.preventDefault();
		$('#arlima-article-attachments').html('');
		var postid = $('#arlima-article-post_id').val() ;

		if( isNaN( postid ) || postid == '' ) {
			alert(ArlimaJS.lang.noImages+', '+ArlimaJS.lang.noConnection); return false;
		}
		var data = {
			action: 'arlima_get_attached_images',
			postid: postid,
			_ajax_nonce : ArlimaJS.arlimaNonce
		};

		$.post(ajaxurl, data, function(json) {
			if( !json[0] ) { alert(ArlimaJS.lang.noImages); return false; }
			$.each(json, function(idx, img) {
				var thumb = $('<div />').addClass('arlima-article-attachment').html(img.thumb);
				$(thumb).click(function(e) {
					var args = { html : img.large, attach_id : img.attach_id, alignment : 'center', size : 'full' };
					arlimaUpdateMainImage( args );
					arlimaUpdateListItem();
					$.fancybox.close();
				});
				$('#arlima-article-attachments').append(thumb);
			});
			$.fancybox({
				'href' : '#arlima-article-attachments'
			});
			
		}, 'json');
	});
	
	$('#arlima-save-active-list').click(function(e) {
		e.preventDefault();
		if( $('.listitem.edited').length == 0 ) {
			alert(ArlimaJS.lang.noList);
			return false;
		}
		var container = $('.listitem.edited').closest('.arlima-list-container');
		//arlimaUpdateListItem();
		$('.arlima-save-list', container).trigger('click');
		
	});
	
	$('#arlima-preview-active-list').click(function(e) {
		e.preventDefault();
		if( $('.listitem.edited').length == 0 ) {
			alert(ArlimaJS.lang.noList);
			return false;
		}
		var container = $('.listitem.edited').closest('.arlima-list-container');
		//arlimaUpdateListItem();
		$('.arlima-preview-list', container).trigger('click');
		
	});
	
	
	// Listen for scissors startup, and uncheck the aspect ratio checkbox
	document.addEventListener("DOMNodeInserted", function(event) {
		var node_id = $(event.target).attr('id')+"";
		
		if ( node_id.startsWith('scissorsCrop') ){
			$('#'+ node_id +' input[type="checkbox"]').each( function( i, e ){
				var aspect = e.id+"";
				if(aspect.startsWith('scissorsLockBox')){
					$(e).prop("checked", false);
				}
			});
			
			$('#'+ node_id +' div').each( function( i, e ){
				var div = e.id+"";
				if ( div.startsWith('scissorsReir') ){
					$('#'+ div).hide();
				}
			});
			
			// Attach ratio buttons
			var arlima_attach_id = $('#arlima-article-image-attach_id').val();
			var ratio_ws = $('<button>').click( function(e){
				e.preventDefault();
				setScissorsRatio(arlima_attach_id, '16', '9');
			})
			.html('Widescreen')
			.addClass('button');

			var ratio_cinema = $('<button>').click( function(e){
				e.preventDefault();
				setScissorsRatio(arlima_attach_id, '21', '9');
			})
			.html('Cinema')
			.addClass('button');

			var ratio_square = $('<button>').click( function(e){
				e.preventDefault();
				setScissorsRatio(arlima_attach_id, '666', '666');
			})
			.html('Kvadrat')
			.addClass('button');			
			
			$('#scissorsCropPane-' + arlima_attach_id).append(ratio_ws);
			$('#scissorsCropPane-' + arlima_attach_id).append(ratio_cinema);
			$('#scissorsCropPane-' + arlima_attach_id).append(ratio_square);
		}
		
		if ( node_id.startsWith('scissorsWatermark') ){
			var vkpt_attach_id = $('#vkpt-attach-id').val();
			
			$('#'+ node_id +' input[type="checkbox"]').each( function( i, e ){
				var node = e.id+"";
				if(node.startsWith('scissors_watermark_target')){
					var split = node.split("_");
					split = split[3].split("-");
					$(e).prop("checked", true);
					scissorsWatermarkStateChanged( split[1], split[0] );
				}
			});
		}
		
	});
	
	function setScissorsRatio( attach_id, rx, ry ){
		$('#scissorsLockBox-' + attach_id).prop("checked", true);
		scissorsAspectChange(attach_id);
		$('#scissorsLockX-' + attach_id).val(rx);
		$('#scissorsLockY-' + attach_id).val(ry);
		scissorsManualAspectChange(attach_id);		
	}
		
	function arlimaStartScissorsFancybox ( ) {
		var imgopts = $('#arlima-article-image-options').data('image_options');
		var data = {
			action: 'arlima_get_scissors',
			attachment_id: imgopts.attach_id,
			_ajax_nonce : ArlimaJS.arlimaNonce
		};

		$.post(ajaxurl, data, function(html) {
			$('#arlima-article-image-scissors').html(html).show();
		});
		$('#arlima-article-image-container').addClass('arlima-fancybox media-item-info');
		$('#arlima-article-image img').addClass('thumbnail');
		arlimaRemoveWidthHeight();
	}
	
	function arlimaCompleteScissorsFancybox ( ) {
		//
	}
	
	function arlimaCloseScissorsFancybox ( ) {
		$('#arlima-article-image-container').removeClass('arlima-fancybox media-item-info');
		$('#arlima-article-image img').removeClass('thumbnail');
		$('#arlima-article-image-container').addClass('media-item-info');
		$('#arlima-article-image-scissors').html('').hide();
		var timestamp = Math.round(new Date().getTime() / 1000);
		arlimaUpdateMainImage( {updated : timestamp} );
		arlimaUpdateListItem();
	}
	
	// Remove img width and height attr. Ugly, but does the trick.
	function arlimaRemoveWidthHeight(){	
		$('#arlima-article-image img').removeAttr('width');
		$('#arlima-article-image img').removeAttr('height');
	}


	function arlimaGetPosts (offset) {
		$('#arlima-get-posts-loader').show();
			
		var cat = $('#arlima-posts-category').val();
		var author = $('#arlima-posts-author').val();
		var ignore_author = $('#arlima-posts-ignore-author').filter(':checked').val();
		var search = $('#arlima-posts-search').val();
		var div = $('#arlima-posts');

        // find excludes
        var excludes = {};
        $('#arlima-posts-form input[name^=arlima_exclude]').each(function() {
            var $input = $(this);
            if( $input.is(':checked') ) {
                var name = $input.attr('name').split('[')[1].replace(']', '');
                excludes[name] = $input.val();
            }
        });

		var data = {
			action: "arlima_query_posts",
			catid: cat,
			search: search,
			author: author,
            arlima_exclude : excludes,
			offset: offset,
			_ajax_nonce : ArlimaJS.arlimaNonce
		};

		$.post(ajaxurl, data, function(data) {
			$('#arlima-get-posts-loader').hide();
			div.html($(data.html));

			$(".dragger", div).each( function (i, item) {
				article = data.articles[i];
				var content = article.content;
				delete article.content;
				arlimaFillListItem(item, article);

				var args = qtip_args_article;
				args.content = '<h2 style="margin:0;">' + article.title + '</h2>' + content;
				$('a', $(item).parents('tr')).qtip(args);
			});

			$(".dragger", div).draggable({
				helper:'clone',
				sender:'templatelist',
				handle:'.handle',
				connectToSortable:'.arlima-list',
				revert:'invalid',
				zIndex: 40
			}); 
		}, "json");
		return false;
	}


	function arlimaDeleteMainImage () {

		$('#arlima-article-image').html('<p>Dra och släpp här</p>').addClass('empty');
		$('#arlima-article-image-options').removeData('image_options');
		$('#arlima-article-image-options').hide();
		$('#arlima-article-image-links .hide-if-no-image').hide();
		
	}


	function arlimaInsertMainImage (imghtml) {

		// called from the insert image-lightbox
		var div = $('<div />').html(imghtml);
		var img = $('img', div);

		var alignclass = 'aligncenter';
		if($(img).hasClass('alignleft')) alignclass = 'alignleft';
		if($(img).hasClass('alignright')) alignclass = 'alignright';
		
		var width = $(img).attr('width');
		var size = 'full';
		
		var args = { html: escape( $(img).parent().html() ), url : $(img).attr('src'), alignment : alignclass, size : size };

		arlimaUpdateMainImage( args );

	}


	function arlimaUpdateMainImage ( args ) {

		//called when clicking a arlima article in a list or changing the options in the edit form
		
		var size = $('#arlima-article-image-size');
		var alignment = $('#arlima-article-image-alignment input');
		var attach_id = $('#arlima-article-image-attach_id');
		var updated = $('#arlima-article-image-updated');
		var connected = $('#arlima-article-image-connected_to_post_thumbnail');
		
		if(args) {
			if(args.html) $('#arlima-article-image').html( unescape( args.html ) ).removeClass('empty');
			if(args.alignment) $(alignment).filter('[value=' + args.alignment +  ']').prop('checked', true);
			if(args.size) $(size).val( args.size );
			if(args.attach_id) $(attach_id).val(args.attach_id);
			if(args.updated) $(updated).val(args.updated);
			if(args.connected) $(connected).val(args.connected);
		}
		
		if($(size).val() == 'full') {
			$(alignment).filter('[value=aligncenter]').prop('checked', true);
			$(alignment).parent().hide();
		}else{
			$(alignment).parent().show();
			if( $(alignment).filter(':checked').val() == 'aligncenter' ) $(alignment).filter('[value=alignleft]').prop('checked', true);
			//$(alignment).filter('[value=aligncenter]').attr('disabled', 'disabled');
		}
		
		var disconnect = $('#arlima-article-image-disconnect');
		if($(connected).val() == 'true') {
			$(disconnect).show();
		}else{
			$(disconnect).hide();
		}
		
		var img = $('#arlima-article-image img');
		arlimaRemoveWidthHeight();
		var imgopts = {};
		if( $(img).length > 0 ) {
			imgopts = { html: escape( $(img).parent().html() ), url : $(img).attr('src'), alignment : $(alignment).filter(':checked').val(), size : $(size).val(), attach_id : $(attach_id).val(), updated : $(updated).val(), connected : $(connected).val() };
		}
		
		$('#arlima-article-image-options').data('image_options', imgopts);
		if( imgopts.html ) {
			$('#arlima-article-image-options').show();
			$('#arlima-article-image-links .hide-if-no-image').show();
			if(!imgopts.attach_id) $('#arlima-article-image-scissors-popup').parent('li').hide();
		} else {
			$('#arlima-article-image-options').hide();
			$('#arlima-article-image').html('<p>Dra och släpp här</p>').addClass('empty');
			$('#arlima-article-image-links .hide-if-no-image').hide();
		}

	}

	function arlimaGetTemplates() {
		var div = $('#arlima-templates');
		var data = {
			action: "arlima_print_custom_templates",
			_ajax_nonce : ArlimaJS.arlimaNonce
		};

		$.post(ajaxurl, data, function(data) {
			div.html($(data.html));

			$(".dragger", div).each( function (i, item) {
				arlimaFillListItem(item, data.articles[i]);
			});

			$(".dragger", div).draggable({
				helper:'clone',
				sender:'postlist',
				handle:'.handle',
				connectToSortable:'.arlima-list',
				revert:'invalid'
			}); 
		}, "json");
	}


	function arlimaSetupUserPanel() {
		$("#setup-loader").show();
		var data = {
			action: "arlima_get_list_setup",
			_ajax_nonce : ArlimaJS.arlimaNonce
		};
			
		$.get(ajaxurl, data, function(json) {
			if(json) {
				$.each(json, function(index, list) {
					arlimaBuildListWidget( list.alid, $('#arlima-container-area'), { top: list.top, left: list.left, height: list.height, width: list.width } );
				});
			}else{
				$("#setup-loader").hide();
			}
		}, "json");
	}


	function arlimaBuildListWidget(alid, div, setup) {

		if(!setup)
            setup = {};
		
		var $prior = $('#arlima-list-container-' + alid);
		if($prior.length > 0) {
			$prior.effect("shake", { times:4, distance: 10 }, 50);
			return false;
		}

        $.ajax({
            url : ajaxurl,
            type:'POST',
            dataType: 'json',
            data :{
                action: "arlima_add_list_widget",
                alid: alid,
                _ajax_nonce : ArlimaJS.arlimaNonce
            },
            success : function(json) {
                if(typeof json.error != 'undefined') {
                    alert(json.error);
                    return;
                }

                var $div = $('<div />');
                $div.attr('id', 'arlima-list-container-' + alid);
                $div.addClass('arlima-list-container'+(json.is_imported ? ' imported':''));
                $div.html(json.html);

                if(setup.top) {
                    $div.css("top", setup.top);
                    $div.css("left", setup.left);
                    $div.css("height", setup.height);
                    $div.css("width", setup.width);
                }
                else {
                    var lastelem = $('.arlima-list-container:last', div);
                    if($(lastelem).length > 0) {
                        var pos = $(lastelem).position();
                        var top = 0;
                        var left = 0;
                        if( ( pos.left + lastelem.width() + 300 ) <= div.width() ) {
                            left = pos.left + lastelem.width();
                            top = pos.top;
                        }
                        $div.css("top", top + 'px');
                        $div.css("left", left + 'px');
                    }
                }

                var list = $('.arlima-list', $div);

                arlimaAddListItems(list, json.articles);

                if(json.version.id > 0) {
                    $('.arlima-list-version-info', $div).html('v ' + json.version.id);
                    $('.arlima-list-version-info', $div).attr('title', json.versioninfo);
                    $('.arlima-list-version-info', $div).qtip(qtip_args);
                    $.each( json.versions, function( idx, version ) {
                        $('.arlima-list-version-ddl', $div).append($("<option></option>").attr("value",version).text('v ' + version + '  '));
                    });
                }

                $(div).append($div);

                $div.hide().slideDown('fast', function() {
                    $div.trigger('init-list-container');
                });
            },
            error : function(a) {
                var mess = a.responseText;
                if(typeof JSON != 'undefined') {
                    var json = false;
                    try {
                        json = JSON.parse(mess);
                    } catch(e) { }

                    if(json && typeof json.error != 'undefined')
                        mess = json.error;
                }
                alert(mess);
            }
        });
	}

	function arlimaRefreshList( $list, version ) {
        if(typeof $list.find == 'undefined')
            $list = $($list);
        if($list.length == 0) {
            return;
        }

		var alid = $('.arlima-list-id', $list).val();
		var $container = $('#arlima-list-container-' + alid);
		var loader = $('.ajax-loader', $container);

		if($container.hasClass('unsaved')) {
			if(! confirm(ArlimaJS.lang.unsaved)) {
				return false;
			}
			$container.removeClass('unsaved');
			$('.arlima-save-list', $container).hide('normal');
		}
		
		if( $('.edited', $list).length > 0 ) {
			$('#arlima-edit-article .inside').slideToggle(200);
			arlimaResetEditForm();
			$('#arlima-preview').hide();
		}

		$(loader).show();

		$list.fadeOut('normal', function() {
            if(typeof version == 'undefined')
                version = '';

			$('.listitem', $list).remove();

			var data = {
				action: "arlima_add_list_widget",
				alid: alid,
				version: version,
				_ajax_nonce : ArlimaJS.arlimaNonce
			};

			$.post(ajaxurl, data, function(json) {
                if(typeof json.error != 'undefined') {
                    alert(json.error + ' '+ version+' '+alid);
                    return;
                }

				$(loader).hide();
				arlimaAddListItems( $list, json.articles );

				$list.fadeIn('normal');

                if(json.is_imported) {
                    $('.arlima-list-version-info', $container).text(json.versioninfo);
                }
                else {
                    updateListWidgetVersionInfo($list, $container, json);
                    /*
                    $('.arlima-list-version-info', container).html('v ' + json.version.id);
                    $('.arlima-list-version-info', container).attr('title', json.versioninfo); */
                    $('.arlima-version-id', $list).val(json.version.id);
                }
			}, 'json');
		});
	}


	function arlimaAddListItems ( list, articles ) {
		$.each(articles, function ( idx, article ) {

			var $listItem = $('<li />');
			$listItem.addClass('listitem').html('<div><span class="arlima-listitem-title"></span><img class="arlima-listitem-remove" alt="remove" src="' + ArlimaJS.imageurl + 'close-icon.png" /></div>');
			arlimaFillListItem($listItem, article);

			if(article.children.length > 0) {
				var sublist = $('<ul />');
				$listItem.append(sublist);
				arlimaAddListItems( sublist, article.children );
			}

			$(list).append($listItem);
			
		});
	}


	function arlimaFillListItem( item, data ) {
		if(!$(item).hasClass('dragger')) {
			$('.arlima-listitem-title', item).html(arlimaGetListItemTitle(data));
		}
		$(item).data('article', data);
		$("#setup-loader").hide();
	}



	function arlimaUpdateListItem() {

		var editForm = $('#arlima-edit-article-form');
		var item = $('li.listitem.edited:first');
		
		if(item.length > 0) {
			var list = $(item).parents('.arlima-list');
			var container = $(list).closest('.arlima-list-container');
			$(container).addClass('unsaved');
			$('.arlima-save-list', $(container)).show('normal');

			var json = editForm.serializeObject();

			json.options = {};
			
			$.each(json, function(key, value) {
				if(key.substr(0, 8) == 'options-') {
					json.options[key.substr(8)] = value;
					delete json[key];
				}
			});
			
			json.text = tinyMCEInstance().getContent();
			json.image_options = $('#arlima-article-image-options').data('image_options');
			
			$('.arlima-listitem-title:first', item).html( arlimaGetListItemTitle( json ) );
			$(item).data('article', json);
			
			arlimaUpdatePreview();
		}
	}


	function arlimaGetListItemTitle(json) {
		var title = '';
		if(json.options) {
			if(json.options.streamer) {
                var color;
				if(json.options.streamer_type == 'extra') color = 'black';
				if(json.options.streamer_type == 'image') color = 'blue';
				if(json.options.streamer_type == 'text') color =  'green'; // @todo: look at json.options.streamer_color;
				title += '<img src="' + ArlimaJS.imageurl + 'square-' + color + '.png" class="arlima-streamer-indicator streamer-' + json.options.streamer_type + '" height="4" width="4" style="vertical-align:middle; margin-right:4px;" /> ';
			}
			if(json.options.pre_title) {
				title += json.options.pre_title + ' ';
			}
		}
		
		if(json.title) 
			title += json.title;
		else
			title += '[' + json.text.replace(/(<.*?>)/ig,"").substring(0,30) +'...]';
		return title;
	}


	function arlimaUpdatePreview(listitem) {
		if(listitem == null) listitem = $('.listitem.edited');

		if($(listitem).length > 0 && $('#arlima-preview').css('display') != 'none') {

			var container = $(listitem).closest('.arlima-list-container');

			var previewtemplate = $('.arlima-list-previewtemplate', container).val();
			
			if($(listitem).parents('.listitem').length > 0) {
				listitem = $(listitem).parents('.listitem:first');
			}
			
			var article = arlimaSerializeArticle(listitem, true);
			
			//$('#arlima-preview').attr('class', 'w' + previewwidth);

			arlimaBuildPreviewTeaser($('#arlima-preview'), article, previewtemplate, false);
			
		}
	}

    function arlimaBuildPreviewTeaser($container, article, templatename, isChildArticle, extraClasses, isChildSplit) {
        if(!ArlimaTemplateLoader.finishedLoading) {
            // templates not yet loaded
            setTimeout(function() {
                arlimaBuildPreviewTeaser($container, article, templatename, isChildArticle, extraClasses);
            }, 500);
            return;
        }

        // construct the same typ of object created on the backend when parsing the template
        var templateArgs = {
            container : {
                id : 'teaser-' + article.id,
                'class' : 'arlima teaser ' + (extraClasses ? extraClasses:'')
            },
            article : {
                html_text : article.text
            },
            streamer : false,
            image : false,
            related : false,
            is_child : isChildArticle,
            is_child_split : isChildSplit === true,
            sub_articles : false
        };

        // title
		if( article.title != '') {		
			var title = article.title.replace('__', '<br />');
			if( article.options && article.options.pre_title )
				title = '<span class="arlima-pre-title">' + article.options.pre_title + '</span> ' + title;


            var headerClass = '';
            if('options' in article && article.options.header_class )
                headerClass = ' class="'+article.options.header_class+'"';

			templateArgs.article.html_title = '<h2 style="font-size:'+article.title_fontsize+'px"'+headerClass+'>'+title+'</h2>';
		}
		
        // Streamer
        if( article.options ) {

            if( article.options.streamer ) {
                templateArgs.streamer = {
                    type : article.options.streamer_type,
                    content : article.options.streamer_type == 'image' ? '<img src="' + article.options.streamer_image + '" />':article.options.streamer_content,
                    style :'background: #'+article.options.streamer_color // bg color in here
                };

                if(article.options.streamer_type == 'extra') {
                    templateArgs.streamer.style = '';
                    templateArgs.streamer.content = 'EXTRA';
                }
                else if(article.options.streamer_type == 'image') {
                    templateArgs.streamer.style = '';
                }
            }
        }

        // image
        if( article.image_options ) {
            if( article.image_options.html ) {
                templateArgs.image = {
                    src : $(unescape(article.image_options.html)).attr('src'),
                    image_class : article.image_options.alignment,
                    image_size : article.image_options.size
                };
            }
        }

        // children
        if(article.children) {
            var childcnt = 0;
            $.each(article.children, function(i, child) {
                childcnt++;
            });
            if( childcnt > 0 ) {
                var $childContainer = $('<div />');

                $childContainer
                    .addClass('teaser-children children-' + childcnt)
                    .appendTo($container);

                $.each(article.children, function(i, childArticle) {
                    //epic variable name
                    var $childteaser = $('<div />');
					var extraClasses = i % 2 == 0 ? 'first':'last';
					
                    if( childcnt > 1 ) {
						extraClasses += ' teaser-split';
                    }

                    // render child article
                    arlimaBuildPreviewTeaser(
                            $childteaser,
                            childArticle,
                            templatename,
                            true,
							extraClasses,
                            childcnt > 1
                        );

                    // append child article to document
					$childContainer.append( $childteaser.html() );
                });

                templateArgs.sub_articles = $childContainer.html();
            }
        }

        var tmpl = ArlimaTemplateLoader.templates[templatename];
        if(typeof tmpl == 'undefined')   {
            tmpl = ArlimaTemplateLoader.templates['arlima-tmpl-column-first'];
            console.warn('Trying to use template "'+templatename+'" but it does not exist, now using article.tmpl instead');
        }

        // remove links
        tmpl = tmpl.replace(/href="([a-zA-Z\.\{+\}\$]+)"/g, 'href="Javascript:void(0)"');

        // Don't let jquery-tmpl insert image sources, it will generate 404
        tmpl = tmpl.replace(/{{html image.src}}/g, templateArgs.image.src ? templateArgs.image.src:'');

        var tmplHTML = $('<div>'+tmpl+'</div>').tmpl( templateArgs );

        $container
            .empty()
            .append( tmplHTML );
    }

	function arlimaSerializeArticle (listitem, santizeEntryWord) {
        if(typeof santizeEntryWord == 'undefined')
            santizeEntryWord = false;

		var article = $(listitem).data('article');
        if(typeof article == 'undefined')
            return {};

		if(!article.title_fontsize) article.title_fontsize = 24;
		article.children = {};

        if( santizeEntryWord ) {
            article.text = article.text.replace(new RegExp('(<span)(.*class=\".*teaser-entryword.*\")>(.*)(<\/span>)','g'), '<span class="teaser-entryword">$3</span>');
        }

		if($(listitem).has('ul')) {
			$('ul li', listitem).each(function(idx) {
				article.children[idx] = arlimaSerializeArticle($(this), santizeEntryWord);
			});
		}
		return article;
	}


	function arlimaSetStreamerFormState() {
		var type = $('#arlima-edit-article-options-streamer-type').val();

		if($('#arlima-edit-article-options-streamer').is(':checked')) {
			if( $("[name='options-streamer_image']").val() != '' ) {
				$('#arlima-edit-article-options-streamer-image-link').html('<img src="' + $("[name='options-streamer_image']").val() + '" width="300" style="vertical-align:middle;" />');
			}else{
				$('#arlima-edit-article-options-streamer-image-link').html(ArlimaJS.lang.chooseImage);
			}
			$('#arlima-edit-article-options-streamer-content').show(0);
			$('.arlima-edit-article-options-streamer-choice').not('#arlima-edit-article-options-streamer-' + type).hide(0);
			$('#arlima-edit-article-options-streamer-' + type).show(0);
			$('.arlima-streamer-activate').addClass('checked');
		}else{
			$('#arlima-edit-article-options-streamer-content').hide(0);
			$('.arlima-streamer-activate').removeClass('checked');
		}
	}

	function arlimaResetEditForm() {
		var editform = $("#arlima-edit-article-form");
		$(':input', editform).not(':button, :submit, :radio, :checkbox').val('');
		$(':input', editform).prop('checked', false).prop('selected', false);
		tinyMCEInstance().setContent('');
		$('#arlima-article-image').html('');
		$('#arlima-article-image-options').removeData('image_options');
		$('#arlima-article-image-options').hide();
		$('#arlima-article-connected-post').html('');
		$('#arlima-article-connected-post-change').show();
		$('#arlima-article-post_id').hide();

	}

	
	arlimaTinyMCEChanged = function() {
	//function arlimaTinyMCEChanged() {
		arlimaUpdateListItem();
		return true; // Continue handling
	};
	
	/******************************************** UPLOAD STUFF ****************************************/
	
	$('#arlima-article-image')

		// Update the drop zone class on drag enter/leave
		.bind('dragenter', function(ev) {
			$(ev.target).addClass('dragover');
			return false;
		})
		.bind('dragleave', function(ev) {
			$(ev.target).removeClass('dragover');
			return false;
		})

		// Allow drops of any kind into the zone.
		.bind('dragover', function(ev) {
			return false;
		})

		// Handle the final drop...
		.bind('drop', function(ev) {
			
			// this is for images dragged from the browser, not from the filesystem.
			ev.preventDefault();
			var dt = ev.originalEvent.dataTransfer;
			
			var types = [];
			for (var i=0,type; type=dt.types[i]; i++)
				types.push(type);

			// uploading from filesystem, return false and let plupload handle it instead 
			try {
				if(dt.types.indexOf('Files') != -1){
					return false;
				}
			} catch(e){}

			try {
				if(dt.types.contains("application/x-moz-file")){
					return false;
				}
			} catch(e){}
			
			var url = dt.getData('URL');
			if( !url || (url.toLowerCase().indexOf('.jpg') == -1 && url.toLowerCase().indexOf('.png') == -1) ) url = dt.getData('text/x-moz-url')
			if( !url || (url.toLowerCase().indexOf('.jpg') == -1 && url.toLowerCase().indexOf('.png') == -1) ) { 
				var html = dt.getData('text/html');
				var tmp = $(html);
				if( $(tmp).is('img') ) 
					url = $(tmp).attr('src');
				else if( $('img', tmp).length > 0 )
					url = $('img', tmp).attr('src');
				else
					url = null;
			}
			if( !url || (url.toLowerCase().indexOf('.jpg') == -1 && url.toLowerCase().indexOf('.png') == -1) ) return false;
			if(url.startsWith('http://trip/') || url.startsWith('http://trip.vk.se/')) {
				url = url.replace('/preview/', '/hires/');
			}
			
			var data = {
				action: 'arlima_upload',
				postid : $('#arlima-article-post_id').val(),
				imgurl : url,
				_ajax_nonce : ArlimaJS.arlimaNonce
			};
			
			$.post(ajaxurl, data, function(json) {
				var args = { html : json.html, size : 'full', attach_id : json.attach_id };
				arlimaUpdateMainImage ( args );
				arlimaUpdateListItem();
			}, 'json');

			ev.stopPropagation();
			return false;
		});
		
	var data = {
		action: 'arlima_upload',
		postid : $('#arlima-article-post_id').val(),
		_ajax_nonce : ArlimaJS.arlimaNonce
		
	};
		
	var uploader = new plupload.Uploader({
		runtimes : 'html5',
		container : 'arlima-article-image-container',
		max_file_size : '10mb',
		url : ArlimaJS.ajaxurl,
		drop_element : 'arlima-article-image',
		filters : [
			{title : "Image files", extensions : "jpg,gif,png"}
		],
		browse_button: 'arlima-article-image-browse',
		multi_selection: false,
		multipart : true,
		multipart_params : {
			action: 'arlima_upload',
			postid : null,
			_ajax_nonce : ArlimaJS.arlimaNonce
		}
	});
	
	uploader.bind('FileUploaded', function(up, file, res) {
		var json = $.parseJSON(res.response);
		var args = { html : json.html, size : 'full', attach_id : json.attach_id };
		arlimaUpdateMainImage ( args );
		arlimaUpdateListItem();
	});

	uploader.init();
	
	uploader.bind('FilesAdded', function(up, files) {
		uploader.settings.multipart_params.postid = $('#arlima-article-post_id').val();
		$.each(files, function(i, file) {
			$('#arlima-article-image').append(
				'<div id="' + file.id + '">' +
				file.name.substring(0, 10) + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
			'</div>');
		});
		up.refresh(); // Reposition Flash/Silverlight
		up.start();
	});

	uploader.bind('UploadProgress', function(up, file) {
		$('#' + file.id + " b").html(file.percent + "%");
	});

	uploader.bind('Error', function(up, err) {
		$('#filelist').append("<div>Error: " + err.code +
			", Message: " + err.message +
			(err.file ? ", File: " + err.file.name : "") +
			"</div>"
		);

		up.refresh(); // Reposition Flash/Silverlight
	});

	uploader.bind('FileUploaded', function(up, file) {
		$('#' + file.id + " b").html("100%");
	});
	
	/**************************************** END UPLOAD STUFF ****************************************/
	
});
