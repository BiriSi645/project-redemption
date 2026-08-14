<?php

namespace App\Controllers;

class EmailTest extends BaseController
{
    public function send()
    {
        $email = service('email');

        $email->setFrom(
            env('email.fromEmail'),
            env('email.fromName', 'Project Redemption')
        );

        // Test mailinin gideceği adres
        $email->setTo('ceydayzcyzc@gmail.com');

        $email->setSubject('Project Redemption - SMTP Test');

        $email->setMessage('
            <h2>SMTP çalışıyor.</h2>

            <p>
                Bu e-postayı görüyorsan Project Redemption
                Gmail üzerinden başarıyla mail gönderebiliyor.
            </p>
        ');

        $email->setMailType('html');

        if ($email->send(false)) {
            return 'Mail başarıyla gönderildi.';
        }

        return '<pre>'
            . esc($email->printDebugger(['headers', 'subject', 'body']))
            . '</pre>';
    }
}
