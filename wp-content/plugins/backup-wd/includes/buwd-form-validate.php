<?php

class Buwd_Form_Validate
{
    protected $_errors = array();

    public function is_valid_email($value)
    {
        $value = explode(',', $value);
        foreach ($value as $v) {
            $preg = "/^([a-zA-Z0-9_\-\.\+])+@([a-zA-Z0-9_\-\.])+(\.[a-zA-Z0-9_\-]+)+/";
            if (!preg_match($preg, trim($v))) {
                $this->set_error(__("Email address is invalid", "buwd"));

                return false;
            }
        }

        return true;
    }

    //TODO remove comment from foreach
    public function is_valid_in_array($value, $allowed)
    {
        /*foreach((array)$value as $v) {
            if(!in_array( $v, $allowed )) {
                $this->set_error(__("Unrecognized value", "buwd"));
                return false;
            }
        }*/

        return true;
    }


    public function set_error($error)
    {
        $this->_errors[] = $error;
    }

    public function get_errors()
    {
        return $this->_errors;
    }

}
