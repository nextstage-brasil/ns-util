<?php

namespace NsUtil;

class Validate {

    private $obrigatorios = [];

    public function __construct() {
        
    }

    public static function validaCpfCnpj($val) {
        $val = (string) (new Format($val))->parseInt();
        if (strlen($val) === 11) {
            return self::validaCPF($val);
        }
        if (strlen($val) === 14) {
            return self::validaCnpj($val);
        }
        return 'Preencha corretamente CPF/CNPJ';
    }

    private static function validaCPF($cpf = null) {
        // Verifica se um número foi informado
        if (empty($cpf) || $cpf === '') {
            return 'CPF Inválido: Vazio';
        }
        // Elimina possivel mascara
        $cpf = (new Format($cpf))->parseInt();
        // Verifica se o numero de digitos informados é igual a 11 
        if (strlen($cpf) != 11) {
            return 'CPF Inválido: Menor que 11 digitos';
        }
        // Verifica se nenhuma das sequências invalidas abaixo 
        // foi digitada. Caso afirmativo, retorna falso
        else if ($cpf == '00000000000' ||
                $cpf == '11111111111' ||
                $cpf == '22222222222' ||
                $cpf == '33333333333' ||
                $cpf == '44444444444' ||
                $cpf == '55555555555' ||
                $cpf == '66666666666' ||
                $cpf == '77777777777' ||
                $cpf == '88888888888' ||
                $cpf == '99999999999') {
            return 'CPF Inválido: Número Sequencial';
            // Calcula os digitos verificadores para verificar se o
            // CPF é válido
        } else {

            for ($t = 9; $t < 11; $t++) {

                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf[$c] != $d) {
                    return 'CPF Inválido: Digito verificador não é válido';
                }
            }
            return true;
        }
    }

    private static function validaCnpj($cnpj = null) {
        $cnpj = (new Format($cnpj))->parseInt();
        if (empty($cnpj) || $cnpj === '') {
            return 'CNPJ Inválido: Vazio';
        }
        if (strlen($cnpj) != 14) {
            return 'CNPJ Inválido: Menor que 14 digitos';
        }
        if ($cnpj === '00000000000000') {
            return 'CNPJ Inválido: Número sequencial';
        }
        $cnpj = (string) $cnpj;
        $cnpj_original = $cnpj;
        $primeiros_numeros_cnpj = substr($cnpj, 0, 12);
        if (!function_exists('multiplica_cnpj')) {

            function multiplica_cnpj($cnpj, $posicao = 5) {
                // Variável para o cálculo
                $calculo = 0;
                // Laço para percorrer os item do cnpj
                for ($i = 0; $i < strlen($cnpj); $i++) {
                    // Cálculo mais posição do CNPJ * a posição
                    $calculo = $calculo + ( $cnpj[$i] * $posicao );
                    // Decrementa a posição a cada volta do laço
                    $posicao--;
                    // Se a posição for menor que 2, ela se torna 9
                    if ($posicao < 2) {
                        $posicao = 9;
                    }
                }
                // Retorna o cálculo
                return $calculo;
            }

        }

        // Faz o primeiro cálculo
        $primeiro_calculo = multiplica_cnpj($primeiros_numeros_cnpj);

        // Se o resto da divisão entre o primeiro cálculo e 11 for menor que 2, o primeiro
        // Dígito é zero (0), caso contrário é 11 - o resto da divisão entre o cálculo e 11
        $primeiro_digito = ( $primeiro_calculo % 11 ) < 2 ? 0 : 11 - ( $primeiro_calculo % 11 );

        // Concatena o primeiro dígito nos 12 primeiros números do CNPJ
        // Agora temos 13 números aqui
        $primeiros_numeros_cnpj .= $primeiro_digito;

        // O segundo cálculo é a mesma coisa do primeiro, porém, começa na posição 6
        $segundo_calculo = multiplica_cnpj($primeiros_numeros_cnpj, 6);
        $segundo_digito = ( $segundo_calculo % 11 ) < 2 ? 0 : 11 - ( $segundo_calculo % 11 );

        // Concatena o segundo dígito ao CNPJ
        $cnpj = $primeiros_numeros_cnpj . $segundo_digito;

        // Verifica se o CNPJ gerado é idêntico ao enviado
        if ($cnpj === $cnpj_original) {
            return true;
        } else {
            return 'CNPJ Inválido: Cálculo do dígito verificador inválido';
        }
    }

    // Define uma função que poderá ser usada para validar e-mails usando regexp
    public static function validaEmail($email) {
        return
                $er = "/^(([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}){0,1}$/";
        if (preg_match($er, $email)) {
            return true;
        } else {
            return false;
        }
    }

    public function addCampoObrigatorio($key, $msg, $type = 'string') {
        $this->obrigatorios['list'][] = ['key' => $key, 'msg' => $msg, 'type' => $type];
    }

    /**
     * Valida os dados informados em $data. Caso não seja satisfeito, retorna o codigo definido
     * @param array $data
     * @param \NsUtil\Api $api
     * @param type $errorCode
     * @return type
     */
    public function runValidateData(array $data, Api $api, $errorCode = 200) {
        $campos = [];
        foreach ($this->obrigatorios['list'] as $item) {
            $campos[] = ['key' => $item['key'], 'value' => $data[$item['key']], 'msg' => $item['msg'], 'type' => $item['type']];
        }
        $error = \NsUtil\Helper::validarCamposObrigatorios($campos);
        if (count($error) > 0) {
            $api->error($error);
        }
    }

}
