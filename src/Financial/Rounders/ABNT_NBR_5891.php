<?php

namespace NsUtil\Financial\Rounders;

use NsUtil\Financial\Contracts\Rounders;

class ABNT_NBR_5891 implements Rounders
{
    private $value, $precision, $config;

    public function __construct(float $value, int $precision = 2, array $config = [])
    {
        $this->value = $value;
        $this->precision = $precision;
        $this->config = [];
    }
    /**
     * Arredonda um valor monetário de acordo com as regras de arredondamento especificadas.
     *
     * @param float $value O valor a ser arredondado.
     * @param int $precision O número de casas decimais desejado (padrão é 2).
     * @return float O valor arredondado.
     */
    public function round(): float
    {
        return round($this->value, $this->precision, self::_getRoundByRules());
    }

    /**
     * Obtém a regra de arredondamento com base na ABNT/NBR 5891/1977
     *
     * @param float $num
     * @param integer $precision
     * @return int
     */
    public function _getRoundByRules(): int
    {
        $num = $this->value;
        $precision = $this->precision;

        $num_parts = explode('.', (string)$num);
        if (!isset($num_parts[1])) {
            return PHP_ROUND_HALF_UP;
        }

        $decimal_parts = str_split($num_parts[1]);
        if (!isset($decimal_parts[$precision - 1])) {
            // Menos decimais do que especificado, retorne qualquer modo
            return PHP_ROUND_HALF_UP;
        }

        $target_decimal = (int)$decimal_parts[$precision - 1];

        // Se não houver mais partes decimais além do dígito alvo, considere o próximo dígito como 0
        $next_decimal = isset($decimal_parts[$precision]) ? (int)$decimal_parts[$precision] : 0;

        if ($target_decimal < 5) {
            // Regra 1
            return PHP_ROUND_HALF_DOWN;
        } else if ($target_decimal > 5 || ($target_decimal === 5 && $next_decimal !== 0)) {
            // Regra 2
            return PHP_ROUND_HALF_UP;
        } else if ($target_decimal === 5 && $next_decimal === 0) {
            // Regras 3 e 4
            return $target_decimal % 2 === 0 ? PHP_ROUND_HALF_DOWN : PHP_ROUND_HALF_UP;
        } else {
            // Caso padrão (não deveria ocorrer)
            return PHP_ROUND_HALF_UP;
        }
    }
}
