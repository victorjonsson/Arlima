var ArlimaScheduledIntervalPicker  = (function($, window, ArlimaArticleForm) {

    var _this = {

        $fancyBox : false,

        open : function() {

            $.fancybox({
                href : '#scheduled-interval-fancybox',
                height: 400,
                width: 400,
                beforeLoad: function() {
                    var interval = ArlimaArticleForm.article.opt('scheduledInterval');
                    if( !interval ) {
                        interval = '*:*';
                        ArlimaArticleForm.change('.scheduled-interval', interval, true);
                    }

                    var $inputs = _this.$fancyBox.find('input');
                    $inputs.removeAttr('checked');
                    $.each(interval.split(':'), function(i, interval) {
                        if($.trim(interval) == '*') {
                            var className = i == 0 ? '.day':'.hour';
                            $inputs.filter(className).attr('checked', 'checked');
                        }
                        else {
                            $.each(interval.split(','), function(i, val) {
                                $inputs.filter('[value="'+val+'"]').attr('checked', 'checked');
                            });
                        }
                    });
                },
                afterClose : function() {
                    var _findCheckedValued = function(className) {
                        var values = '*';
                        var $inputs = _this.$fancyBox.find('.'+className);
                        if($inputs.filter(':checked').length != $inputs.length) {
                            values = '';
                            $inputs.filter(':checked').each(function() {
                                values += ','+ this.value;
                            });
                            if(values == '')
                                values = '*';
                            else
                                values = values.substr(1);
                        }
                        return values;
                    };

                    var newInterval = _findCheckedValued('day') +':'+ _findCheckedValued('hour');
                    if( newInterval != ArlimaArticleForm.article.opt('scheduledInterval') ) {
                        ArlimaArticleForm.change('.scheduled-interval', newInterval, true);
                    }

                }
            });
        },

        removePickedInterval : function() {
            ArlimaArticleForm.change('.scheduled-interval', '', true);
        },

        init : function($fancyBox) {

            // generate hour choices
            var $scheduledHours = $fancyBox.find('.hours').children().eq(0);
            for(var i=1; i < 25; i++) {
                var val = i < 10 ? '0'+i : i;
                var br = i % 8 === 0 ? '<br />':'';
                $('<label><input type="checkbox" class="hour" value="'+val+'" /> '+val+'</label>'+br).insertBefore($scheduledHours);
            }

            // Togglers
            $fancyBox.find('.toggler').click(function() {
                var $checkBoxes = $(this).parent().parent().find('input');
                if( $checkBoxes.filter(':checked').length ) {
                    $checkBoxes.removeAttr('checked');
                } else {
                    $checkBoxes.attr('checked', 'checked');
                }
                return false;
            });

            this.$fancyBox = $fancyBox;
        }
    };

    return _this;

})(jQuery, window, ArlimaArticleForm);
