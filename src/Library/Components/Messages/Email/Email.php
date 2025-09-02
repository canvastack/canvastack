<?php

namespace Canvastack\Canvastack\Library\Components\Messages\Email;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Created on Jul 1, 2023
 *
 * Time Created : 1:39:04 AM
 *
 * @filesource  Email.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 */
class Email extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;

    public $subject = 'IncoDIY Information';

    public function __construct($mailData)
    {
        if (! empty($mailData['subject'])) {
            $this->subject = $mailData['subject'];
            unset($mailData['subject']);
        }

        $this->mailData = $mailData;
    }

    public function build()
    {
        $this->subject($this->subject)->view(canvastack_config('template').'.emails.default');

        return $this;
    }
}
