<?php

/**
 *
 */
abstract class Buwd_Form_Element
{
    const TYPE_TEXT       = "text";
    const TYPE_PASSWORD   = "password";
    const TYPE_NUMBER     = "number";
    const TYPE_RADIO      = "radio";
    const TYPE_CHECKBOX   = "checkbox";
    const TYPE_SELECT     = "select";
    const TYPE_FILE       = "file";
    const TYPE_TEXTAREA   = "textarea";
    const TYPE_HIDDEN     = "hidden";
    const TYPE_HIDDEN_ROW = "hidden_row";
    const TYPE_PARAGRAPH  = "paragraph";
    const TYPE_TIME       = "time";
    const TYPE_DAY        = "day";

    protected $_id = null;
    protected $_label = null;
    protected $_name = '';
    protected $_classes = array();
    protected $_value = null;
    protected $_required = false;
    protected $_header = null;
    protected $_hint = array();
    protected $_visible = true;
    protected $_attr = array();
    protected $_data = array();
    protected $_errors = array();
    protected $_validator = array();

    public function __construct($name)
    {
        $this->_name = $name;
    }

    public function set_id($id)
    {
        $this->_id = $id;
    }

    public function get_id()
    {
        return $this->_id;
    }

    public function get_label()
    {
        return $this->_label;
    }

    public function set_label($label)
    {
        $this->_label = $label;
    }

    public function set_name($name)
    {
        $this->_name = $name;
    }

    public function get_name()
    {
        return $this->_name;
    }

    public function set_attr($arr)
    {
        foreach ($arr as $k => $v) {
            $this->_attr[$k] = $v;
        }
    }

    public function get_attr()
    {
        return $this->_attr;
    }

    public function get_hint()
    {
        return $this->_hint;
    }

    public function has_hint()
    {
        if (empty($this->_hint)) {
            return false;
        }

        return true;
    }

    public function set_hint($hint)
    {
        $this->_hint = $hint;
    }

    public function add_classes($class)
    {
        $this->_classes = $class;
    }

    public function get_classes()
    {
        if (is_array($this->_classes)) {
            return join(' ', $this->_classes);
        } else {
            return $this->_classes;
        }
    }

    public function set_required($req = false)
    {
        $this->_required = $req;
    }

    public function get_required()
    {
        return $this->_required;
    }

    public function set_value($value)
    {
        $this->_value = $value;
    }

    public function get_value()
    {
        return $this->_value;
    }

    public function has_errors()
    {
        return (bool)count($this->_errors);
    }

    public function get_errors()
    {
        return $this->_errors;
    }

    public function set_error($error)
    {
        $this->_errors[] = $error;
    }

    public function set_header($header)
    {
        $this->_header = $header;
    }

    public function get_header()
    {
        return $this->_header;
    }

    public function add_validator($type, $validation_data = array())
    {
        $this->_validator[$type] = $validation_data;

        return $this;
    }

    public function get_validators()
    {
        return $this->_validator;
    }

    public function is_visible()
    {
        return (bool)$this->_visible;
    }

    public function set_visible($visible)
    {
        $this->_visible = ( bool )$visible;
    }

    public function overload(array $data)
    {
        $this->_data = $data;

        if (isset($data["id"])) {
            $this->set_id($data["id"]);
        }

        if (isset($data["label"])) {
            $this->set_label($data["label"]);
        }

        if (isset($data["hint"])) {
            $this->set_hint($data["hint"]);
        }

        if (isset($data["visibility"])) {
            $this->set_visible($data["visibility"]);
        }

        if (isset($data["class"])) {
            $this->add_classes($data["class"]);
        }

        if (isset($data["header"])) {
            $this->set_header($data["header"]);
        }

        if (isset($data["attr"])) {
            $this->set_attr($data["attr"]);
        }

        if (isset($data["is_required"]) && $data["is_required"]) {
            $this->set_required(true);
        } else {
            $this->set_required(false);
        }

    }

    public function dump()
    {
        $f = new stdClass();
        $f->title = $this->get_label();
        $f->name = $this->get_name();
        $f->value = $this->get_value();
        $f->classes = $this->get_classes();
        $f->required = $this->get_required();
        $f->type = $this->get_type();

        if (isset($this->_data["visibility"])) {
            $f->visibility = $this->_data["visibility"];
        }

        return $f;
    }

    public function __toString()
    {
        return $this->render();
    }
}