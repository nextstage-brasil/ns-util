<?php

namespace NsUtil\Integracao\Pagarme;

class Config {

    public static $dicionario = [
        'legal_name' => 'Nome do titular', 
        'document_number' => 'CPF', 
        'conta' => 'Conta', 
        'agencia' => 'Agência', 
        'bank_code' => 'Código do banco', 
        'value is required' => '',
        'bank_account_id' => 'Número do banco',
        'invalid format' => 'Inválido ou não informado',
        'conta_dv' => 'Dígito da conta',
        'value too short' => 'Valor não informado ou incorreto',
        'shipping' => 'Envio',
        '"fee" must be a number' => 'Deve ser um número',
        'customer' => 'Cliente',
        'child "email" fails because ["email" must be a valid email]' => 'Email inválido',
        'child "documents" fails because ["documents" at position 0 fails because [child "number" fails because ["number" is not allowed to be empty]]]' => 'CPF inválido',
        'conta_dv' => 'Digito da conta não identificado',
        'waiting_funds' => 'Aguardando liberação',
        'paid' => 'Pagamento liberado',
        'credit_card' => 'Cartão de crédito',
        'credit' => 'Crédito',
        'boleto' => 'Boleto',
        'number too small' => 'deve ser maior que 1,00',
        'amount' => 'Valor',
        '"state" is not allowed to be empty' => 'Preencha corretamente o endereço da fatura do cartão',
        'billing' => ''
    ];

    /**
     * Comparação do status de pedido do sistema com os nomes de status de transacao do pagarme
     * @return type
     */
    public static function getStatusDePara() {
        return [
            'processing' => 1,
            'waiting_payment' => 1,
            'authorized' => 50,
            'paid' => 50,
            'refused' => 11,
            'pending_refund' => 14,
            'refunded' => 14
        ];
    }

    public static function getDicionario($key) {
        $mk = mb_strtolower($key);
        if (!isset(self::$dicionario[$mk])) {
            \AppLibraryController::notificaDev('', 'Configuração para Pagarme não definida (PagarmeConfig47)', ['key' => $key]);
        }
        return ((isset(self::$dicionario[$mk])) ? self::$dicionario[$mk] : $key);
    }

}
