<?php

namespace NsUtil;

class PgLogParser {

    public function __construct() {
        
    }

    private function item($data, $tipo, $tipo2, $duracao, $descricao) {
        return [
            'datahora' => $data,
            'database' => '',
            'tipo' => $tipo,
            //'log' => $tipo2,
            'duracao' => $duracao,
            'descricao' => $descricao,
            'statement' => '',
            'details' => '',
            'context' => '',
        ];
    }

    public function parseToArray($filename) {
        //$filename = __DIR__ . '/win-log.log'; // str_replace('\\', DIRECTORY_SEPARATOR, 'D:\.pg-data\log\postgresql-2020-10-19_093005.log');
        //2020-10-19 14:05:35.611 -03 [19895] collaborative@cs_1.11.5 ERROR:  unrecognized configuration parameter "application.name"

        $list = file($filename);
        $out = [];
        $item = [];
        $i = -1;

        $format = new Format();

        foreach ($list as $key => $item) {
            $item = str_replace(["\t", "\n"], [' ', ' '], trim((string)$item));
            if (strlen((string)$item) < 2) {
                continue;
            }
            //2020-10-19 09:55:59.487 -03 [4880] ERROR: syntax error at or near ")" at character 1224
            //2020-10-18 19:30:22.645 -03 [7677] collaborative@cs_1.11.5 LOG:  duration: 20667.541 ms  execute pdo_stmt_00000006: INSERT INTO agencia_2.historico (id_usuario, entidade_historico, valorid_historico, texto_historico, data_historico, classificacao_historico, tipo_historico, extras_historico) values (1030, 'RSS', 10, '“Maguito dará continuidade ao trabalho exemplar realizado nesses quatro anos por Iris Rezende”, diz Gustavo Mendanha', '2020-10-18', 2, 4, '{"datetime":"2020-10-18 19:27:35","link":{"0":"https:\/\/www.jornalopcao.com.br\/ultimas-noticias\/maguito-dara-continuidade-ao-trabalho-exemplar-realizado-nesses-quatro-anos-por-iris-rezende-diz-gustavo-mendanha-290252\/"},"description":"<p>Prefeito de Aparecida discursou durante mega carreata do candidato \u00e0 Prefeitura de Goi\u00e2nia Maguito Vilela (MDB), que percorreu ruas dos bairros da regi\u00e3o leste da capital<\/p>\n<p>O post <a rel=nofollow href=https:\/\/www.jornalopcao.com.br\/ultimas-noticias\/maguito-dara-continuidade-ao-trabalho-exemplar-realizado-nesses-quatro-anos-por-iris-rezende-diz-gustavo-mendanha-290252\/>\u201cMaguito dar\u00e1 continuidade ao trabalho exemplar realizado nesses quatro anos por Iris Rezende\u201d, diz Gustavo Mendanha<\/a> apareceu primeiro em <a rel=nofollow href=https:\/\/www.jornalopcao.com.br>Jornal Op\u00e7\u00e3o<\/a>.<\/p>","timestamp":1603060055}'),(1030, 'RSS', 10, 'Escolha de nova Juma Marruá dá uma esfriada', '2020-10-18', 2, 4, '{"datetime":"2020-10-18 19:25:00","link":{"0":"https:\/\/www.gazetadigital.com.br\/variedades\/variedades\/escolha-de-nova-juma-marru-d-uma-esfriada\/632697"},"description":"","timestamp":1603059900}'),(1030, 'RSS', 10, 'Ex-noiva de Gabriel Diniz relembra cantor no dia que ele faria 30 anos', '2020-10-18', 2, 4, '{"datetime":"2020-10-18 19:22:00","link":{"0":"https:\/\/www.gazetadigital.com.br\/variedades\/variedades\/ex-noiva-de-gabriel-diniz-relembra-cantor-no-dia-que-ele-faria-30-anos\/632696"},"description":"","timestamp":1603059720}'),(1030, 'RSS', 10, 'Jorge Aragão é internado na UTI com pneumonia causada por covid-19', '2020-10-18', 2, 4, '{"datetime":"2020-10-18 19:19:00","link":{"0":"https:\/\/www.gazetadigital.com.br\/variedades\/variedades\/jorge-arago-internado-na-uti-com-pneumonia-causada-por-covid-19\/632695"},"description":"","timestamp":1603059540}'),(1030, 'RSS', 10, 'Mayra Cardi faz festa para celebrar 2 anos da filha Sophia', '2020-10-18', 2, 4, '{"datetime":"2020-10-18 19:16:00","link":{"0":"https:\/\/www.gazetadigital.com.br\/variedades\/variedades\/mayra-cardi-faz-festa-para-celebrar-2-anos-da-filha-sophia\/632694"},"description":"","timestamp":1603059360}'),(1030, 'RSS', 10, 'Brasil tem 5.235.344 casos e 153.905 mortes por covid-19', '2020-10-18', 2, 4, '{"datetime":"2020-10-18 19:08:00","link":{"0":"http:\/\/noticias.r7.com\/saude\/brasil-tem-5235344-casos-e-153905-mortes-por-covid-19-18102020"},"description":"<div class=media_box full-dimensions460x305>\n  \n  <div class=edges>\n        <img class=croppable src=https:\/\/img.r7.com\/images\/reuters-covid-19-teste-alemanha-1500-16102020112408939?dimensions=460x305 title=Brasil tem 5.224.362 casos e 153.675 mortes por covid-19\n alt=Brasil tem 5.224.362 casos e 153.675 mortes por covid-19\n \/>\n          <div class=gallery_link>\n          <\/div>\n        \n  <\/div>\n  <div class=content_image>\n    <span class=legend_box  >Brasil tem 5.224.362 casos e 153.675 mortes por covid-19\n<\/span>\n    <span class=credit_box >Matthias Rietschel\/Reuters - 16.10.2020<\/span>\n  <\/div>\n<\/div>\n\n \n<p>\nO Brasil registrou, neste domingo (18), 5.235.344 casos e\u00a0153.905 mortes confirmadas por <a href=https:\/\/noticias.r7.com\/saude\/coronavirus target=_blank><strong>covid-19,<\/strong><\/a> de acordo com os dados divulgados pelo Minist\u00e9rio da Sa\u00fade.<\/p>\n<p>\nNas \u00faltimas 24 horas, foram fichados 10.982 novos casos e 230 novas mortes.<\/p>\n<p>\nNo s\u00e1bado (17), o <a href=https:\/\/noticias.r7.com\/saude\/brasil-tem-5224362-casos-e-153675-mortes-por-covid-19-17102020 target=_blank><strong>pa\u00eds havia registrado\u00a05.224.362 casos e 153.675 mortes<\/strong><\/a>\u00a0da doen\u00e7a respirat\u00f3ria causada pelo novo coronav\u00edrus.<\/p>\n<p>\n<strong>Leia mais:\u00a0<a href=https:\/\/noticias.r7.com\/internacional\/mundo-chega-a-39-milhoes-de-casos-de-covid-e-aumento-nos-contagios-17102020 target=_blank>Mundo chega a 39 milh\u00f5es de casos de covid e aumento nos cont\u00e1gios<\/a><\/strong><\/p>\n<p>\nAinda segundo o balan\u00e7o do minist\u00e9rio,\u00a04.650.030 pessoas se curaram da doen\u00e7a e outras\u00a0431.409 est\u00e3o em acompanhamento.<\/p>\n<p>\n<strong>Vacina<\/strong><\/p>\n<p>\nO governo de S\u00e3o Paulo anuncia nesta segunda-feira (19) que a vacina Coronavac, desenvolvida pelo Instituto Butant\u00e3 em parceria com a farmac\u00eautica chinesa Sinovac, se <a href=https:\/\/noticias.r7.com\/sao-paulo\/vacina-do-butanta-e-segura-mas-aval-de-eficacia-fica-para-fim-do-ano-18102020 target=_blank><strong>mostrou segura tamb\u00e9m em testes com 9 mil volunt\u00e1rios brasileiros,<\/strong><\/a> reafirmando os resultados de pesquisa anterior com 50 mil participantes chineses. Os dados de efic\u00e1cia, por\u00e9m, devem ser divulgados somente entre novembro e dezembro.<\/p>","timestamp":1603058880}'),(1030, 'RSS', 10, 'PM prende homem com 46 comprimidos de ecstasy perto de boate em Cuiabá', '2020-10-18', 2, 4, '{"datetime":"2020-10-18 19:06:00","link":{"0":"https:\/\/www.gazetadigital.com.br\/editorias\/policia\/pm-prende-homem-com-46-comprimidos-de-ecstasy-perto-de-boate-em-cuiab\/632693"},"description":"","timestamp":1603058760}'),(1030, 'RSS', 10, 'Quais os requisitos para cadastro da carteira do Idoso? confira', '2020-10-18', 2, 4, '{"datetime":"2020-10-18 19:03:55","link":{"0":"https:\/\/www.mixvale.com.br\/2020\/10\/18\/quais-os-requisitos-para-cadastro-da-carteira-do-idoso-confira\/"},"description":"<p>Esta not\u00edcia <a rel=nofollow href=https:\/\/www.mixvale.com.br\/2020\/10\/18\/quais-os-requisitos-para-cadastro-da-carteira-do-idoso-confira\/>Quais os requisitos para cadastro da carteira do Idoso? confira<\/a> apareceu primeiro no <a rel=nofollow href=https:\/\/www.mixvale.com.br>Portal Mix Vale<\/a>. www.mixvale.com.br<\/p>\n<p>Quais os requisitos para cadastro da carteira do Idoso? confira. Quais s\u00e3o Idosos com mais de 60 anos, que recebem at\u00e9 dois sal\u00e1rios m\u00ednimos e n\u00e3o possuem meios de comprova\u00e7\u00e3o de renda, t\u00eam o direito de\u00a0solicitar a Carteira do Idoso para\u00a0viajar de forma gratuita ou com 50% de desconto no valor das passagens interestaduais de [\u2026]<\/p>\n<p>Esta not\u00edcia <a rel=nofollow href=https:\/\/www.mixvale.com.br\/2020\/10\/18\/quais-os-requisitos-para-cadastro-da-carteira-do-idoso-confira\/>Quais os requisitos para cadastro da carteira do Idoso? confira<\/a> apareceu primeiro no <a rel=nofollow href=https:\/\/www.mixvale.com.br>Portal Mix Vale<\/a>. www.mixvale.com.br<\/p>","timestamp":1603058635}'),(1030, 'RSS', 10, 'Saque emergencial do FGTS está sendo realizado por golpistas', '2020-10-18', 2, 4, '{"datetime":"2020-10-18 19:02:13","link":{"0":"https:\/\/www.mixvale.com.br\/2020\/10\/18\/saque-emergencial-do-fgts-esta-sendo-realizado-por-golpistas\/"},"description":"<p>Esta not\u00edcia <a rel=nofollow href=https:\/\/www.mixvale.com.br\/2020\/10\/18\/saque-emergencial-do-fgts-esta-sendo-realizado-por-golpistas\/>Saque emergencial do FGTS est\u00e1 sendo realizado por golpistas<\/a> apareceu primeiro no <a rel=nofollow href=https:\/\/www.mixvale.com.br>Portal Mix Vale<\/a>. www.mixvale.com.br<\/p>\n<p>Saque emergencial do FGTS est\u00e1 sendo realizado por golpistas. Trabalhadores pelo pa\u00eds que tentaram fazer o saque emergencial do\u00a0FGTS\u00a0de at\u00e9 R$ 1.045 descobriram que o dinheiro j\u00e1 havia sido sacado. O golpe se d\u00e1 da seguinte forma: usando o CPF e o nome dos trabalhadores, golpistas se cadastram no aplicativo Caixa Tem, informando um e-mail [\u2026]<\/p>\n<p>Esta not\u00edcia <a rel=nofollow href=https:\/\/www.mixvale.com.br\/2020\/10\/18\/saque-emergencial-do-fgts-esta-sendo-realizado-por-golpistas\/>Saque emergencial do FGTS est\u00e1 sendo realizado por golpistas<\/a> apareceu primeiro no <a rel=nofollow href=https:\/\/www.mixvale.com.br>Portal Mix Vale<\/a>. www.mixvale.com.br<\/p>","timestamp":1603058533}'),(1030, 'RSS', 10, 'Prefeitura aplica multas em bares do Leblon, no Rio, por aglomeração', '2020-10-18', 2, 4, '{"datetime":"2020-10-18 19:02:00","link":{"0":"https:\/\/www.tnh1.com.br\/noticia\/nid\/prefeitura-aplica-multas-em-bares-do-leblon-no-rio-por-aglomeracao\/"},"description":"","timestamp":1603058520}'),(1030, 'RSS', 10, 'Sol e temperatura de até 30 graus levam bom público aos parques de Porto Alegre', '2020-10-18', 2, 4, '{"datetime":"2020-10-18 18:54:59","link":{"0":"https:\/\/www.correiodopovo.com.br\/not%C3%ADcias\/geral\/sol-e-temperatura-de-at%C3%A9-30-graus-levam-bom-p%C3%BAblico-aos-parques-de-porto-alegre-1.501762"},"description":"Fim de semana foi o primeiro ap\u00f3s a libera\u00e7\u00e3o da prefeitura para pr\u00e1tica de esportes coletivos","timestamp":1603058099}') on conflict do nothing
            //STATEMENT
            $cols = explode("]", $item);
            $datahora = explode(' ', $cols[0]);
            $data = false;
            try {
                $str = $datahora[0] . ' ' . $datahora[1];
                if (strlen((string)$str) > 8) {
                    $data = $format->setString($str)->date('arrumar', true);
                }
            } catch (Exception $exc) {
                $data = false;
            }
            $tipo2 = '';
            $duracao = '';
            if ($data) {
                // Tipo
                unset($cols[0]);
                $linha = implode(']', $cols);
                $a2 = explode(":", $linha);
                $a23 = explode(' ', $a2[0]);
                $tipo = array_pop($a23); // $a2[0];
                $database = array_pop($a23);
                // Descrição
                unset($a2[0]);
                $descricao = implode(':', $a2);
                // Tipo 2
                if (stripos($item, 'duration: ') > 0) {
                    $dur = explode(':', $descricao);
                    $tipo = $tipo2 = 'LENTIDAO';
                    $duracao = explode(' ', trim((string)$dur[1]))[0] . 'ms';
                    $descricao = trim((string)$dur[2]);
                }
                // Novo item
                if ($data !== $lastDate) {
                    $lastDate = $data;
                    $i++;
                    $out[$i] = $this->item($data, $tipo, $tipo2, $duracao, $descricao);
                    $out[$i]['database'] = $database;
                    $chave = 'descricao';
                } else {
                    switch (true) {
                        case strpos($item, 'STATEMENT') > -1:
                            $chave = 'statement';
                            break;
                        case strpos($item, 'CONTEXT') > -1:
                            $chave = 'context';
                            break;
                        case strpos($item, 'DETAIL') > -1:
                            $chave = 'detail';
                            break;
                        default:
                            $chave = 'descricao';
                    }
                    $out[$i][$chave] .= chr(13) . trim((string)$descricao);
                }
            } else {
                $out[$i][$chave] .= chr(13) . trim((string)$item);
            }

            //echo $item . "<br/>";
        }

        return $out;
    }

    public function loadToTable($filename, $schema, $tablename, ConnectionPostgreSQL $con, $tempDir = "/tmp") {
        $out = $this->parseToArray($filename);
        $csv = [];
        $header = [];
        Helper::saveFile($tempDir . DIRECTORY_SEPARATOR . 'nsutilcsv/index.tmp');
        $filecsv = $tempDir . DIRECTORY_SEPARATOR . $tablename . ".csv";
        $fp = fopen($filecsv, 'w+');
        foreach ($out as $key => $val) {
            // head of csv
            if ($key === 0) {
                foreach ($val as $k => $v) {
                    $header[] = $k;
                }
                fputcsv($fp, $header);
            } else {
                fputcsv($fp, $val);
            }
        }
        fclose($fp);

        $pgloader = new PgLoadCSV($con, $schema, false, false);
        $pgloader->run($filecsv);
        Helper::deleteFile($tempDir . DIRECTORY_SEPARATOR . 'nsutilcsv/index.tmp', true, false);
        Helper::deleteFile($filecsv, true, false);
    }

}
