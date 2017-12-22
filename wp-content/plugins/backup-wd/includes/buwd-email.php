<?php

class Buwd_Email
{
    public static function send($parrams)
    {
        $recipient = $parrams['recipient'];
        if (!empty($recipient)) {
            $from = $parrams['from'];
            $email_from = $parrams['email_from'];
            $headers = array();
            $headers[] = "From: " . $from . " <" . $email_from . ">";
            $headers[] = "Content-Type: text/html; charset=" . get_bloginfo('charset');

            $subject = $parrams['subject'];
            $body = $parrams['body'];
            $attachment = $parrams['attachment'];

            wp_mail($recipient, $subject, $body, $headers, $attachment);
        }
    }
}