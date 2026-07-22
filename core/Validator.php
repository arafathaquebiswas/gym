<?php

final class Validator
{
    private array $errors = [];

    public function __construct(private array $data)
    {
    }

    public function required(string $field, string $label): static
    {
        if (trim((string) ($this->data[$field] ?? '')) === '') {
            $this->errors[$field] = "$label is required.";
        }
        return $this;
    }

    public function email(string $field, string $label = 'Email'): static
    {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "$label must be a valid email address.";
        }
        return $this;
    }

    public function minLength(string $field, int $length, string $label): static
    {
        if (!empty($this->data[$field]) && strlen((string) $this->data[$field]) < $length) {
            $this->errors[$field] = "$label must be at least $length characters.";
        }
        return $this;
    }

    public function matches(string $field, string $otherField, string $label): static
    {
        if (($this->data[$field] ?? null) !== ($this->data[$otherField] ?? null)) {
            $this->errors[$field] = "$label does not match.";
        }
        return $this;
    }

    public function phone(string $field, string $label = 'Phone'): static
    {
        if (!empty($this->data[$field]) && !preg_match('/^[0-9+\-\s]{7,20}$/', (string) $this->data[$field])) {
            $this->errors[$field] = "$label is not a valid phone number.";
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors === [] ? null : reset($this->errors);
    }
}
