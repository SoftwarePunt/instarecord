# Validation
**Instarecord offers optional, built-in validation of model instances.**

## Introduction

### Setting constraints
You can specify constraints by adding attributes to the model properties.

```php
<?php

use Instarecord\Model;
use SoftwarePunt\Instarecord\Attributes\FriendlyName;use SoftwarePunt\Instarecord\Attributes\MaxLength;
use SoftwarePunt\Instarecord\Attributes\MinLength;

class User extends Model
{
    #[Required]
    #[MinLength(3, "Your name is too short!")]
    public string $name;

    #[Required]
    #[MaxLength(255)]
    #[FriendlyName("E-mail address")]
    public string $email;
}
```

### Running validations
⚠️ Validations are currently not run automatically when saving a model. You must call `validate()` manually.

You can validate a model by simply calling `validate()` on it:

```php
$user = new User();
$user->name = "A";
$user->email = "";
$result = $user->validate();

if (!$result->ok) {
    foreach ($result->errors as $error) {
        echo $error->message . "\n";
    }
}
``` 

The example above will output:

```
Your name is too short!
E-mail address is required.
```

## Validation attributes

Validation attributes live in the `Instarecord\Attributes` namespace.

### `#[FriendlyName(string $friendlyName)]`

Specifies a friendly name for the property. This is used in default error messages.

If no friendly name is specified, the property name is used.

### `#[Required]`

The property is required and its value cannot be:

- `null`
- empty string
- whitespace-only string
- `false`
- zero
- empty array

> {name} is required.

### `#[MinLength(int $minLength, ?string $customError = null)]`

Requires the property to be non-null, non-empty and at least `$minLength` characters long.

> {name} must be at least {minLength} characters.

### `#[MaxLength(int $maxLength, ?string $customError = null)]`

Requires the property to be at most `$maxLength` characters long.

> {name} can't be longer than {maxLength} characters.

## Custom validators
You can easily build custom validator attributes by simply extending the `ValidationAttribute` class.

```php
<?php

use Attribute;
use SoftwarePunt\Instarecord\Validation\ValidationAttribute;
use SoftwarePunt\Instarecord\Validation\ValidationResult;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MyCustomValidator extends ValidationAttribute
{
    public function __construct(public string $someParam, public ?string $customError = null)
    {
    }
    
    public function validate(string $name, mixed $value): ValidationResult
    {
        if ($value != $this->someParam) {
            return ValidationResult::fail($this->customError ?? "{$name} must be equal to '{$this->someParam}'.");
        }
        
        return ValidationResult::pass();
    }
}
```

`validate()` will be called with the friendly property name (if available), and the property's current value.

The validator should return a result by either calling `ValidationResult::pass()` or `ValidationResult::fail("some error")`.


