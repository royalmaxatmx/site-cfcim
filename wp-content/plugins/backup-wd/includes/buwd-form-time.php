<?php

class Buwd_Form_Time extends Buwd_Form_Multiple
{
    var $_child = null;

    public function get_type()
    {
        return self::TYPE_TIME;
    }

    public function get_child()
    {
        return $this->_child;
    }

    public function set_child($child)
    {
        $this->_child = $child;
    }


    public function overload(array $data)
    {
        $this->set_child($data['child']);

        parent::overload($data);
    }

    public function render()
    {
        $name = $this->get_name();
        $options = array(
            'id'    => $this->get_id(),
            'name'  => $name,
            'class' => $this->get_classes(),
            'type'  => $this->get_type(),
        );

        $options += $this->get_attr();

        $choices = $this->get_choices();

        $html = '';
        if (count($choices)) {
            foreach ($choices as $key => $choice) {
                $selected = null;
                if (in_array($choice['value'], (array)$this->get_value())) {
                    $selected = "selected";
                }

                $option = new Buwd_Helper_Html("option", array(
                    'value'    => $choice['value'],
                    'selected' => $selected,
                ), $choice['label']);
                $html .= $option->render();
            }

            $element = new Buwd_Helper_Html("select", $options, $html);

            return $element->render();
        }
    }
}
