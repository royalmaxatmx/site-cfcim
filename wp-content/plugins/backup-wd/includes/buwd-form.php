<?php

class Buwd_Form
{
    public $title = "";
    public $desc = "";
    protected $_name = "";
    protected $_field = array();

    public function __construct($options = array())
    {
        if (isset($options["name"])) {
            $this->set_name($options["name"]);
            unset($options["name"]);
        }

        if (isset($options["title"])) {
            $this->title = $options["title"];
            unset($options["title"]);
        }

        if (isset($options["desc"])) {
            $this->desc = $options["desc"];
            unset($options["desc"]);
        }
    }

    public function set_name($name)
    {
        $this->_name = $name;
    }

    public function get_name()
    {
        return $this->_name;
    }

    public function add(Buwd_Form_Element $element)
    {
        $this->_field[$element->get_name()] = $element;
    }

    public function update(Buwd_Form_Element $element)
    {
        $this->_field[$element->get_name()] = $element;
    }

    public function has($field)
    {
        if ($field instanceof Buwd_Form_Element) {
            $name = $field->get_name();
        } else {
            $name = $field;
        }

        if (isset($this->_field[$name])) {
            return true;
        } else {
            return false;
        }
    }

    public function get($field)
    {
        if ($this->has($field)) {
            if ($field instanceof Buwd_Form_Element) {
                $name = $field->get_name();
            } else {
                $name = $field;
            }

            return $this->_field[$name];
        }
    }

    public function get_all()
    {
        return $this->_field;
    }

    public function get_visible()
    {
        $arr = array();
        foreach ($this->_field as $field) {
            if ($field->is_visible()) {
                $arr[] = $field;
            }
        }

        return $arr;
    }
}

?>