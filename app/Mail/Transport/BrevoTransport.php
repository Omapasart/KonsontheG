<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class BrevoTransport extends AbstractTransport
{
    public function __construct(
        protected string $key,
        protected int $timeout = 30,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if ($this->key === '') {
            throw new TransportException('Brevo API key is missing. Set BREVO_API_KEY in the environment.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $sender = $message->getEnvelope()->getSender();
        $html = $email->getHtmlBody();
        $text = $email->getTextBody();

        if (($html === null || $html === '') && is_string($text) && $text !== '') {
            $html = nl2br(e($text), false);
        }

        $payload = [
            'sender' => array_filter([
                'email' => $sender->getAddress(),
                'name' => $sender->getName() !== '' ? $sender->getName() : null,
            ]),
            'to' => $this->mapAddresses($email->getTo()),
            'subject' => $email->getSubject() ?: 'KONSONTHEGO Tournament',
            'htmlContent' => $html ?: '<p></p>',
        ];

        if (is_string($text) && $text !== '') {
            $payload['textContent'] = $text;
        }

        $cc = $this->mapAddresses($email->getCc());
        if ($cc !== []) {
            $payload['cc'] = $cc;
        }

        $bcc = $this->mapAddresses($email->getBcc());
        if ($bcc !== []) {
            $payload['bcc'] = $bcc;
        }

        $replyTo = $this->mapAddresses($email->getReplyTo());
        if ($replyTo !== []) {
            $payload['replyTo'] = $replyTo[0];
        }

        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['api-key' => $this->key])
            ->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->failed()) {
            throw new TransportException(sprintf(
                'Brevo API failed (%s): %s',
                $response->status(),
                $response->body()
            ));
        }
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array{email: string, name?: string}>
     */
    protected function mapAddresses(array $addresses): array
    {
        return array_values(array_map(function (Address $address): array {
            $mapped = ['email' => $address->getAddress()];

            if ($address->getName() !== '') {
                $mapped['name'] = $address->getName();
            }

            return $mapped;
        }, $addresses));
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
