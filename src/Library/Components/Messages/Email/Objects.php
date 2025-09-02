<?php

namespace Canvastack\Canvastack\Library\Components\Messages\Email;

use Illuminate\Support\Facades\Mail;

/**
 * Created on Jul 1, 2023
 *
 * Time Created : 9:27:38 PM
 *
 * @filesource  Objects.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 */
class Objects
{
    private $mail;

    private $data;

    private $title;

    private $message;

    private $from;

    private $to;

    private $cc;

    private $bcc;

    public function __construct()
    {
        //	$this->mail = new Mail();
    }

    private function setAddress($address, $address_name = [])
    {
        if (! is_array($address)) {
            $address = [$address];
        }

        $email = [];
        $email['email'] = [];
        $email['name'] = [];

        foreach ($address as $mailAddress) {
            if (! is_array($mailAddress)) {
                $splitMail = explode('@', $mailAddress);
                $email['name'][$mailAddress] = ucwords(str_replace('-', ' ', canvastack_clean_strings($splitMail[0])));
                $email['email'][] = $mailAddress;
            }
        }
        unset($address);

        return $email;
    }

    public function from($address, $address_name = [])
    {
        $this->from = $this->setAddress($address, $address_name);
    }

    public function to($address, $address_name = [])
    {
        $this->to = $this->setAddress($address, $address_name);
    }

    public function cc($address, $address_name = [])
    {
        $this->cc = $this->setAddress($address, $address_name);
    }

    public function bcc($address, $address_name = [])
    {
        $this->bcc = $this->setAddress($address, $address_name);
    }

    public function subject($string)
    {
        $this->data['subject'] = $string;
    }

    public function title($string)
    {
        $this->title = $string;
        $this->data[__FUNCTION__] = $string;
    }

    public function message($string)
    {
        $message = $string.'<br /><p>'.canvastack_config('email.feet.text').',</p><p>'.canvastack_config('email.feet.signature').'.</p>';

        $this->message = $message;
        $this->data['body'] = $message;
    }

    public function send($mailData = [])
    {
        if (! empty($mailData)) {
            $this->data = $mailData;
        }

        $mail = new Mail();
        $this->mail = $mail::to($this->to['email'], $this->to['name']);

        if (! empty($this->cc)) {
            $this->mail->cc($this->cc['email'], $this->cc['name']);
        }
        if (! empty($this->bcc)) {
            $this->mail->bcc($this->bcc['email'], $this->bcc['name']);
        }

        $this->mail->send(new Email($this->data));
    }
}
