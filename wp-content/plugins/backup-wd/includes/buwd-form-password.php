<?php

class Buwd_Form_Password extends Buwd_Form_Element
{
    protected $validation_types = array('email');

    public function get_type()
    {
        return self::TYPE_PASSWORD;
    }

    public function overload(array $data)
    {
        if (isset($data["validation"])) {
            $validation_type = $data["validation"];
            $this->add_validator($validation_type);
        }

        parent::overload($data);
    }

    public function render()
    {
        $options = array(
            'id'    => $this->get_id(),
            'name'  => $this->get_name(),
            'type'  => $this->get_type(),
            'class' => $this->get_classes(),
            'value' => $this->get_value(),
        );

        $options += $this->get_attr();

        $element = new Buwd_Helper_Html("input", $options);

        return $element->render();
    }

    public function validate()
    {
        $this->_has_errors = false;
        $value = $this->get_value();
        foreach ($this->get_validators() as $type => $allowed) {
            if (in_array($type, $this->validation_types)) {
                $v_class = new Buwd_Form_Validate();
                $v_method_name = 'is_valid_' . $type;

                if ($value && !$v_class->$v_method_name($value)) {
                    $this->_has_errors = true;
                    $this->_errors = $v_class->get_errors();
                }
            }
        }

        return $this->_has_errors;
    }
}
