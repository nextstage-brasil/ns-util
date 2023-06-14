<?php

namespace NsUtil;

class Html
{

    public function __construct()
    {
    }

    public static function input(array $props, string $label): string
    {
        return '';
    }

    public static function hint($text, $position = "top")
    {
        return ' data-toggle="tooltip" data-placement="' . $position . '" data-html="true" title="' . $text . '" ';
    }

    /**
     * Retorna dois elementos interligados de datapicker
     * @param string $labelA
     * @param string $labelB
     * @param string $modelA
     * @param string $modelB
     * @param string $minDate
     * @param string $maxDate
     * @param string $onChange
     * @return \stdClass
     */
    public static function inputDatePickersGetLeftAndRight($labelA, $labelB, $modelA, $modelB, $minDate, $maxDate, $onChange = '')
    {
        $out = new \stdClass();
        $out->left = Html::inputDatePickerDependente($labelA, $modelA, $minDate, $modelB, $onChange);
        $out->right = Html::inputDatePickerDependente($labelB, $modelB, $modelA, $maxDate, $onChange);
        return $out;
    }

    /**
     *Retorna um elemento de datepicker com relação a outro
     *
     * @param string $label
     * @param string $model
     * @param boolean $minDate
     * @param boolean $maxDate
     * @param boolean $ngChange
     * @param boolean $readonly
     * @return void
     */
    public static function inputDatePickerDependente($label, $model, $minDate = false, $maxDate = false, $ngChange = false, $readonly = true)
    {
        $readonly = (($readonly) ? ' readonly="true"' : '');
        return self::input([
            'id' => md5((string)$model),
            'ng-if' => '!' . $model . '_ro',
            'readonly' => $model . '_ro',
            'name' => $model,
            'datepickernew' => '',
            'ng-model' => $model,
            'max-date' => '{{' . $maxDate . '}}',
            'min-date' => '{{' . $minDate . '}}',
            'ng-change' => $ngChange,
            'autocomplete' => 'off',
        ], $label);
    }
}
