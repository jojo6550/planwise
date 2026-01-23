<?php
/**
 * Validator Class
 * Handles server-side input validation using filter_input, filter_var, empty(), strlen()
 * CS334 Module 1 - Input validation (40 marks)
 */

class Validator
{
    private $errors = [];

    /**
     * Validate data against rules
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return bool True if valid, false otherwise
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            // Convert rules string to array if needed
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }

            foreach ($fieldRules as $rule) {
                if (!$this->validateRule($field, $value, $rule)) {
                    break; // Stop validating this field on first error
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate a single rule
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Validation rule
     * @return bool True if valid, false otherwise
     */
    private function validateRule(string $field, $value, string $rule): bool
    {
        // Parse rule and parameters
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $parameter = $parts[1] ?? null;

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0' && $value !== 0) {
                    $this->addError($field, 'The ' . $field . ' field is required');
                    return false;
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'The ' . $field . ' must be a valid email address');
                    return false;
                }
                break;

            case 'min':
                if (!empty($value) && strlen($value) < (int)$parameter) {
                    $this->addError($field, 'The ' . $field . ' must be at least ' . $parameter . ' characters');
                    return false;
                }
                break;

            case 'max':
                if (!empty($value) && strlen($value) > (int)$parameter) {
                    $this->addError($field, 'The ' . $field . ' may not be greater than ' . $parameter . ' characters');
                    return false;
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, 'The ' . $field . ' must be a number');
                    return false;
                }
                break;

            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'The ' . $field . ' must be a valid URL');
                    return false;
                }
                break;

            case 'alpha':
                if (!empty($value) && !preg_match('/^[a-zA-Z]+$/', $value)) {
                    $this->addError($field, 'The ' . $field . ' may only contain letters');
                    return false;
                }
                break;

            case 'alphanum':
                if (!empty($value) && !preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                    $this->addError($field, 'The ' . $field . ' may only contain letters and numbers');
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Add validation error
     *
     * @param string $field Field name
     * @param string $message Error message
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Get all validation errors
     *
     * @return array Validation errors
     */
    public function getAllErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field
     *
     * @param string $field Field name
     * @return array Field errors
     */
    public function getErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if field has errors
     *
     * @param string $field Field name
     * @return bool True if field has errors
     */
    public function hasErrors(string $field = null): bool
    {
        if ($field === null) {
            return !empty($this->errors);
        }
        return isset($this->errors[$field]);
    }

    /**
     * Get first error for a field
     *
     * @param string $field Field name
     * @return string|null First error message or null
     */
    public function getFirstError(string $field): ?string
    {
        $errors = $this->getErrors($field);
        return $errors[0] ?? null;
    }

    /**
     * Sanitize input using filter_input
     *
     * @param int $type Input type (INPUT_POST, INPUT_GET, etc.)
     * @param string $variableName Variable name
     * @param int $filter Filter to apply
     * @param array $options Filter options
     * @return mixed Sanitized value
     */
    public static function sanitizeInput(int $type, string $variableName, int $filter = FILTER_SANITIZE_STRING, array $options = [])
    {
        return filter_input($type, $variableName, $filter, $options);
    }

    /**
     * Validate input using filter_input
     *
     * @param int $type Input type (INPUT_POST, INPUT_GET, etc.)
     * @param string $variableName Variable name
     * @param int $filter Filter to apply
     * @param array $options Filter options
     * @return mixed Validated value
     */
    public static function validateInput(int $type, string $variableName, int $filter = FILTER_VALIDATE_EMAIL, array $options = [])
    {
        return filter_input($type, $variableName, $filter, $options);
    }
}
