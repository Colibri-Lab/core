<?php

namespace Colibri\Common;

use PHPMailer\PHPMailer\PHPMailer;
use Colibri\AppException;

class SmtpHelper
{
    public static function Send($configArray, $address, $subject, $body) {

        $smtpEnabled = $configArray['smtp']['enabled'];
        if(!$smtpEnabled) {
            return;
        }

        $smtpHost = isset($configArray['smtp']['host']) ? $configArray['smtp']['host'] : '';
        // $smtpPort = isset($configArray['smtp']['port']) ? $configArray['smtp']['port'] : ''
        // $smtpSecure = isset($configArray['smtp']['secure']) ? $configArray['smtp']['secure'] : ''
        $smtpUser = isset($configArray['smtp']['user']) ? $configArray['smtp']['user']: '';
        $smtpPassword = isset($configArray['smtp']['password']) ? $configArray['smtp']['password'] : '';
        $smtpFrom = isset($configArray['smtp']['from']) ? $configArray['smtp']['from'] : '';
        $smtpFromName = isset($configArray['smtp']['fromname']) ? $configArray['smtp']['fromname'] : '';

        $mailer = new PHPMailer();
        $mailer->CharSet = 'utf-8';
        $mailer->isSMTP();
        $mailer->Host = $smtpHost;
        $mailer->SMTPAuth = true;
        $mailer->Username = $smtpUser;
        $mailer->Password = $smtpPassword;

        $mailer->setFrom($smtpFrom, $smtpFromName);
        $mailer->Subject = $subject;
        $mailer->isHTML();
        $mailer->Body = $body;
        $mailer->addAddress($address);
        try {
            if(!$mailer->Send()) {
                throw new AppException($mailer->ErrorInfo);
            }
        }
        catch(AppException $e) {
            throw new AppException('Не получилось отправить письмо!', 500, $e);
        }
        unset($mailer);
    }
}