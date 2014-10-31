/* Not yet in use
var ArlimaArticleFormBuilder = (function($) {


    var _createBoolFieldHTML = function(varName, selected) {
            return '<select><option value="1">Yes</option><option value="">Nej</option></select>';
        };


    return {

        $form : null,

        TYPES : {
            BOOL : 'boolean',
            TEXT : 'text'
        },

        getFieldHTML : function(type, label, varName, value) {
            var html = '<div class="form-field"><strong>'+label+'</strong><div class="field">';
            switch (type) {
                case this.TYPES.BOOL:
                    html += _createBoolFieldHTML(label, varName, value);
                    break;
                default :
                    html += '<em>Unknown field type '+type+'</em>';
                    break;
            }

            return html + '</div></div>';
        },

        addFields : function(fields) {
            var html = '',
                _this = this;
            $.each(fields, function(i, f) {
                html += _this.getFieldHTML(f.type, f.label, f.varName, f.value);
            });
            this.$form.append(html);
        },

        removeField : function(varName) {
            this.$form.find('*[data-prop="'+varName+'"]').remove();
        }

    };

})(jQuery); */