<?php


namespace NsUtil\Integracao\OpenAI;

use NsUtil\Helper;

class ChatGPT
{

    private $apiKey;
    private $baseUrl = 'https://api.openai.com/v1/completions';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function generateText($prompt, $model = 'text-davinci-003', $temperature = 0.9, $max_tokens = 3646, $top_p = 1, $frequency_penalty = 0, $presence_penalty = 0.6, $stop = ["Human:", "AI:"]): string
    {
        $curl = curl_init();

        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
            'top_p' => $top_p,
            'frequency_penalty' => $frequency_penalty,
            'presence_penalty' => $presence_penalty,

        ];

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->apiKey
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $ret = json_decode($response)->choices;
        return (string) trim(implode("\n", array_map(fn ($item) => $item->text, $ret)));
    }
}
