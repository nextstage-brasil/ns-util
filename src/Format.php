<?php

namespace NsUtil;

use DateTime;
use DateTimeZone;
use Exception;

class Format {

    private $timezone;
    private $string;

    public function __construct($string = false, $timezone = 'America/Sao_Paulo') {
        $this->timezone = $timezone;
        $this->string = $string;
    }

    public function setString($string) {
        $this->string = $string;
        return $this;
    }

    /**
     * 
     * @param type $data
     * @param type $escolha
     * @param type $datahora
     * @param type $alterarTimeZone
     * @return boolean
     */
    public function date($escolha = 'arrumar', $datahora = false, $alterarTimeZone = false) {
        $data = $this->string;
        if ($data !== 'NOW') {
            if (strlen($data) < 6) {
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
            $c = (string) substr($data, 2, 1);
            if (!is_numeric($c)) {
                $data = substr($data, 6, 4) . '-' . substr($data, 3, 2) . '-' . substr($data, 0, 2);
            }
            $data = $data . 'T' . $hora . '-00:00';
        }

        try {
            $date = new DateTime($data);
            if ($alterarTimeZone) {
                $date->setTimezone(new DateTimeZone($this->timezone));
            } else {
                //$date->setTimezone(new DateTimeZone('+0300'));
                //$date->setTimezone(new DateTimeZone());
            }
        } catch (Exception $e) {
            //$backtrace = debug_backtrace();
            //$origem = $backtrace[0]['file'] . ' [' . $backtrace[1]['class'] . '::' . $backtrace[1]['function'] . ' (' . $backtrace[0]['line'] . ')]';
            //Log::logTxt('debug', 'ERROR DATE: ' . $e->getMessage() . '||' . $origem . __METHOD__ . __LINE__);
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
                $out = strftime('%d de %B de %Y', $date->getTimestamp());
                break;
            case 'timestamp':
                $out = $date->getTimestamp();
                break;
            default:
                $out = $date->format('Y-m-d');
                break;
        }

        return $out;
    }

    public function fone() {
        $fone = self::parseInt($this->string);
        $ddd = '(' . substr($fone, 0, 2) . ') ';
        $fone = substr($fone, 2, strlen($fone) - 2);
        $out = $ddd . substr($fone, 0, 4) . substr($fone, 4, 8);
        if (strlen($fone) === 9) { // nono digito
            $out = $ddd . substr($fone, 0, 5) . substr($fone, 5, 9);
        }
        return $out;
    }

    public function cep() {
        $cep = self::parseInt($this->string);
        return substr($cep, 0, 5) . '-' . substr($cep, 5, 8);
    }

    /**
     * Retorna o valor ABS da string formatada em decimal americano
     * @return string
     */
    public function decimal() {
        $var = self::parseInt($this->string);
        $var = substr($var, 0, strlen($var) - 2) . "." . substr($var, strlen($var) - 2, 2);
        return $var;
    }

    public function parseInt() {
        return preg_replace("/[^0-9]/", "", $this->string);
    }

    public function dateToMktime() {
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

    public function subDays(int $days, $operacao = '-') {
        $d = $this->date();
        $d = date_parse($d);
        $dia = $operacao === '-' ? $d['day'] - $days : $d['day'] + $days;
        $d = mktime($d['hour'], $d['minute'], $d['second'], $d['month'], $dia, $d['year']);
        return date('Y-m-d H:i:s', $d);
    }

    public function subMonths(int $months, $operacao = '-') {
        $d = $this->date();
        $d = date_parse($d);
        $mes = $operacao === '-' ? ($d['month'] - $months) : ($d['month'] + $months);
        $d = mktime($d['hour'], $d['minute'], $d['second'], $mes, date('day'), $d['year']);
        return date('Y-m-d H:i:s', $d);
    }

}
