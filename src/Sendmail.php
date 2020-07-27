<?php

namespace NsUtil;

use PHPMailer\PHPMailer\PHPMailer;

class Sendmail {
    
    /**
     * 
     * @param array $to ['Nome' => 'Email']
     * @param type $subject
     * @param type $text
     * @param array $config [host=>'host', SMTPAuth, username, email, password, port, smtpSecure]
     * @return boolean
     */
    public static function send(array $to, $subject, $text, array $config) {
        $mail = new PHPMailer();
        $mail->IsSMTP(); // Define que a mensagem será SMTP
        $mail->Host = $config['host'];
        $mail->SMTPAuth = $config['SMTPAuth']; // Autenticação
        $mail->SMTPDebug = 1;
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
        //$mail->AddAttachment("e:\home\login\web\documento.pdf", "novo_nome.pdf");
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
