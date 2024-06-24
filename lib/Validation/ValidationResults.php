<?php

namespace SoftwarePunt\Instarecord\Validation;

class ValidationResults
{
    public readonly array $results;
    public readonly bool $ok;
    public readonly array $messages;

    /**
     * @param ValidationResult[] $results
     */
    public function __construct(array $results)
    {
        $this->results = $results;

        $ok = true;
        $messages = [];

        foreach ($this->results as $result) {
            if ($result->ok) {
                // Pass, no error
                continue;
            }

            $ok = false;
            $messages[] = $result->message;
        }

        $this->ok = $ok;
        $this->messages = $messages;
    }
}