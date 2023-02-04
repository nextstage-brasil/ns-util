<?php

namespace NsUtil;

use DOMDocument;

class Phrases {
    public function phrase($autor = 'jesus_cristo', $page = 1): string {
        $page = $page <= 0 ? 1 : $page;
        $url = "https://www.pensador.com/$autor/$page";
        $html = Helper::myFileGetContents("https://www.pensador.com/$autor/$page");
        $phrases = [];

        $DOM = new DOMDocument();
        $DOM->loadHTML($html);

        // autor
        $autorName = $autor;
        foreach ($DOM->getElementsByTagName('h1') as $item) {
            if ($item->getAttribute('class') === 'title') {
                $autorName = $item->textContent;
            }
        }

        // frases
        foreach ($DOM->getElementsByTagName('div') as $item) {
            if ($item->getAttribute('class') === 'phrases-list') {
                foreach ($item->getElementsByTagName('div') as $pp) {
                    $p = $pp->getElementsByTagName('p');
                    if ($p[0]->textContent) {
                        $phrases[] = $p[0]->textContent;
                    }
                }
            }
        }
        return $phrases[rand(0, count($phrases) + 1)]
            . " - $autorName. Fonte: $url";
    }
}
