<?php

namespace Arris\AuthLite;

/*
 * How to:
 *

$mailer = new Mailer(function($to, $subject, $message, $headers) {
    // Здесь может быть логика отправки через SMTP, API и т.д.
    echo "Sending email to $to with subject '$subject'\n";
    // Возвращаем true для имитации успешной отправки
    return true;
});

$mailer->send(
    to: 'recipient@example.com',
    subject: 'Custom Subject',
    message: 'Custom message',
    headers: "From: sender@example.com\r\n"
);
 */

class Mailer
{
    /**
     * @var callable|null Функция отправки почты
     */
    private $sender;

    /**
     * Конструктор Mailer.
     *
     * @param callable|null $customSender Кастомная функция отправки почты.
     *                                    Если null, используется sendmail.
     */
    public function __construct(callable $customSender = null) {
        $this->sender = $customSender ?? [$this, 'defaultSend'];
    }

    /**
     * Отправка письма
     *
     * @param string $to      Получатель
     * @param string $subject Тема письма
     * @param string $message Тело письма
     * @param string $headers Заголовки письма
     *
     * @return bool Успешность отправки
     */
    public function send(string $to, string $subject, string $message, string $headers = ''): bool
    {
        return call_user_func($this->sender, $to, $subject, $message, $headers);
    }

    /**
     * Стандартный метод отправки через sendmail
     *
     * @param string $to      Получатель
     * @param string $subject Тема письма
     * @param string $message Тело письма
     * @param string $headers Заголовки письма
     *
     * @return bool Успешность отправки
     */
    private function defaultSend(string $to, string $subject, string $message, string $headers = ''): bool
    {
        $fullHeaders = "To: $to\r\nSubject: $subject\r\n$headers";
        return mail($to, $subject, $message, $fullHeaders);
    }

}
