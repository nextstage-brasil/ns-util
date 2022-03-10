<?php

namespace NsUtil;

use NsUtil\Integracao\SendgridService;
use PHPMailer\PHPMailer\PHPMailer;

//require __DIR__ . '/lib/phpmailer/class.phpmailer.php';

class Sendmail {

    public static function getConfig(string $host, string $email, string $username, string $password, int $port = 465, string $smtpSecure = 'ssl', bool $smtpAuth = true, bool $isTLSv1_2 = false): array {
        return [
            'host' => $host,
            'email' => $email,
            'username' => $username,
            'password' => $password,
            'port' => $port,
            'smtpSecure' => $smtpSecure,
            'SMTPAuth' => $smtpAuth,
            'isTLSv1_2' => $isTLSv1_2
        ];
    }

    /**
     * 
     * @param array $to ['Nome' => 'Email']
     * @param type $subject
     * @param type $text
     * @param array $config [host=>'host', SMTPAuth, username, email, password, port, smtpSecure]
     * @return boolean
     */

    /**
     * 
     * @param array $to array ['Nome descritivo' => 'email@notem.com]
     * @param type $subject Assunto
     * @param type $text Corpo do email
     * @param array Use a função getConfig
     * @param type $debug
     * @param type $attach
     * @return boolean
     */
    public static function send(array $to, string $subject, $text, array $config, int $debug = 0, array $attach = []) {
//        $mail = new \PHPMailer();
        $mail = new PHPMailer();
        $mail->IsSMTP(); // Define que a mensagem será SMTP

        $mail->Host = $config['host'];
        $mail->SMTPAuth = $config['SMTPAuth']; // Autenticação
        $mail->SMTPDebug = $debug;
        $mail->Port = $config['port'];
        $mail->SMTPSecure = $config['smtpSecure'];

        if ($config['email']) {
            $mail->Username = $config['email'];
            $mail->Password = $config['password'];
            $mail->SetFrom($mail->Username, (string) $config['username']);
        }

        if ($config['isTLSv1_2'] === true) {
            define('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT', true);
        }

        //Define os destinatário(s)
        foreach ($to as $key => $val) {
            $mail->AddAddress($val, $key);
        }

        //Define os dados técnicos da Mensagem
        $mail->IsHTML(true);
        $mail->CharSet = 'UTF-8';

        //Texto e Assunto
        $mail->Body = $text;
        $mail->AltBody = $text;
        $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        //Anexos (opcional)
        if (count($attach) > 0) {
            foreach ($attach as $file) {
                if (file_exists($file)) {
                    $mail->AddAttachment($file);
                }
            }
        }

        try {
            //Envio da Mensagem
            $enviado = $mail->Send();

            //Limpa os destinatários e os anexos
            $mail->ClearAllRecipients();
            $mail->ClearAttachments();

            //Exibe uma mensagem de resultado
            if ($enviado) {
                return true;
            } else {
                return $mail->ErrorInfo;
            }
        } catch (Exception $exc) {
            return $exc->getMessage();
        }
    }

    public static function sendBySendgrid(
            string $apiKey,
            string $fromAddress,
            string $fromName,
            string $toAddress,
            string $toName,
            string $subject = null,
            string $template_id = null,
            array $template_data = []
    ) {
        $sg = new SendgridService($apiKey);
        $headers = [
            'from' => ['address' => $fromAddress, 'name' => $fromName],
            'to' => ['address' => $toAddress, 'name' => $toName]
        ];
        if (!is_null($subject)) {
            $headers['subject'] = $subject;
        }
        $sg->setEmailHeaders($headers);
        if (!is_null($template_id)) {
            $sg->setTemplate($template_id, $template_data);
        }
        $ret = $sg->sendEmail();
        return (($ret->statusCode() < 300) ? true : $ret->headers());
    }

}
