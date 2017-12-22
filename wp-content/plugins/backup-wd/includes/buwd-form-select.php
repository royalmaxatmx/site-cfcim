<?php

class Buwd_Form_Select extends Buwd_Form_Multiple
{

    public function get_type()
    {
        return self::TYPE_SELECT;
    }

    public function overload(array $data)
    {

        parent::overload($data);
    }

    public function render()
    {
        $name = $this->is_multiple() ? $this->get_name() . "[]" : $this->get_name();
        $options = array(
            'id'       => $this->get_id(),
            'name'     => $name,
            'class'    => $this->get_classes(),
            'type'     => $this->get_type(),
            'multiple' => $this->is_multiple(),
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
