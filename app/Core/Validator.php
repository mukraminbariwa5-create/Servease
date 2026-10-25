<?php
declare(strict_types=1);

/**
 * Minimal validator.
 *   $v = Validator::make($_POST, ['email' => 'required|email|max:255', 'age' => 'nullable|int|min:18']);
 *   if ($v->fails()) back_with_errors($v->errors());
 *   $data = $v->validated();
 */
final class Validator
{
    private array $errors = [];
    private array $clean = [];

    private function __construct(private array $data, private array $rules, private array $labels) {}

    public static function make(array $data, array $rules, array $labels = []): self
    {
        $v = new self($data, $rules, $labels);
        $v->run();
        return $v;
    }

    public function fails(): bool { return (bool) $this->errors; }
    public function errors(): array { return $this->errors; }
    public function validated(): array { return $this->clean; }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $raw = $this->data[$field] ?? null;
            $value = is_string($raw) ? trim($raw) : $raw;
            $label = $this->label($field);
            $empty = $value === null || $value === '' || $value === [];

            if ($empty) {
                if (in_array('required', $rules, true)) $this->errors[$field] = self::sentence("$label is required.");
                else $this->clean[$field] = null;
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $error = match ($name) {
                    'email'    => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "Enter a valid email address.",
                    'int'      => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "$label must be a whole number.",
                    'numeric'  => is_numeric($value) ? null : "$label must be a number.",
                    'min'      => is_numeric($value) && !in_array('string', $rules, true)
                                    ? ((float) $value >= (float) $arg ? null : "$label must be at least $arg.")
                                    : (mb_strlen((string) $value) >= (int) $arg ? null : "$label must be at least $arg characters."),
                    'max'      => is_numeric($value) && !in_array('string', $rules, true)
                                    ? ((float) $value <= (float) $arg ? null : "$label may not be greater than $arg.")
                                    : (mb_strlen((string) $value) <= (int) $arg ? null : "$label may not be longer than $arg characters."),
                    'in'       => in_array((string) $value, explode(',', (string) $arg), true) ? null : "Choose a valid $label.",
                    'date'     => strtotime((string) $value) !== false ? null : "$label must be a valid date.",
                    'after_today' => strtotime((string) $value) >= strtotime('today') ? null : "$label can't be in the past.",
                    'phone'    => preg_match('/^[0-9+\-\s()]{7,20}$/', (string) $value) ? null : "Enter a valid phone number.",
                    'password' => (mb_strlen((string) $value) >= 8 && preg_match('/[A-Za-z]/', (string) $value) && preg_match('/\d/', (string) $value))
                                    ? null : "Password must be at least 8 characters and include a letter and a number.",
                    'confirmed'=> ($this->data[$field . '_confirmation'] ?? null) === $value ? null : "$label confirmation does not match.",
                    'exists'   => self::exists((string) $arg, $value) ? null : "Choose a valid $label.",
                    default    => null,
                };
                if ($error) { $this->errors[$field] = self::sentence($error); break; }
            }
            if (!isset($this->errors[$field])) $this->clean[$field] = $value;
        }
    }

    /** Messages start with a capital letter even when the field label is lowercase ("date" → "Date can't…"). */
    private static function sentence(string $msg): string
    {
        return mb_strtoupper(mb_substr($msg, 0, 1)) . mb_substr($msg, 1);
    }

    /** exists:table,column */
    private static function exists(string $arg, $value): bool
    {
        [$table, $column] = explode(',', $arg);
        if (!preg_match('/^\w+$/', $table) || !preg_match('/^\w+$/', $column)) return false;
        return (bool) DB::value("SELECT 1 FROM `$table` WHERE `$column` = ? LIMIT 1", [$value]);
    }
}
