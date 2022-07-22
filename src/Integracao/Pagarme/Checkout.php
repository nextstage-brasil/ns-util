<?php

namespace NsUtil\Integracao\Pagarme;

use NsUtil\Format;

class Checkout extends AbstractPagarme {

    private $transaction, $capturedTransaction;

    public function __construct($apikey=false) {
        //parent::$apikey = $apikey;
        parent::setPagarme();
    }

    public function transaction(int $idpedido, $amount, $payment_method, $card_hash, int $parcelas, array $Customer, array $Billing, array $Shipping, array $Itens) {
        $payment_method = mb_strtolower($payment_method);
        $f = new Format();
        $tr = [
            'postback_url' => \Config::getData('url').'/api/site/listener/pagarme',
            'metadata' => ['Pedido' => $idpedido],
            'async' => false,
            'amount' => $f->setString($amount)->parseInt(), // valor total em centavos
            'payment_method' => $payment_method,
            'customer' => $Customer,
            'billing' => $Billing,
            //'shipping' => $Shipping,
            'items' => $Itens,
            'capture' => false, // False, pois irei avaliar o custo informado, atualizar a tabela de pedidos e gerar o split de pagamentos
        ];
        switch ($payment_method) {
            case 'credit_card':
                $tr['card_hash'] = $card_hash;
                $tr['installments'] = $parcelas;
                break;
            case 'boleto':
                $tr['installments'] = 1;
                unset($tr['billing']);
                break;
            default:
                throw new \Exception('Payment method is not implememts');
        }
        // Criar transação
        $transaction = $this->pagarme->transactions()->create($tr);
        $this->setError($transaction);

        $this->transaction = $transaction;
    }

    public function capture(array $SplitRules) {
        $this->capturedTransaction = new \stdClass();
        if ($this->transaction->status === 'authorized') {
            $cap = [
                'id' => $this->transaction->id,
                'amount' => $this->transaction->amount,
                'split_rules' => $SplitRules
            ];

            $transaction = $this->pagarme->transactions()->capture($cap);
            $this->setError($transaction);
            $this->capturedTransaction = $transaction;
        } else {
            $this->capturedTransaction->error = 'Status não permite capturar transação: ' . $transaction->status;
        }
    }

    private function setError(&$transaction) {
        $transaction->error = false;
        switch ($transaction->status) {
            case 'authorized':
            case 'paid':
            case 'waiting_payment':
                break;
            case 'refused':
                // Recusado
                $transaction->error = $transaction->refuse_reason;
                break;
            default:
                $transaction->error = 'Status não identificado: ' . $transaction->status;
        }
    }

    function getTransaction() {
        return $this->transaction;
    }

    function getCapturedTransaction() {
        return $this->capturedTransaction;
    }

    public function loadTransaction($id) {
        
    }

}
