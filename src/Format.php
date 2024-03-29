<?php

namespace NsUtil;

use DateTime;
use DateTimeZone;
use Exception;

class Format
{

    private $timezone;
    private $string;

    public function __construct($string = false, $timezone = 'America/Sao_Paulo')
    {
        $this->timezone = $timezone;
        $this->string = $string;
    }

    public function setString($string)
    {
        $this->string = $string;
        return $this;
    }

    /**
     * 
     * @param string $escolha
     * @param bool $datahora
     * @param bool $alterarTimeZone
     * @return mixed
     */
    public function date($escolha = 'arrumar', $datahora = false, $alterarTimeZone = false)
    {
        $data = $this->string;
        if ($data !== 'NOW') {
            if (strlen((string) $data) < 6) {
                return '';
            }
            $data = str_replace('"', '', $data);
            $t = explode('.', $data);
            $data = str_replace("T", " ", $t[0]);
            $hora = '12:00:00';
            $t = explode(' ', $data);
            if (count($t) > 1) {
                $data = $t[0];
                $hora = $t[1];
            }
            $c = (string) substr((string) $data, 2, 1);
            if (!is_numeric($c)) {
                $data = substr((string) $data, 6, 4) . '-' . substr((string) $data, 3, 2) . '-' . substr((string) $data, 0, 2);
            }
            $data = $data . 'T' . $hora . '-00:00';
        }

        try {
            $date = new DateTime($data);
            if ($alterarTimeZone) {
                $date->setTimezone(new DateTimeZone($this->timezone));
            }
        } catch (Exception $e) {
            return '';
        }
        switch ($escolha) {
            case 'arrumar':
                if ($datahora) {
                    $out = $date->format('Y-m-d H:i:s');
                } else {
                    $out = $date->format('Y-m-d');
                }
                break;
            case 'mostrar':
                if ($datahora) {
                    $out = $date->format('d/m/Y H:i:s');
                } else {
                    $out = $date->format('d/m/Y');
                }
                break;
            case 'iso8601':
            case 'c':
                $out = $date->format('c');
                break;
            case 'extenso':
                $out = $date->format('d \d\e F \d\e Y');
                // $out = strftime('%d de %B de %Y', $date->getTimestamp());
                break;
            case 'timestamp':
                $out = $date->getTimestamp();
                break;
            case 'age':
                $dataAtual = new DateTime();
                $diferenca = $dataAtual->diff($date);
                $out = [$diferenca->y, $diferenca->m, $diferenca->d];
                break;
            default:
                $out = $date->format('Y-m-d');
                break;
        }

        return $out;
    }

    public function fone()
    {
        $fone = self::parseInt();
        $ddd = '(' . substr((string) $fone, 0, 2) . ') ';
        $fone = substr((string) $fone, 2, strlen((string) $fone) - 2);
        $out = $ddd . substr((string) $fone, 0, 4) . substr((string) $fone, 4, 8);
        if (strlen((string) $fone) === 9) { // nono digito
            $out = $ddd . substr((string) $fone, 0, 5) . substr((string) $fone, 5, 9);
        }
        return $out;
    }

    public function cep()
    {
        $cep = self::parseInt();
        return substr((string) $cep, 0, 5) . '-' . substr((string) $cep, 5, 8);
    }

    /**
     * Retorna o valor ABS da string formatada em decimal americano
     * @return string
     */
    public function decimal()
    {
        $var = $this->string;
        if (stripos($var, ',') > -1) {
            $var = self::parseInt();
            $var = substr((string) $var, 0, strlen((string) $var) - 2) . "." . substr((string) $var, strlen((string) $var) - 2, 2);
        }
        return $var;
    }

    public function parseInt()
    {
        return preg_replace("/[^0-9]/", "", $this->string);
    }

    public function dateToMktime()
    {
        $date = $this->string;
        if (!$date) {
            $date = time();
            return $date;
        }
        $date = $this->date('arrumar', true);
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        $timestamp = $dt->getTimestamp();
        return $timestamp;
    }

    public function subDays(int $days, $operacao = '-')
    {
        $d = $this->date();
        $d = date_parse($d);
        $dia = $operacao === '-' ? $d['day'] - $days : $d['day'] + $days;
        $d = mktime($d['hour'], $d['minute'], $d['second'], $d['month'], $dia, $d['year']);
        return date('Y-m-d H:i:s', $d);
    }

    public function subMonths(int $months, $operacao = '-')
    {
        $d = $this->date();
        $d = date_parse($d);
        $mes = $operacao === '-' ? ($d['month'] - $months) : ($d['month'] + $months);
        $d = mktime($d['hour'], $d['minute'], $d['second'], $mes, date('day'), $d['year']);
        return date('Y-m-d H:i:s', $d);
    }

    /**
     * Formata um numero para exibiição
     *
     * @param boolean $sinalNoFim
     * @param boolean $prefixo
     * @param boolean $color
     * @return string
     */
    public function formatNumber(bool $sinalNoFim = false, bool $prefixo = true, bool $color = true): string
    {
        $number = $this->string;

        if ($prefixo) {
            $prefixo = '<strong><small>' .
                (($prefixo !== true) ? $prefixo : 'R$')
                . '</small> </strong> ';
        }
        $out = $prefixo . number_format((float) $number, 2, ',', '.');
        if ($sinalNoFim) {
            $out = $prefixo . number_format(abs($number), 2, ',', '.') . (($number < 0) ? '-' : '+');
        }
        if ($color && $number < 0) {
            $out = '<span class="text-red">' . $out . '</span>';
        }

        return (string) $out;
    }

    public function cpfCnpj(): string
    {
        $var = self::parseInt();

        if (strlen((string) $var) === 11) { // cpf
            $out = substr((string) $var, 0, 3) . '.' . substr((string) $var, 3, 3) . '.' . substr((string) $var, 6, 3) . '-' . substr((string) $var, 9, 2);
        } else if (strlen((string) $var) === 14) { // cnpj
            $out = substr((string) $var, 0, 2) . '.' . substr((string) $var, 2, 3) . '.' . substr((string) $var, 5, 3) . '/' . substr((string) $var, 8, 4) . '-' . substr((string) $var, 12, 2);
        } else {
            $out = $var;
        }
        return (string) $out;
    }
}
