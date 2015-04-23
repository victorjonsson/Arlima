var ArlimaFormBuilder = (function($, ArlimaJS) {

    var defaultFieldDefinition = {
            type: 'text',
            property : '', // name of article property (can also be an article option by prefixing with opt:)
            value : '', // The default value
            width: '100%',
            rows : false, // used with type=text
            label : {
                text : '',
                display: 'inline',
                description : ''
            }
        },
        _createTextFieldHTML = function(f, extraAttrs) {
            var placeholder = f.label.placeholder ? ' placeholder="'+f.label.placeholder+'"' : '';
            extraAttrs = extraAttrs || '';
            if(f.rows) {
                return '<textarea '+extraAttrs+' rows="'+ f.rows+'" data-prop="'+ f.property+'"'+placeholder+'>'+ f.value+'</textarea>';
            } else {
                return '<input '+extraAttrs+' type="text" data-prop="'+ f.property+'" value="'+ f.value +'"'+placeholder+' />';
            }
        },
        _createNumberFieldHTML = function(f) {
            return '<input type="number" data-prop="'+ f.property+'" value="'+ f.value +'" />';
        },
        _createBoolFieldHTML = function(f) {
            var yesVal = f.label.yes || ArlimaJS.lang.yes,
                noVal = f.label.no || ArlimaJS.lang.no,
                selectYes = f.value ? ' selected="selected"':'',
                selectNo = f.value ? '':' selected="selected"';
            return '<select data-prop="'+ f.property+'"><option value="1"'+selectYes+'>'+yesVal+'</option><option value=""'+selectNo+'>'+noVal+'</option></select>';
        },
        _createListHTML = function(f) {
            var opts = '';
            $.each(f.options.split(','), function(i, val) {
                val = $.trim(val);
                opts += '<option value="'+val+'"'+( val == f.value ? ' selected="selected"':'')+'>'+val+'</option>';
            });
            return '<select data-prop="'+ f.property+'">' +opts+ '</select>';
        };

    return {

        $elem : null,

        TYPES : {
            BOOL : 'boolean',
            TEXT : 'text',
            NUMBER : 'number',
            LIST : 'list',
            DATE : 'date'
        },

        defaultFieldDefinition : defaultFieldDefinition,

        /**
         * @param {defaultFieldDefinition} field
         * @returns {string}
         */
        getFieldHTML : function(field) {
            var fieldDisplay = !field.display || field.display == 'block' ? 'block':'inline',
                labelDisplay = field.label && field.label.display ? field.label.display:'block',
                labelDisplayClass = 'label-display-'+labelDisplay,
                fieldWidthStyle = !field.width ? '':' style="width:'+field.width+'"',
                fieldDesc = field.label.description ? ' title="'+field.label.description+'"':'',
                html = '<div class="form-field '+fieldDisplay+' '+labelDisplayClass+' type-'+ field.type+'"'+fieldWidthStyle+fieldDesc+'>';

            if(!field.label.placeholder && field.label.text)
                html += '<strong>'+field.label.text+'</strong>';


            switch (field.type) {
                case this.TYPES.BOOL:
                    html += _createBoolFieldHTML(field);
                    break;
                case this.TYPES.TEXT:
                    html += _createTextFieldHTML(field);
                    break;
                case this.TYPES.DATE:
                    html += _createTextFieldHTML(field, 'style="position: relative; z-index: 100000;"');
                    break;
                case this.TYPES.NUMBER:
                    html += _createNumberFieldHTML(field);
                    break;
                case this.TYPES.LIST:
                    html += _createListHTML(field);
                    break;
                default :
                    html += '<em>Unknown field type '+ field.type+'</em>';
                    break;
            }

            return html + '</div>';
        },

        addFields : function(fields) {
            var html = '',
                dateFields = [],
                _this = this;

            $.each(fields, function(i, f) {
                html += _this.getFieldHTML(f);
                if(f.type == _this.TYPES.DATE) {
                    dateFields.push(f);
                }
            });
            this.$elem.append(html);

            setTimeout(function() {
                if( dateFields.length ) {
                    $.each(dateFields, function(i, f) {
                        var settings = f.settings || {};
                        _this.$elem.find('input[data-prop="' +f.property+ '"]').datepicker(settings);
                    });
                }
            }, 1000);
        },

        inputs : function() {
            return this.$elem.find('.form-field input, .form-field select, .form-field textarea');
        },

        clear : function() {
            this.$elem.find('.form-field').remove();
        },

        init : function($formPart) {
            this.$elem = $formPart;
        }

    };

})(jQuery, ArlimaJS);