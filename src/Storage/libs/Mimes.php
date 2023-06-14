<?php

namespace NsUtil\Storage\libs;

class Mimes
{
    /*
     * To change this license header, choose License Headers in Project Properties.
     * To change this template file, choose Tools | Templates
     * and open the template in the editor.
     */

    private static $_MIMES = array(
        'application/pdf' => 'pdf',
        'application/rtf' => 'rtf',
        'audio/x-wav' => 'wav',
        'audio/wav' => 'wav',
        'audio/mpeg' => 'mp3',
        'audio/mp3' => 'mp3',
        'audio/vnd.dlna.adts' => 'aac',
        'image/gif' => 'gif',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'nuncadeveexistir/apenasparaconstar' => 'jpeg',
        'image/png' => 'png',
        'image/tiff' => 'tiff',
        'image/x-portable-bitmap' => 'bpm',
        'multipart/x-zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
        'nuncaexistir_2' => 'x-zip-compressed',
        'text/html' => 'html',
        'text/plain' => 'txt',
        'text/richtext' => 'rtx',
        'video/mpeg' => 'mpeg',
        'video/quicktime' => 'mov',
        'video/msvideo' => 'avi',
        'video/x-sgi-movie' => 'movie',
        'video/mp4' => 'mp4',
        'application/vnd.ms-excel' => 'csv',
        'application/octet-stream' => 'ofx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/msword' => 'doc',
    );

    /**
     * Recupera o Mime-Type de um arquivo
     * @param string $file Caminho para o arquivo
     * @param boolean $encoding Define se também será retornado a codificação do arquivo
     * @return string
     */
    public static function getMimeType($file, $encoding = true)
    {
        if (function_exists('finfo_open') && is_file($file) && is_readable($file)) {
            $finfo = new \finfo($encoding ? FILEINFO_MIME : FILEINFO_MIME_TYPE);
            $out = explode(';', $finfo->file($file))[0];
        } else {
            $plim = explode('.', $file);
            $extensao = array_pop($plim);
            $out = array_search($extensao, self::$_MIMES);
        }
        $out = str_replace('.', '-', $out);
        return self::$_MIMES[$out] ?? 'not-found';
    }
}
