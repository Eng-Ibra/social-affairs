<?php

namespace App\Core;

class Validator
{
    private array $errors = [];

    public function __construct(private array $data, private array $rules)
    {
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function fails(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            $isNumericField = in_array('numeric', $rules, true);
            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule, $isNumericField);
            }
        }
        return count($this->errors) > 0;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, $value, string $rule, bool $isNumericField = false): void
    {
        $param = null;
        if (str_contains($rule, ':')) {
            [$rule, $param] = explode(':', $rule, 2);
        }

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                    $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                }
                break;
            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'Please enter a valid email address.');
                }
                break;
            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be a number.');
                }
                break;
            case 'date':
                if ($value !== null && $value !== '' && strtotime($value) === false) {
                    $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be a valid date.');
                }
                break;
            case 'min':
                if ($value === null || $value === '') {
                    break;
                }
                if ($isNumericField) {
                    if ((float) $value < (float) $param) {
                        $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least {$param}.");
                    }
                } elseif (mb_strlen((string) $value) < (int) $param) {
                    $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least {$param} characters.");
                }
                break;
            case 'max':
                if ($value === null || $value === '') {
                    break;
                }
                if ($isNumericField) {
                    if ((float) $value > (float) $param) {
                        $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$param}.");
                    }
                } elseif (mb_strlen((string) $value) > (int) $param) {
                    $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$param} characters.");
                }
                break;
            case 'in':
                $options = explode(',', (string) $param);
                if ($value !== null && $value !== '' && !in_array($value, $options, true)) {
                    $this->addError($field, 'Invalid value selected for ' . str_replace('_', ' ', $field) . '.');
                }
                break;
            case 'confirmed':
                if (($this->data[$field . '_confirmation'] ?? null) !== $value) {
                    $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' confirmation does not match.');
                }
                break;
            case 'unique':
                [$table, $column, $exceptId] = array_pad(explode(',', (string) $param), 3, null);
                if ($value !== null && $value !== '') {
                    $pdo = Database::connection();
                    $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
                    $params = [$value];
                    if ($exceptId) {
                        $sql .= ' AND id != ?';
                        $params[] = $exceptId;
                    }
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    if ((int) $stmt->fetchColumn() > 0) {
                        $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' is already taken.');
                    }
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
