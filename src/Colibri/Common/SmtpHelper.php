<?php

namespace Colibri\Common;

use PHPMailer\PHPMailer\PHPMailer;
use Colibri\AppException;

class SmtpHelper
{
    public static function Send(array $configArray, string $address, string $subject, string $body, array $attachments = []): void
    {

        $smtpEnabled = $configArray['enabled'];
        if (!$smtpEnabled) {
            return;
        }

        $smtpHost = isset($configArray['host']) ? $configArray['host'] : '';
        $smtpPort = isset($configArray['port']) ? $configArray['port'] : '';
        $smtpSecure = isset($configArray['secure']) ? $configArray['secure'] : '';
        $smtpUser = isset($configArray['user']) ? $configArray['user'] : '';
        $smtpPassword = isset($configArray['password']) ? $configArray['password'] : '';
        $smtpFrom = isset($configArray['from']) ? $configArray['from'] : '';
        $smtpFromName = isset($configArray['fromname']) ? $configArray['fromname'] : '';

        $mailer = new PHPMailer();
        $mailer->CharSet = 'utf-8';
        $mailer->isSMTP();
        $mailer->Host = $smtpHost;
        $mailer->Port = $smtpPort;
        $mailer->SMTPSecure = $smtpSecure;
        $mailer->SMTPAuth = true;
        $mailer->Username = $smtpUser;
        $mailer->Password = $smtpPassword;

        $mailer->setFrom($smtpFrom, $smtpFromName);
        $mailer->Subject = $subject;
        $mailer->isHTML();
        $mailer->Body = $body;
        $mailer->addAddress($address);
        foreach($attachments as $attachment) {
            $mailer->addAttachment($attachment['path'], $attachment['name'], $attachment['encoding'] ?? PHPMailer::ENCODING_BASE64, $attachment['type'] ?? '', $attachment['disposition'] ?? 'attachment');
        }
        try {
            if (!$mailer->Send()) {
                throw new AppException($mailer->ErrorInfo);
            }
        } catch (AppException $e) {
            throw new AppException('Не получилось отправить письмо!', 500, $e);
        }
        unset($mailer);
    }
}