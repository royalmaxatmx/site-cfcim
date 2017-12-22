<?php

class Buwd_Form_Multiple extends Buwd_Form_Element
{
    protected $_choices = array();
    protected $_multiple = false;
    protected $_column = null;
    protected $_has_errors = false;
    protected $validation_types = array('in_array');

    public function __construct($name)
    {
        $this->_name = $name;
    }

    public function set_attr($arr)
    {
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $s_k => $s_v) {
                    $this->_attr[$k][$s_k] = $s_v;
                }
            } else {
                $this->_attr[$k] = $v;
            }
        }
    }

    public function overload(array $data)
    {
        parent::overload($data);

        if (isset($data['column'])) {
            $this->set_column($data['column']);
        }

        if (isset($data['multiple'])) {
            $this->set_multiple($data['multiple']);
        }

        foreach ($data['choices'] as $val => $label) {
            $this->add_choice($val, $label);
        }

        if (isset($data['attr'])) {
            $this->set_attr($data['attr']);
        }

    }

    public function add_choice($value, $label)
    {
        $this->_choices[] = array(
            "value" => $value,
            "label" => $label
        );
    }

    public function add_choices($choices)
    {
        foreach ($choices as $choice) {
            $this->add_choice($choice["value"], $choice["label"]);
        }
    }

    public function set_multiple($multiple)
    {
        $this->_multiple = intval($multiple);
    }

    public function is_multiple()
    {
        return $this->_multiple;
    }

    public function get_column()
    {
        return $this->_column;
    }

    public function set_column($column)
    {
        $this->_column = $column;
    }

    public function get_choices()
    {
        return $this->_choices;
    }

    public function remove_choices()
    {
        $this->_choices = array();
    }

    public function validate()
    {
        $arr = array();

        $value = (array)$this->get_value();
        foreach ($value as $v) {
            if (is_array($v)) {
                $v = null;
            } else if (is_string($v)) {
                $v = trim($v);
            }

            if (!empty($v)) {
                $arr[] = $v;
            }
        }

        $allowed = array();
        foreach ($this->get_choices() as $opt) {
            $allowed[] = trim($opt["value"]);
        }
        $this->add_validator('in_array', $allowed);

        foreach ($this->get_validators() as $type => $allowed) {
            if (in_array($type, $this->validation_types)) {
                $v_class = new Buwd_Form_Validate();
                $v_method_name = 'is_valid_' . $type;
                if (!empty($arr) && !$v_class->$v_method_name($value, $allowed)) {
                    $this->_has_errors = true;
                    $this->_errors = $v_class->get_errors();
                }
            }
        }

        return $this->_has_errors;
    }

    public function dump()
    {
        $dump = parent::dump();
        $dump->choices = $this->_data["choices"];

        return $dump;
    }

}