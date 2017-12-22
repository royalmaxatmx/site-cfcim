<?php

class Buwd_Form_Day extends Buwd_Form_Element
{

    const DAYS_COUNT   = 28;
    const COLUMN_COUNT = 6;

    public function get_type()
    {
        return self::TYPE_DAY;
    }

    public function overload(array $data)
    {
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
        $html = $this->day_html();
        $element = new Buwd_Helper_Html("div", $options, $html);

        return $element->render();
    }

    public function validate()
    {
        return false;
    }

    private function day_html()
    {
        $values = $this->get_value() ? explode(",", $this->get_value()) : array();

        $html = '<table class="buwd_days" id="buwd_days">';
        $rows_count = ceil(self::DAYS_COUNT / self::COLUMN_COUNT);

        $start_day = 1;
        $end_day = self::COLUMN_COUNT;
        for ($j = 0; $j < $rows_count; $j++) {
            $html .= '<tr>';
            for ($i = $start_day; $i <= $end_day; $i++) {
                $active_class = in_array($i, $values) ? " buwd-day-span-active" : "";
                $html .= '<td><span class="buwd-day-span ' . $active_class . '" data-value="' . $i . '">' . $i . '</span></td>';
            }
            if ($end_day == self::DAYS_COUNT) {
                $active_class = in_array("L", $values) ? " buwd-day-span-active" : "";
                $html .= '<td title="Last day of month" class="buwd-last-day"><span class="buwd-day-span ' . $active_class . '" data-value="L">' . __("L", "buwd") . '</td>';
            }

            $start_day += self::COLUMN_COUNT;
            $end_day += self::COLUMN_COUNT;
            $end_day = $end_day > self::DAYS_COUNT ? self::DAYS_COUNT : $end_day;

            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }


}
