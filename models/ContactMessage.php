<?php

final class ContactMessage extends Model
{
    public function create(string $name, string $email, string $phone, string $subject, string $message): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at)
             VALUES (:name, :email, :phone, :subject, :message, "new", NOW())'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
