<?php

namespace Altelma\LaravelSignRequest;

use GuzzleHttp\Client;

class SignRequestService
{
    const API_BASEURL = 'https://[SUBDOMAIN]signrequest.com/api/v1';

    private $client;
    private $subdomain;

    public function __construct($token, $subdomain = null)
    {
        $this->subdomain = $subdomain;
        $this->client = $client = new Client([
            'base_uri' => $this->getApiURL(),
            'headers'  => [
                'Authorization' => 'Token '.$token,
            ],
        ]);
    }

    /**
     * Gets templates from sign request frontend.
     *
     * @throws Exceptions\RequestException
     *
     * @return \stdClass response
     */
    public function getTemplates()
    {
        $response = $this->newRequest('templates', 'get')->send();

        return json_decode($response->body);
    }

    /**
     * Send a document to SignRequest.
     *
     * @param string $file        The absolute path to a file.
     * @param string $identifier  unique identifier for this file
     * @param string $callbackUrl [optional] url to call when signing is completed
     * @param string $filename    [optional] the filename as the signer will see it
     * @param array  $settings    [optional]
     *
     * @return \stdClass response
     */
    public function createDocument(
        $file,
        $identifier = null,
        $callbackUrl = null,
        $filename = null,
        $settings = []
    ) {
        $data = [
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => curl_file_create($file, null, $filename),
                ],
                [
                    'name'     => 'external_id',
                    'contents' => $identifier,
                ],
                [
                    'name'     => 'events_callback_url',
                    'contents' => $callbackUrl,
                ],
            ],
        ];

        $data = array_merge($settings, $data);
        $response = $this->newRequest('documents', 'post', $data);

        return json_decode($response->getBody());
    }

    /**
     * Send a document to SignRequest using the file_from_url option.
     *
     * @param string $url         The URL of the page we want to sign.
     * @param string $identifier
     * @param string $callbackUrl
     * @param array  $settings    [optional]
     *
     * @return \stdClass response
     */
    public function createDocumentFromURL(
        $url,
        $identifier = null,
        $callbackUrl = null,
        $settings = []
    ) {
        $data = [
            'multipart' => [
                [
                    'name'     => 'file_from_url',
                    'contents' => $url,
                ],
                [
                    'name'     => 'external_id',
                    'contents' => $identifier,
                ],
                [
                    'name'     => 'events_callback_url',
                    'contents' => $callbackUrl,
                ],
            ],
        ];

        $data = array_merge($settings, $data);
        $response = $this->newRequest('documents', 'post', $data);

        return json_decode($response->getBody());
    }

    /**
     * Send a document to SignRequest using the template option.
     *
     * @param string $url         the URL of the template we want to sign
     * @param string $identifier
     * @param string $callbackUrl
     * @param array  $settings    [optional]
     *
     * @return \stdClass response
     */
    public function createDocumentFromTemplate(
        $url,
        $identifier = null,
        $callbackUrl = null,
        $settings = []
    ) {
        $data = [
            'multipart' => [
                [
                    'name'     => 'template',
                    'contents' => $url,
                ],
                [
                    'name'     => 'external_id',
                    'contents' => $identifier,
                ],
                [
                    'name'     => 'events_callback_url',
                    'contents' => $callbackUrl,
                ],
            ],
        ];

        $data = array_merge($settings, $data);
        $response = $this->newRequest('documents', 'post', $data);

        return json_decode($response->getBody());
    }

    /**
     * Add attachment to document sent to SignRequest.
     *
     * @param string    $file     The absolute path to a file.
     * @param \stdClass $document
     *
     * @return \stdClass response
     */
    public function addAttachmentToDocument($file, $document)
    {
        $file = curl_file_create($file);
        $data = [
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => $file,
                ],
                [
                    'name'     => 'document',
                    'contents' => $document->url,
                ],
            ],
        ];

        $response = $this->newRequest('document-attachments', 'post', $data);

        return json_decode($response->getBody());
    }

    /**
     * Send a sign request for a created document.
     *
     * @param string $documentId    uuid
     * @param string $sender        Senders e-mail address
     * @param array  $recipients
     * @param string $message
     * @param bool   $sendReminders Send automatic reminders
     * @param array  $settings      Add additional request parameters or override defaults
     *
     * @throws Exceptions\SendSignRequestException
     *
     * @return \stdClass The SignRequest
     */
    public function sendSignRequest(
        $documentId,
        $sender,
        $recipients,
        $message = null,
        $sendReminders = false,
        $settings = []
    ) {
        foreach ($recipients as &$r) {
            if (!array_key_exists('language', $r)) {
                $r['language'] = self::$defaultLanguage;
            }
        }

        $data = array_merge([
            'disable_text'        => true,
            'disable_attachments' => true,
            'disable_date'        => true,
        ], $settings, [
            'document'       => $this->getApiURL().'/documents/'.$documentId.'/',
            'from_email'     => $sender,
            'message'        => $message,
            'signers'        => $recipients,
            'send_reminders' => $sendReminders,
        ]);

        $data = array_merge($data, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $this->newRequest('signrequests', 'post', $data);

        return json_decode($response->getBody());
    }

    /**
     * Send a reminder to all recipients who have not signed yet.
     *
     * @param string $signRequestId uuid
     *
     * @return \stdClass response
     */
    public function sendSignRequestReminder($signRequestId)
    {
        $response = $this->newRequest(
            'signrequests/'.$signRequestId.'/resend_signrequest_email',
            'post',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getBody());
    }

    /**
     * Cancel an existing sign request.
     *
     * @param string $signRequestId uuid
     *
     * @throws Exceptions\RemoteException
     *
     * @return mixed
     */
    public function cancelSignRequest($signRequestId)
    {
        $response = $this->newRequest(
            'signrequests/'.$signRequestId.'/cancel_signrequest',
            'post',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getBody());
    }

    /**
     * Gets the current status for a sign request.
     *
     * @param string $signRequestId uuid
     *
     * @return \stdClass response
     */
    public function getSignRequestStatus($signRequestId)
    {
        $response = $this->newRequest('signrequests/'.$signRequestId, 'get');

        return json_decode($response->getBody());
    }

    /**
     * Get a file.
     *
     * @param string $documentId uuid
     *
     * @return \stdClass response
     */
    public function getDocument($documentId)
    {
        $response = $this->newRequest('documents/'.$documentId, 'get');

        return json_decode($response->getBody());
    }

    /**
     * Setup a base request object.
     *
     * @param string $action
     * @param string $method post,put,get,delete,option
     *
     * @return Request
     */
    private function newRequest($action, $method, $data = [])
    {
        $url = $this->getApiURL().'/'.$action.'/';

        switch ($method) {
            case 'post':
                $baseRequest = $this->client->request($method, $url, $data);
                break;

            default:
                $baseRequest = $this->client->request($method, $url);
                break;
        }

        return $baseRequest;
    }

    private function getApiURL()
    {
        return preg_replace('/\[SUBDOMAIN\]/', ltrim($this->subdomain.'.', '.'), self::API_BASEURL);
    }

    /**
     * Check for error in status headers.
     *
     * @param Response $response
     *
     * @return bool
     */
    private function hasErrors($response)
    {
        return !preg_match('/^20\d$/', $response->statusCode);
    }
}
