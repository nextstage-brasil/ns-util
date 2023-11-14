<?php

namespace NsUtil;

use WideImage\WideImage;


class ResizeImage
{

    private $file;
    private $resolucao;

    public function __construct()
    {

    }

    function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    function setResolucao($resolucao)
    {
        $this->resolucao = (int) $resolucao;
        return $this;
    }

    /**
     * Executa a função
     *
     * @return void
     */
    public function run()
    {
        $wide = WideImage::load($this->file);
        list($largura_original, $altura_original) = getimagesize($this->file);
        if ($largura_original > $altura_original) {
            $wide->resize((int) $this->resolucao, null, 'outside', 'down')->saveToFile($this->file);
        } else {
            $wide->resize(null, (int) $this->resolucao, 'outside', 'down')->saveToFile($this->file);
        }
    }

    public function reduz()
    {
        list($largura_original, $altura_original) = getimagesize($this->file);
        $ladoMaior = (($largura_original > $altura_original) ? $largura_original : $altura_original);
        if ($ladoMaior > $this->resolucao) {
            $wide = WideImage::load($this->file);
            if ($largura_original > $altura_original) {
                $wide->resize((int) $this->resolucao, null, 'outside', 'down')->saveToFile($this->file);
            } else {
                $wide->resize(null, (int) $this->resolucao, 'outside', 'down')->saveToFile($this->file);
            }
        }
    }

}
