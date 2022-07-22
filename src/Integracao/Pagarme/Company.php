<?php

namespace NsUtil\Integracao\Pagarme;

use NsUtil\Format;

class Company extends AbstractPagarme {

    public function __construct() {
        parent::setPagarme();
    }

    public function read() {
        $url = 'https://api.pagar.me/1/company?api_key=' . self::$apikey;
        return json_decode(\NsUtil\Helper::curlCall($url)->content);
    }

    public function getCost() {
        $var = $this->read();
        
        // Fixeds
        $itens = $var->pricing->gateway->live;
        $antifraude = $itens->antifraud_cost[0]->cost;
        $creditCardFixed = $antifraude + $itens->transaction_cost->credit_card;
        $debitCardFixed = $antifraude + $itens->transaction_cost->debit_card;
        $boletoFixed = $antifraude + $itens->boletos->payment_fixed_fee;
        $pixFixed = $antifraude;
        
        // Taxs
        $mdrs = [];
        foreach($var->pricing->psp->live->mdrs as $item) {
            $mdrs[$item->payment_method] = $item->installments[0]->mdr;
        }    
        
        return (new Entities())->getCostCompany($creditCardFixed, $debitCardFixed, $boletoFixed, $pixFixed, $mdrs);
        
    }

}
