<?php

class Buwd_List_Table
{
    protected $info = null;

    public function __construct($defaults = array())
    {
        $default_orderby = isset($defaults['orderby']) ? $defaults['orderby'] : 'id';
        $this->info['order'] = $this->get_order();
        $this->info['orderby'] = $this->get_orderby($default_orderby);
        $this->info['current_action'] = $this->get_current_action();
    }

    public function get_order()
    {
        return Buwd_Helper::get("order") ? Buwd_Helper::get("order") : 'asc';
    }

    public function get_orderby($default_value)
    {
        return Buwd_Helper::get("orderby") && in_array(Buwd_Helper::get("orderby"), $this->info['sortable_columns']) ? Buwd_Helper::get("orderby") : $default_value;
    }

    public function get_current_action()
    {
        return Buwd_Helper::get("action") ? Buwd_Helper::get("action") : '';
    }

    public function action_delete($option, $type)
    {
        $elements = Buwd_Helper::get($option) ? ( array )Buwd_Helper::get($option) : array();
        if (empty($elements)) {
            return;
        }

        /* if( $type == 'option' ) { */
        foreach ($elements as $element) {
            Buwd_Options::delete_job($element);
        }
        /*} */

    }
}
