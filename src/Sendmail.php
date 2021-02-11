<?php

namespace NsUtil;

require __DIR__ . '/lib/phpmailer/class.phpmailer.php';

class Sendmail {
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
     * @param array $config [
      'host' => 'mail.host.com.br',
      'email' => 'nome@email.com',
      'username' => 'Nome od usuario que envia',
      'password' => 'pass',
      'port' => 465,
      'smtpSecure' => 'ssl', // ssl ou tpl
      'SMTPAuth' => true
      ]
     * @param type $debug
     * @param type $attach
     * @return boolean
     */
    public static function send(array $to, $subject, $text, array $config, $debug = false, array $attach = []) {
        $mail = new \PHPMailer();
        $mail->IsSMTP(); // Define que a mensagem será SMTP
        $mail->Host = $config['host'];
        $mail->SMTPAuth = $config['SMTPAuth']; // Autenticação
        $mail->SMTPDebug = $debug;
        $mail->Username = $config['email'];
        $mail->Password = $config['password'];
        $mail->Port = $config['port']; //465;
        $mail->SMTPSecure = $config['smtpSecure']; //'ssl';
        $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $mail->SetFrom($mail->Username, (string) $config['username']);

        //Define os destinatário(s)
        foreach ($to as $key => $val) {
            $mail->AddAddress($val, $key);
        }

        //Define os dados técnicos da Mensagem
        $mail->IsHTML(true); // Define que o e-mail será enviado como HTML
        $mail->CharSet = 'UTF-8'; // Charset da mensagem (opcional)
        //Texto e Assunto

        $mail->Body = $text;
        $mail->AltBody = $text;


        //Anexos (opcional)
        if (count($attach) > 0) {
            foreach ($attach as $file) {
                if (file_exists($file)) {
                    $mail->AddAttachment($file);
                } 
            }
        }

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
    }

}
