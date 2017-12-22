<?php

/**
 *
 */
class Buwd_Form_Group
{
    /**
     *
     */
    protected $_errors = array();
    protected $_data = null;
    protected $_group = array();
    protected $_renderer = null;

    public function __construct($data)
    {
        $this->_renderer = new Buwd_Form_Renderer;

        $this->load_groups($data);
        $this->load_meta($data);
    }

    public function __get($key)
    {
        if ($key == 'fieldset') {
            return $this->_group;
        }
    }

    public function load_groups($options)
    {
        foreach ($options as $option) {
            $desc = isset($option['desc']) ? $option['desc'] : '';
            $this->add_group($option['key'], $option['title'], $desc);
        }
    }

    public function load_meta($options)
    {
        foreach ($options as $option) {
            foreach ($option['fields'] as $field) {
                $type = $field['type'] == 'hidden_row' ? 'hidden' : $field['type'];
                $element = $this->create($field['name'], $type);
                $element->overload($field);
                $element->set_value($field['value']);
                $this->add_element($element, $option["key"]);
            }
        }
    }

    public function add_group($key, $title = "", $desc = "")
    {
        if ($this->_data && $this->_data->has_group($key)) {
            $init = $this->_data->get_group($key);
        } else {
            $init = array(
                "name"  => $key,
                "title" => $title,
                "desc"  => $desc,
            );
        }

        $this->_group[$key] = new Buwd_Form($init);
    }

    public function add_element($element, $group)
    {
        $this->fieldset[$group]->add($element);
    }

    public function has_element($name)
    {
        if ($this->get_element($name) === null) {
            return false;
        } else {
            return true;
        }
    }

    public function get_element($name)
    {
        foreach ($this->fieldset as $k => $v) {
            if ($this->fieldset[$k]->has($name)) {
                return $this->fieldset[$k]->get($name);
            }
        }

        return null;
    }

    public function get_groups()
    {
        return $this->_group;
    }

    public function get_fields()
    {
        $fields = array();
        foreach ($this->fieldset as $group) {
            $fields += $group->get_all();
        }

        return $fields;
    }

    public function get_visible_fields()
    {
        $fields = array();
        foreach ($this->fieldset as $group) {
            $fields += $group->get_visible();
        }

        return $fields;
    }

    public function render(Buwd_Form $options)
    {
        $html = "";
        if (isset($options->title) && $options->title) {
            $html .= $this->_renderer->render_header($options->title);
        }

        if (isset($options->desc) && $options->desc) {
            $html .= $this->_renderer->render_desc($options->desc);
        }

        $fields = $options->get_all();
        foreach ($fields as $field) {
            if ($field->get_type() === Buwd_Form_Element::TYPE_HIDDEN_ROW) {
                $html .= $this->_renderer->render_input($field, 'hidden');
            } else if ($field->get_type() == "table") {
                //$html .= $this->_renderer->render_table();
            } else {
                $html .= $this->_renderer->render_row($field);
            }
        }

        return $html;
    }

    public function render_group($group)
    {
        if (isset($this->fieldset[$group])) {
            return $this->render($this->fieldset[$group]);
        }

        return null;
    }

    public function render_hidden()
    {
        $html = "";
        foreach ($this->fieldset as $fieldset) {
            foreach ($fieldset->get_all() as $field) {
                if ($field->get_type() === Buwd_Form_Element::TYPE_HIDDEN) {
                    $html .= $field->render();
                }
            }
        }

        return $html;
    }

    public function create($element, $type = 'text')
    {
        $class = ucfirst(BUWD_PREFIX) . "_Form_" . ucfirst($type);

        return new $class($element);
    }

    public function is_valid($values)
    {
        $is_valid = true;
        foreach ($this->get_visible_fields() as $field) {
            $value = null;
            if (isset($values[$field->get_name()])) {
                $value = $values[$field->get_name()];
            } else if ($field->get_type() == "checkbox") {
                $value = null;
            }

            if ($field->get_type() == Buwd_Form_Element::TYPE_FILE) {
                if (isset($_FILES[$field->get_name()])) {
                    $field->set_value($_FILES[$field->get_name()]);
                }
            } else {
                $field->set_value($value);
            }

            if ($field->validate()) {
                $is_valid = false;
                $this->_errors[$field->get_name()] = array();
                foreach ($field->get_errors() as $error) {
                    $this->_errors[$field->get_name()][] = $error;
                }
            }
        }

        return $is_valid;
    }

    public function get_errors()
    {
        return $this->_errors;
    }
}