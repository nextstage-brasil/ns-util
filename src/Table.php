<?php

namespace NsUtil;

class Table {

    public $head;
    private $linha;
    private $css;
    private $headTamanho;
    private $onClick;
    private $foreach;
    private $explode;
    private $infiniteScroll;
    private $menuContexto;
    private $headArray;
    private $bindHTML = false;

    /**
     * 
     * @param type $campos
     * @param type $idTabela
     * @param type $zebra
     * @param type $css
     * @param type $head
     * @param type $infinitescroll
     */
    public function __construct($campos, $idTabela = false, $zebra = true, $css = '', $head = true, $infinitescroll = false) {
        if (is_array($campos)) {
            $this->linha = false;
            $this->headTamanho = count($campos);
            $t['elements'] = $campos;
            $t['id'] = (($idTabela) ? $idTabela : md5((string)microtime()));
            $t['css'] = 'table ' . (($zebra) ? ' table-striped ' : '') . ' ' . $css;
            $this->infiniteScroll = $infinitescroll;
            $this->setHead($t, $head);
            $this->explode = true;
        } else {
            die(__METHOD__ . __LINE__ . ': Head Not Array' . var_dump(debug_backtrace(-1)));
        }
    }

    public function setCss($text) {
        $this->css = (string) $text . ' ';
        return $this;
    }

    public function setOnClick($text) {
        $this->onClick = $text;
        $this->css = $this->css . ' mouseover-hand '; // setando automaticamente
        return $this;
    }

    public function setForeach($conjunto, $variavel) {
        /*
          $this->foreach = ' ng-repeat="' . $variavel . ' in ' . $conjunto . '" ';
          return $this;
         * 
         */
        $this->foreach = false;
        if ($conjunto && $variavel) {
            $this->foreach = ' ng-repeat="' . $variavel . ' in ' . $conjunto . ' | orderBy: $order:reverseSort" ';
            //$this->foreach = ' ng-repeat="' . $variavel . ' in ' . $conjunto . '"'; // . ' | orderBy: $order:reverseSort" ';
        }
        return $this;
    }

    public function setMenuContexto($menuContexto) {
        $this->menuContexto = $menuContexto;
        return $this;
    }

    private function setHead($var, $head) {
        $inf = (($this->infiniteScroll) ? ' infinite-scroll="' . $this->infiniteScroll . '" infinite-scroll-disabled="working"' : '');
        $this->head = '<div ng-init="reverseSort=false" class="table-responsive"' . $inf . '>';
        $this->head .= '
                <table id="' . $var['id'] . '" class="' . $var['css'] . '">';
        if ($head) {
            $this->head .= '<thead><tr class="">';
            foreach ($var['elements'] as $key => $val) {
                if (stripos($val, '|tr') !== false) { // para tratar o translate
                    $dd = explode("|", $val);
                    $val = $dd[0] . '|' . $dd[1];
                    $css = $dd[2];
                } else {
                    $dd = explode("|", $val);
                    $css = $dd[1];
                    $val = $dd[0];
                }




                $this->head .= '
                    <th  class="mouseover-hand ' . $css . '" ' . ((!is_int($key)) ? ' ng-click="$order = \'' . $key . '\'; reverseSort = !reverseSort" ' : '') . '>'
                        . $val
                        . ((!is_int($key)) ? '' // imprimir os icones caso tenha sido definido as chaves a ordernar a lista
                        . ' <span ng-show="$order == \'' . $key . '\'"><span ng-show="reverseSort"><i class="fa fa-chevron-up"></i></span><span ng-show="!reverseSort"><i class="fa fa-chevron-down"></i></span></span>'
                        . ' <span ng-show="!$order || $order != \'' . $key . '\'"><i class="fa fa-sort"></i></span>' : '')
                        . '</th>';
            }
            $this->head .= '</tr></thead><tbody>';
        }
    }

    public function setExplode($var) {
        $this->explode = (boolean) $var;
    }

