<?php


/**
 * Widget displaying an article list
 *
 * @since 2.5.9
 * @package Arlima
 */
class Arlima_Widget extends WP_Widget {

    const WIDGET_PREFIX = 'arlima-widget';

    /**
     * @param string $id
     * @param string $name
     */
    function __construct($id=null, $name='Arlima Widget')
    {
        if( $id === null )
            $id = self::WIDGET_PREFIX;

        parent::__construct($id, $name);

        $this->widget_options = array(
                    'classname' => self::WIDGET_PREFIX,
                    'description'=>__('Widget that displays an article list', 'arlima')
                );
    }

    /**
     * @param array $args
     * @param array $instance
     */
    function widget($args, $instance)
    {
        echo $args['before_widget'];

        if( !empty($instance['title']) ) {
            echo $args['before_title'] .$instance['title']. $args['after_title'];
        }

        $factory = new Arlima_ListFactory();
        $list = $factory->loadList($instance['list']);
        if( !empty($instance['template']) ) {
            $list->setOption('template', $instance['template']);
        }

        arlima_render_list($list, $instance);

        echo $args['after_widget'];
    }

    /**
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    function update($new_instance, $old_instance)
    {
        $new_instance['title'] = strip_tags($new_instance['title']);
        $new_instance['limit'] = (int)$new_instance['limit'];
        $new_instance['offset'] = (int)$new_instance['offset'];
        return $new_instance;
    }

    /**
     * @param array $instance
     * @return string|void
     */
    function form($instance)
    {
        $instance = array_merge(array(
                'width' => 100,
                'list' => '',
                'offset' => 0,
                'limit' => 0,
                'filter_suffix' => 'widget',
                'template' => '',
                'title' => ''
            ), $instance);

        $factory = new Arlima_ListFactory();
        $lists = $factory->loadListSlugs();

        ?>
        <table cellpadding="5">
            <tr>
                <td><strong><?php _e('Title', 'arlima') ?>:</strong></td>
                <td><input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title'] ?>" style="width:120px"></td>
            </tr>
            <tr>
                <td><strong><?php _e('List', 'arlima') ?>:</strong></td>
                <td>
                    <select id="<?php echo $this->get_field_id( 'list' ); ?>" name="<?php echo $this->get_field_name( 'list' ); ?>">
                        <?php foreach ($lists as $list_data): ?>
                            <option value="<?php echo $list_data->id; ?>"<?php
                                if ( $instance['list'] ==  $list_data->id ){
                                    echo ' selected="selected"';
                                }
                                ?>><?php echo $list_data->title; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><strong><?php _e('Width', 'arlima') ?>:</strong></td>
                <td><input type="number" id="<?php echo $this->get_field_id( 'width' ); ?>" name="<?php echo $this->get_field_name( 'width' ); ?>" value="<?php echo $instance['width'] ?>" style="width:50px"/> px</td>
            </tr>
            <tr>
                <td><strong><?php _e('Offset', 'arlima') ?>:</strong></td>
                <td><input type="number" id="<?php echo $this->get_field_id( 'offset' ); ?>" name="<?php echo $this->get_field_name( 'offset' ); ?>" value="<?php echo $instance['offset'] ?>" style="width:50px"/></td>
            </tr>
            <tr>
                <td><strong><?php _e('Limit', 'arlima') ?>:</strong></td>
                <td><input type="number" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" value="<?php echo $instance['limit'] ?>" style="width:50px"/></td>
            </tr>
            <tr>
                <td><strong><?php _e('Filter suffix', 'arlima') ?>:</strong></td>
                <td><input type="text" id="<?php echo $this->get_field_id( 'filter_suffix' ); ?>" name="<?php echo $this->get_field_name( 'filter_suffix' ); ?>" value="<?php echo $instance['filter_suffix'] ?>" style="width: 120px" /></td>
            </tr>
            <tr>
                <td><strong><?php _e('Template', 'arlima') ?>:</strong></td>
                <td>
                    <select id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>">
                        <option value="">- - <?php _e('No override', 'arlima') ?> - -</option>
                        <?php
                        $tmpl = new Arlima_TemplatePathResolver();
                        foreach($tmpl->getTemplateFiles() as $file) {
                            $selected = $file['name'] == $instance['template'] ? ' selected="selected"':'';
                            echo sprintf('<option value="%s"%s>%s</option>', $file['name'], $selected, $file['label']);
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
    <?php
    }
}
register_widget('Arlima_Widget');