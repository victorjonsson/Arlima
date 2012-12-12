
(function($) {

    // Make contains function case sensitive
    $.expr[':'].Contains = function(a, i, m) {
        return $(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
    };

    //turns a form/collection into a key->value json object
    $.fn.serializeObject = function(){
        var o = {};
        var a = this.serializeArray();
        $.each(a, function() {
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

    $.fn.arlimaListSearch = function(listChildrenQuery) {
        return this.keyup(function(e) {
            var key = e.keyCode ? e.keyCode : e.which;
            if (key == '13') {
                e.preventDefault();
            }
            var search = $.trim($(this).val());
            if(search.length > 0) {
                $(listChildrenQuery+":Contains('" + search + "')").show();
                $(listChildrenQuery).not(":Contains('" + search + "')").hide();
            } else {
                $(listChildrenQuery).hide();
            }
        })
        .blur(function() {
          setTimeout(function() {
              $(listChildrenQuery).hide();
          }, 500); // this has to be done after a while, otherwise the children wont be possible to click
        })
        .focus(function() {
            $(this).trigger('keyup');
        });
    };

    /**
     * jQuery utility function used for getting or setting content
     * of the tinyMCE editor
     * @param {String|undefined} content - Leave empty to get content of editor
     * @return {String|undefined}
     */
    $.tinyMCEContent = function(content) {
        var hasActiveEditor = tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden();

        // Set
        if(content !== undefined) {
            if(hasActiveEditor)
                tinyMCE.activeEditor.setContent(content);
            else
                $('#tinyMCE').val( content );
        }

        // Get
        else {
            if(hasActiveEditor)
                return tinyMCE.activeEditor.getContent();
            else
                return $('#tinyMCE').val();
        }
    };
    
})(jQuery);