<?php

namespace NsUtil\Integracao;

use Exception;
use SendGrid;
use SendGrid\Mail\Attachment;
use SendGrid\Mail\Mail;

class SendgridService {

    private $service;
    private $email;

    public function __construct($apikey) {

        $this->service = new SendGrid($apikey);
        $this->email = new Mail();
    }

    /**
     * @param Array $headers ['to' => ['address'=>'example@mail.com', 'name' => 'user 1 ' ],'from'=> ['address'=>'example@mail.com', 'name' => 'user 1 ' ],'cc'=> ['address'=>'example@mail.com', 'name' => 'user 1 ' ],'subject'=> 'email title','headers?']
     * 
     * @return SendgridService $this
     */
    public function setEmailHeaders(array $headers): SendgridService {
        if (!array_key_exists('from', $headers) || !array_key_exists('to', $headers)) {
            throw new Exception('Email sem destinátario ou remetente');
        }
        $this->email->setFrom($headers['from']['address'], $headers['from']['name']);
        $this->email->addTo($headers['to']['address'], $headers['to']['name']);
        if (array_key_exists('subject', $headers)) {
            $this->email->setSubject($headers['subject']);
        }

        return $this;
    }

    /**
     * @param Array $content ["text/html" => "<h1>Email</h1>"]
     * 
     * @return SendgridService $this
     */
    public function setEmailContent(array $contents): SendgridService {
        $this->email->addContents($contents);
        return $this;
    }

    /**
     * @param string $template_id 
     * @param Array $template_data ["name" => "User 1"]
     * 
     * @return SendgridService $this
     */
    public function setTemplate(string $template_id, array $template_data): SendgridService {
        $this->email->setTemplateId($template_id);
        $this->email->addDynamicTemplateDatas($template_data);
        return $this;
    }

    /**

     * @param Array $file_data ["content" => FILE, "content_type" => 'image/png', 'file_name' => 'relatorio.png', 'disposition' => 'attachment']
     * 
     * @return SendgridService $this
     */
    public function setAttachment(array $file_data) {
        $attachment = new Attachment();
        $attachment->setContent($file_data['content']);
        $attachment->setType($file_data['content_type']);
        $attachment->setFilename($file_data['file_name']);
        $attachment->setDisposition($file_data['disposition']);
        $this->email->setAttachment($attachment);
        return $this;
    }

    public function sendEmail() {

        $resp = $this->service->send($this->email);
        return $resp;
    }

}
