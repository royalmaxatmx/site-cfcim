<?php

class Buwd_Form_Hidden extends Buwd_Form_Element
{
    protected $validation_types = array();
    public $type = 'TYPE_HIDDEN';

    public function get_type()
    {
        return $this->type;
    }

    public function overload(array $data)
    {
        parent::overload($data);
        if (isset($data["type"])) {
            $this->set_type($data["type"]);
        }
    }

    public function set_type($type)
    {
        $this->type = $type;
    }

    public function render()
    {
        $options = array(
            "id"    => $this->get_id(),
            "name"  => $this->get_name(),
            "class" => $this->get_classes(),
            "value" => $this->get_value(),
            "type"  => $this->get_type()
        );

        $options += $this->get_attr();

        $input = new Buwd_Helper_Html("input", $options);

        return $input->render();
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
