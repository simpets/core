<?php


class PrivateMessage
{
    private $sender_id;
    private $recipient_id;
    private $subject;
    private $body;

    public function setsender($sender_id) {
        $this->sender_id = $sender_id;
    }
    public function setrecipient($recipient_id) {
        $this->recipient_id = $recipient_id;
    }
    public function setmessage($subject, $body) {
        $this->subject = $subject;
        $this->body = $body;
    }
    public function post() {
        global $pdo;
        $stmt = $pdo->prepare(
            "INSERT INTO messages (sender_id, recipient_id, subject, body, sent_at, is_read)
             VALUES (?, ?, ?, ?, NOW(), 0)"
        );
        $stmt->execute([
            $this->sender_id,
            $this->recipient_id,
            $this->subject,
            $this->body
        ]);
    }
}
?>