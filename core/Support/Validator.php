<?php

declare(strict_types=1);

namespace Kadosys\Core\Support;

/**
 * Validator
 *
 * Validador simples de dados de entrada baseado em regras nomeadas
 * (similar ao estilo Laravel, porem implementacao 100% propria).
 *
 * Exemplo:
 *   $validator = Validator::make($request->all(), [
 *       'nome' => 'required|min:3',
 *       'email' => 'required|email',
 *   ]);
 *
 *   if ($validator->fails()) {
 *       $errors = $validator->errors();
 *   }
 */
final class Validator
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     */
    private function __construct(
        private readonly array $data,
        private readonly array $rules
    ) {
        $this->validate();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     */
    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        $parameter = null;

        if (str_contains($rule, ':')) {
            [$rule, $parameter] = explode(':', $rule, 2);
        }

        match ($rule) {
            'required' => $this->ruleRequired($field, $value),
            'email' => $this->ruleEmail($field, $value),
            'min' => $this->ruleMin($field, $value, (int) $parameter),
            'max' => $this->ruleMax($field, $value, (int) $parameter),
            'numeric' => $this->ruleNumeric($field, $value),
            'confirmed' => $this->ruleConfirmed($field, $value),
            default => null,
        };
    }

    private function ruleRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->addError($field, "O campo {$field} e obrigatorio.");
        }
    }

    private function ruleEmail(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "O campo {$field} deve ser um e-mail valido.");
        }
    }

    private function ruleMin(string $field, mixed $value, int $min): void
    {
        if ($value !== null && mb_strlen((string) $value) < $min) {
            $this->addError($field, "O campo {$field} deve ter no minimo {$min} caracteres.");
        }
    }

    private function ruleMax(string $field, mixed $value, int $max): void
    {
        if ($value !== null && mb_strlen((string) $value) > $max) {
            $this->addError($field, "O campo {$field} deve ter no maximo {$max} caracteres.");
        }
    }

    private function ruleNumeric(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, "O campo {$field} deve ser numerico.");
        }
    }

    private function ruleConfirmed(string $field, mixed $value): void
    {
        $confirmation = $this->data[$field . '_confirmation'] ?? null;

        if ($value !== $confirmation) {
            $this->addError($field, "A confirmacao do campo {$field} nao corresponde.");
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna apenas os dados validados (uteis para insercao segura no banco).
     *
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return array_intersect_key($this->data, $this->rules);
    }
}
