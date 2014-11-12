<?php
$args = arlima_file_args(array(
    array(
        'type' => 'date',
        'property' => 'count_down_to',
        'value' => date('Y-m-d', time()+86400),
        'width' => '30%',
        'label' => array(
            'text' => 'Count down to:'
        ),
        'settings' => array(
            'dateFormat' => 'yy-mm-dd'
        )
    ),
    array(
        'property' => 'text',
        'value' => '%s left until christmas eve 2014',
        'label' => array(
            'text' => 'Text:',
            'description' => 'Text to be displayed when count down finished'
        )
    )
));
if( !$args ) {
    // Important to return immediately if arlima_file_args() returned
    // false, this means that Arlima has included the file only to
    // get information about which arguments the file has 
    return;
}
?>
<div class="tmpl-article count-down">
    <?php printf($args['text'], human_time_diff(strtotime($args['count_down_to'])) ) ?>
</div>