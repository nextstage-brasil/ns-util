<?php

namespace NsUtil;

use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use Closure;
use Exception;
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
     * Envio de mensagem utilizando SMTP
     *
     * @param array $to Padrão ['Nome do recipiente' => 'email@notem.com]
     * @param string $subject
     * @param string $text
     * @param array $config Retorno de getConfig
     * @param integer $debug Nivel de debug, 0 a 5
     * @param array $attach
     * @return mixed
     */
    public static function send(array $to, string $subject, string $text, array $config, int $debug = 0, array $attach = []) {
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
        } catch (\Exception $exc) {
            return $exc->getMessage();
        }
    }

    /**
     * Faz o envio de uma mensagem usando o Sendgrid como gateway
     *
     * @param string $apiKey
     * @param string $fromAddress
     * @param string $fromName
     * @param string $toAddress
     * @param string $toName
     * @param string|null $subject
     * @param string|null $template_id
     * @param array $template_data
     * @return mixed
     */
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



    /**
     * Envio de email utilizando SES AWS
     *
     * @param string $to
     * @param string $subject
     * @param string $html_body
     * @param array|null $anexo
     * @param array $template_data
     * @param string|null $template_id
     * @param Closure|null $success
     * @param Closure|null $error
     * @return boolean
     */
    public static function sendByAWS(string $to, string $subject, string $html_body, ?array $anexo = [],  array $template_data = [], ?string $template_id = null, ?Closure $success = null, ?Closure $error = null): bool {
        try {

            Validate::validate(['AWS_KEY', 'AWS_SECRET', 'SENDMAIL_EMAIL', 'to', 'subject', 'body'], [
                'AWS_KEY' => getenv('AWS_KEY'),
                'AWS_SECRET' => getenv('AWS_SECRET'),
                'SENDMAIL_EMAIL' => getenv('SENDMAIL_EMAIL'),
                'to' => $to,
                'subject' => $subject,
                'body' => strlen($html_body) > 0 ?  $html_body : ($template_data[0] ?? '')
            ], true);

            $SesClient = new SesClient([
                'version' => '2010-12-01',
                'region' => getenv('AWS_REGION') ? getenv('AWS_REGION') : 'sa-east-1',
                'credentials' => [
                    'key' => getenv('AWS_KEY'),
                    'secret' => getenv('AWS_SECRET')
                ]
            ]);
            $sender_email = getenv('SENDMAIL_EMAIL');
            $recipient_emails = [$to];
            $type = stripos($html_body, '[PLAINTEXT]') !== false ? 'Text' : 'Html';
            $html_body = str_replace('[PLAINTEXT]', '', $html_body);
            $char_set = 'UTF-8';

            $result = $SesClient->sendEmail([
                'Destination' => [
                    'ToAddresses' => $recipient_emails,
                ],
                'ReplyToAddresses' => [$sender_email],
                'Source' => $sender_email,
                'Message' => [
                    'Body' => [
                        $type => [
                            'Charset' => $char_set,
                            'Data' => $html_body,
                        ],
                    ],
                    'Subject' => [
                        'Charset' => $char_set,
                        'Data' => $subject,
                    ],
                ],
            ]);

            if (is_callable($success)) {
                call_user_func($success, $result['MessageId']);
            }

            return true;
        } catch (AwsException $e) {
            if (is_callable($error)) {
                call_user_func($error, 'AwsException: ' . $e->getMessage());
            }
            throw new Exception($e->getMessage());
        } catch (Exception $e) {
            if (is_callable($error)) {
                call_user_func($error, 'Exception: ' . $e->getMessage());
            }
            throw new Exception($e->getMessage());
        }
    }
}
