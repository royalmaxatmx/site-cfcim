<?php

class Buwd_Form_Checkbox extends Buwd_Form_Multiple
{

    public function get_type()
    {
        return self::TYPE_CHECKBOX;
    }

    public function overload(array $data)
    {

        parent::overload($data);
    }

    public function render()
    {
        $label_options = array();
        $options = array(
            'class' => $this->get_classes(),
            'type'  => $this->get_type(),
        );

        $unique_attr = array();
        $unique_label_attr = array();
        foreach ($this->get_attr() as $attr_k => $attr_v) {
            if (is_array($attr_v)) {
                foreach ($attr_v as $a_k => $a_v) {
                    if ($attr_k != 'title') {
                        $unique_attr[$a_k][$attr_k] = $a_v;
                    } else {
                        $unique_label_attr[$a_k]['title'] = $a_v;
                    }
                }
            } else {
                $options += [$attr_k => $attr_v];
            }
        }

        $choices = $this->get_choices();

        $c = array();

        $hidden_elements = array();
        $column = $this->get_column();
        if (count($choices)) {
            foreach ($choices as $key => $choice) {
                $options['id'] = $this->get_id() . '-' . $choice['value'];
                $options['value'] = $choice['value'];
                $options['name'] = $this->get_name() . '[]';
                $options['checked'] = false;
                if (in_array($choice['value'], (array)$this->get_value())) {
                    $options['checked'] = 'checked';
                }

                if (!empty($unique_attr) && isset($unique_attr[$choice['value']])) {

                    $element = new Buwd_Helper_Html("input", $options + $unique_attr[$choice['value']]);

                    if (isset($unique_attr[$choice['value']]['visibility'])) {
                        $hidden_elements[] = $key;
                    }

                } else {
                    $element = new Buwd_Helper_Html("input", $options);
                }

                if (isset($options['icon']) && $options['icon'] == true) {
                    $span = new Buwd_Helper_Html("span", array('class' => 'buwd-logo ' . $options['id'] . '-logo'), '');
                    $label_html = $span . $choice['label'];

                } else {
                    $label_html = $choice['label'];
                }

                $label_options['for'] = $options['id'];
                if (!empty($unique_label_attr) && isset($unique_label_attr[$choice['value']])) {
                    $label = new Buwd_Helper_Html("label", $label_options + $unique_label_attr[$choice['value']], $label_html);
                } else {
                    $label = new Buwd_Helper_Html("label", $label_options, $label_html);
                }

                $html = $element->render() . ' ' . $label->render();
                $div = new Buwd_Helper_Html("div", array(), $html);

                $c[$key] = $div->render();
            }

            if ($column) {
                /*	$col = array();
                    for ( $k = 0; $k <= $n; $k ++ ) {
                        $column_div = new Buwd_Helper_Html( "div", array( 'class' => 'buwd-column buwd-column-' . $column ), implode( '', array_slice( $c, $k * $column_count, $column_count ) ) );
                        $col[] = $column_div->render();
                    }*/

                $col = array();
                $hide_class = '';
                foreach ($c as $_key => $_c) {
                    $hide_class = '';
                    if (in_array($_key, $hidden_elements)) {
                        $hide_class = 'buwd-hide';
                    }
                    $column_div = new Buwd_Helper_Html("div", array('class' => 'buwd-column buwd-column-' . $column . ' ' . $hide_class), $_c);
                    $col[] = $column_div->render();
                }

                $columns_div = new Buwd_Helper_Html("div", array('class' => 'buwd-columns'), implode('', $col));

                return $columns_div->render();
            }

            return implode('', $c);
        }
    }


}
