<?php

/**
 * Class LinkedPickersInputField
 */
class LinkedPickersInputField extends TextLineField
{
    protected $linkedPickersMainIcon1 = 'glyphicon glyphicon-calendar';
    protected $linkedPickersMainIcon2 = 'glyphicon glyphicon-calendar';

    /**
     * @inheritdoc
     * Set default options for the linked pickers
     */
    public function init()
    {
        parent::init();

        if ($this->hasLinkedPickers()) {
            $this->field['linked_pickers'] = array_merge(
                array(
                    'mainIcon1' => 'glyphicon glyphicon-calendar',
                    'mainIcon2' => 'glyphicon glyphicon-calendar',
                    'label1' => 'Von:',
                    'label2' => 'Bis:',
                    'format1' => 'YYYY-MM-DD',
                    'format2' => 'YYYY-MM-DD',
                    'placeholder1' => 'Startzeitpunkt',
                    'placeholder2' => 'Endzeitpunkt',
                    'locale' => DBWEBGEN_LANG,
                ),
                $this->field['linked_pickers']
            );
        }
    }

    /**
     * Check if input field should be rendered as linked pickers
     * @return bool
     */
    public function hasLinkedPickers()
    {
        return ! is_null($this->getLinkedPickersOptions()) && is_array($this->getLinkedPickersOptions());
    }

    /**
     * Return the linked pickers options
     * @return mixed|null
     */
    public function getLinkedPickersOptions()
    {
        return isset($this->field['linked_pickers']) ? $this->field['linked_pickers'] : null;
    }

    /**
     * Extend the render function, also show linked pickers
     * @param $outputBuffer
     * @return string
     */
    protected function render_internal(&$outputBuffer)
    {
        if ($this->hasLinkedPickers()) {
            $html = '
              <div>{{label1}}</div>
              <div class="input-group date" id="{{idPrefix}}-from">
                <input type="text" class="form-control" placeholder="{{placeholder1}}" {{required}} {{disabled}} />
                <span class="input-group-addon">
                  <span class="{{mainIcon1}}"></span>
                </span>
              </div>
              
              <div>{{label2}}</div>
              <div class="input-group date" id="{{idPrefix}}-to">
                <input type="text" class="form-control" placeholder="{{placeholder2}}" {{required}} {{disabled}} />
                <span class="input-group-addon">
                  <span class="{{mainIcon2}}"></span>
                </span>
              </div>
              
              <input type="hidden" name="{{name}}__null__" value="false">
              <input type="text" name="{{name}}" id="{{idPrefix}}-hidden">
              
              <script type="text/javascript">
              $(function () {
                var $from = $("#{{idPrefix}}-from")
                var $to = $("#{{idPrefix}}-to")
                var $hidden = $("#{{idPrefix}}-hidden")
                
                $from.datetimepicker({
                  defaultDate: "{{value1}}",
                  format: "{{format1}}",
                  locale: "{{locale}}"
                })
                
                $to.datetimepicker({
                  defaultDate: "{{value2}}",
                  format: "{{format2}}",
                  locale: "{{locale}}"
                })
                
                $from.on("dp.change", function (e) {
                  $to.data("DateTimePicker").minDate(e.date)
                  $hidden.val(JSON.stringify([$from.data("date"), $to.data("date")]))
                })
                
                $to.on("dp.change", function (e) {
                  $from.data("DateTimePicker").maxDate(e.date)
                  $hidden.val(JSON.stringify([$from.data("date"), $to.data("date")]))
                })
                
                $hidden.val(JSON.stringify([$from.data("date"), $to.data("date")]))
              })
              </script>
            ';

            $date = $this->parseDate($this->get_submitted_value());

            $outputBuffer .= $this->parseHtml(
                $html,
                array_merge(
                    $this->getLinkedPickersOptions(),
                    array(
                        'idPrefix' => $this->get_control_id(),
                        'disabled' => $this->get_disabled_attr(),
                        'required' => $this->get_required_attr(),
                        'name' => $this->get_control_name(),
                        'value1' => $date[0],
                        'value2' => $date[1],
                    )
                )
            );

            return $outputBuffer;
        }

        return parent::render_internal($outputBuffer);
    }

    private function parseDate($value) {
        return json_decode($value, true);
    }

    /**
     * Simple HTML parser
     * @param $html
     * @param $arguments
     * @return mixed
     */
    private function parseHtml($html, $arguments)
    {
        foreach ($arguments as $key => $value) {
            $html = str_replace('{{'.$key.'}}', $value, $html);
        }

        return $html;
    }

    /**
     * Don't set value to null
     * @return bool
     */
    public function allow_setnull_box()
    {
        if ($this->hasLinkedPickers()) {
            return false;
        }

        parent::allow_setnull_box();
    }
}
