<?php

class Buwd_Form_Renderer
{

    protected function _render($tag, array $option, $inner = null)
    {
        $opList = array();
        foreach ($option as $k => $v) {
            $opList[] = $k . '="' . ($v) . '"';
        }
        $op = join(" ", $opList);

        if ($inner === null) {
            return "<$tag $op />";
        } else {
            return "<$tag $op>$inner</$tag>";
        }
    }

    public function render_select(Buwd_Form_Element $element)
    {
        $optionList = "";
        foreach ($element->get_choices() as $choice) {
            $opt = array();
            $opt['value'] = $choice['value'];
            if ($opt['value'] == $element->get_value()) {
                $opt['selected'] = "selected";
            } else if (isset($opt['selected'])) {
                unset($opt['selected']);
            }
            $opt['value'] = esc_html($opt['value']);
            $optionList .= $this->_render("option", $opt, $choice['label']);
        }

        $opt = array();
        $opt['name'] = $element->get_name();
        $opt['id'] = $element->get_id();
        $opt['class'] = $element->get_classes(true);

        return $this->_render("select", $opt, $optionList);
    }

    public function render_input(Buwd_Form_Element $element, $type)
    {
        $opt = array();
        $opt['id'] = $element->get_id();
        $opt['name'] = $element->get_name();
        $opt['type'] = $type;
        $opt['class'] = "regular-text " . $element->get_classes(true);
        $opt['value'] = esc_html($element->get_value());

        return $this->_render("input", $opt);
    }

    private function _escape($text)
    {
        return htmlentities($text, ENT_NOQUOTES, "UTF-8");
    }

    public function render_header($header)
    {
        return '<tr valign="top"><td colspan="2" class="buwd-section"><div class="buwd-flex"><div class="section-title">' . $header . '</div><div class="section-line"><div></div></div></div></td></tr>';
    }

    public function render_desc($desc)
    {
        return '<tr valign="top"><td colspan="2" class="buwd-section-desc"><div class="buwd-flex"><div class="section-desc">' . $desc . '</div></div></td></tr>';
    }

    public function render_row(Buwd_Form_Element $element)
    {
        $c = '';
        if ($element->get_header()) {
            $c .= $this->render_header($element->get_header());
        }

        if ($element->get_type() != 'time' || ($element->get_type() == 'time' && $element->get_child() == 1)) {
            if ($element->is_visible()) {
                $c .= '<tr valign="top" class="tr-' . $element->get_name() . '">';
            } else {
                $c .= '<tr valign="top" class="buwd-hide tr-' . $element->get_name() . '">';
            }

            /*
                    if($element->isRequired()) {
                        $req = '<span class="buwd-red">&nbsp;*</span>';
                    } else { */
            $req = '';
            /* } */

            $c .= '<td class="buwd-key">';
            $c .= '<label for="' . $element->get_name() . '">' . $element->get_label() . $req . '</label>';
            $c .= '</td>';
            $c .= '<td class="buwd-value">';
        }
        if ($element->has_hint()) {
            $hint = $element->get_hint();
            if (isset($hint['pos']) && $hint['pos'] == 'before') {
                $tag = isset($hint['tag']) ? $hint['tag'] : 'span';
                $c .= '<' . $tag . ' class="setting-description">' . $hint['html'] . '</' . $tag . '>';
            }
        }

        if ($element->get_type() == 'checkbox') {


        }
        $c .= $element->render() . PHP_EOL;

        if ($element->has_hint()) {
            $hint = $element->get_hint();
            if (!isset($hint['pos']) || (isset($hint['pos']) && $hint['pos'] == 'after')) {
                $tag = isset($hint['tag']) ? $hint['tag'] : 'span';
                $c .= '<' . $tag . ' class="setting-description">' . $hint['html'] . '</' . $tag . '>';
            }
        }

        if ($element->get_type() != 'time' || ($element->get_type() == 'time' && $element->get_child() == 2)) {
            $c .= '</td>';
            $c .= '</tr>';
        }

        return $c;
    }


}

?>