    /**
     * 
     * @param array $line
     * @param type $idLinha
     * @param type $tip
     * @return boolean|$this
     */
    public function addLinha(array $line, $idLinha = false, $tip = false) {
        if (count($line) != $this->headTamanho) {
            /*
            Log::error('Erro ao criar tabela: '
                    . '<br/>Array Line tem ' . count($line) . '  objetos '
                    . '<br/>Header tem ' . $this->headTamanho . ' objetos'
                    . '<br/>Head: ' . json_encode($this->headArray)
                    . ' <br/>Campos: ' . json_encode($line)
            );
             */ 
            $bt = debug_backtrace();
            //Log::logTxt('error-table', $bt);
            die('ERROR REGISTRADO');
            return false;
        }
        $idLinha = $idLinha ? $idLinha : substr((string)md5((string)date('h-m-s')), 0, 6);
        $onClick = (($this->onClick) ? 'ng-click="' . $this->onClick . '"' : '');
        $this->linha .= '<tr ' . $this->foreach . ' id="' . $idLinha . '" class="%$this->css% table-line" ' . $onClick . ''
                . (($this->menuContexto) ? 'on-long-press="" context-itens="{{' . $this->menuContexto . '}}"' : '')
                . (($tip) ? Html::tooltip($tip) : '')
                . '>';
        foreach ($line as $val) {

            // @26/08/2020 Definição de isTag para nunca colocar tags em html-bind
            if (stripos($val, '[TAG]') > -1) {
                $val = str_replace('[TAG]', '', $val);
                $isTag = true;
            } else {
                $isTag = false;
            }

            if ($this->explode && stripos($val, '<img') === false && !$isTag) {
                $dd = explode("|", $val);
                if (stripos($val, '|tr') > 0 || stripos($val, 'filter:') !== false || stripos($val, 'currency') !== false || stripos($val, 'date:') !== false || stripos($val, 'cep') !== false) {
                    $css = str_replace("}}", "", $dd[2]);
                    $angular = str_replace('}}', '', $dd[1]);
                    $fecha = '}}';
                    $val = "$dd[0] | $angular $fecha";
                } else {
                    $css = $dd[1];
                    $val = $dd[0];
                }
                //$this->linha .= '<td class="' . $css . '">' . $val . '</td>';
            } else {
                //$this->linha .= '<td>' . $val . '</td>';
                //$this->linha .= '<td ng-bind-html="'.$val.'"></td>';
            }

            //if (!$this->bindHTML || stripos($val, '<img') >= 0) {
            // @14/08/2020 para nao inserir imagem como ng-bind-html pq da erro
            if (!$this->bindHTML || stripos($val, '<img') !== false || $isTag) {
                $this->linha .= '<td class="' . $css . '">' . $val . '</td>';
            } else {
                $this->linha .= '<td class="' . $css . '" ng-bind-html="' . str_replace(['{{', '}}'], '', $val) . '"></td>';
            }
        }

        $this->linha .= '</tr>';

        return $this;
    }

    public function printTable() {
        $html = $this->head . $this->linha . '</tbody></table></div>';
        return str_replace('$this->css', $this->css, $html);
    }

    public function render() {
        return $this->printTable();
    }

    public function actions($id, $dir, $edita = true, $exclui = true, $autoriza = false, $rejeita = false, $extras = false) {
        $out = '<div align="center" id="action_' . $id . '">' .
                (($autoriza) ? '<a href=""><span class="glyphicon glyphicon-check" aria-hidden="true"></span></a>&nbsp;' : '') .
                (($rejeita) ? ' <a href="javascript: void(0)><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></a>&nbsp;' : '') .
                (($edita) ? ' <a href="' . strtolower($dir) . '-edit.php?id=' . $id . '"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></a>' : '') .
                (($exclui) ? ' <a href="javascript:void(0)" onclick="javascript:delete(\'' . $dir . '\', ' . $id . ')" href="' . strtolower($dir) . '-edit.php?action=excluir&id=' . $id . '"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a>' : '') .
                (($extras) ? $extras : '');
        '</div>';
        return $out;
    }

    public function setNgBindHtml($bool) {
        $this->bindHTML = $bool;
        return $this;
    }

    public function menuContextAddOnTd() {
        return '
            <div class="text-center text-dark mouseover-hand" style="padding:0px;">
                <div ng-show="' . $this->menuContexto . '.length"><a menu-contexto="" menu-contexto-itens="{{' . $this->menuContexto . '}}">' . Html::iconFafa('ellipsis-v', 2) . '</a></div>            
            </div>';
    }

}
