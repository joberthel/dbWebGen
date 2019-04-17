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
                    'template' => '[$1, $2)',
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
              
              <input type="hidden" name="{{name}}" id="{{idPrefix}}-hidden">
              
              <script type="text/javascript">
              $(function () {
                var $from = $("#{{idPrefix}}-from")
                var $to = $("#{{idPrefix}}-to")
                var $hidden = $("#{{idPrefix}}-hidden")
                
                function format () {
                  var template = "{{template}}"
                  $hidden.val(template.replace("$1", $from.data("date")).replace("$2", $to.data("date")))
                }
                
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
                  format()
                })
                
                $to.on("dp.change", function (e) {
                  $from.data("DateTimePicker").maxDate(e.date)
                  format()
                })
                
                format()
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
                        'template' => str_replace('"', '\"', $this->getLinkedPickersOptions()['template']),
                    )
                )
            );

            return $outputBuffer;
        }

        return parent::render_internal($outputBuffer);
    }

    private function parseDate($value) {
        $template = $this->getLinkedPickersOptions()['template'];

        preg_match_all('/\$[1-2]/', $template, $matches, PREG_OFFSET_CAPTURE);
        $start = $matches[0][0][1];
        $end = strlen($template) - $matches[0][1][1] - 2;

        preg_match('/\$[1-2](.*)\$[1-2]/', $template, $matches);
        $between = $matches[1];

        $value = substr($value, $start);
        $value = substr($value, 0, strlen($value) - $end);

        $response = explode($between, $value);

        if (strpos($template, '$2') < strpos($template, '$1')) {
            array_reverse($response);
        }

        return $response;
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
