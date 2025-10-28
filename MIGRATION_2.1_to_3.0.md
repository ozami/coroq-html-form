# Migration Guide: coroq/html-form 2.1.0 to 3.0.0

This guide helps you upgrade from coroq/html-form version 2.1.0 to 3.0.0.

## Overview

Version 3.0.0 introduces breaking changes primarily due to:
- Upgrade to PHP 8.0+ with strict type declarations
- Dependency upgrade to `coroq/form` 3.0.0-alpha5
- Constructor changes requiring `ErrorMessageFormatter`
- Interface-based architecture instead of concrete `Form` class
- New Bootstrap 5 integration
- Removed `__call()` magic method

## System Requirements

### Before (2.1.0)
- PHP >= 7.2
- coroq/form ^2.0
- coroq/html ^0.2

### After (3.0.0)
- PHP >= 8.0
- coroq/form 3.0.0-alpha5
- coroq/html 0.2.0

## Breaking Changes

### 1. Constructor Signature Change

**The most critical breaking change**: `HtmlForm` constructor now requires an `ErrorMessageFormatter` parameter.

#### Before (2.1.0)
```php
use Coroq\Form\Form;
use Coroq\HtmlForm\HtmlForm;

$form = new Form();
$htmlForm = new HtmlForm($form);
```

#### After (3.0.0)
```php
use Coroq\Form\Form;
use Coroq\HtmlForm\HtmlForm;
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Form\BasicErrorMessages;

$form = new Form();
$formatter = new ErrorMessageFormatter();
$formatter->setMessages(BasicErrorMessages::get());
$htmlForm = new HtmlForm($form, $formatter);
```

**Action Required**: Update all `HtmlForm` instantiations to include the `ErrorMessageFormatter` parameter.

### 2. FormInterface Instead of Concrete Form Class

The constructor now accepts `FormInterface` instead of the concrete `Form` class.

#### Before (2.1.0)
```php
use Coroq\Form\Form;

class HtmlForm {
  protected $form;
  public function __construct(Form $form) {
    $this->form = $form;
  }
}
```

#### After (3.0.0)
```php
use Coroq\Form\FormInterface;

class HtmlForm {
  private FormInterface $form;
  public function __construct(FormInterface $form, ErrorMessageFormatter $errorMessageFormatter) {
    $this->form = $form;
    $this->errorMessageFormatter = $errorMessageFormatter;
  }
}
```

**Impact**: More flexible - you can now pass any class implementing `FormInterface`, not just `Form` instances.

### 3. Strict Type Declarations

All methods now have strict type hints for parameters and return types.

#### Before (2.1.0)
```php
public function value($item_path) {
  // ...
}

public function format($item_path, $format): Html {
  // ...
}
```

#### After (3.0.0)
```php
public function value(string|array $item_path): Html {
  // ...
}

public function format(string|array $item_path, string $format): Html {
  // ...
}
```

**Impact**: Type errors will now be caught at runtime. Ensure you're passing correct types.

### 4. Property Visibility Changes

Properties changed from `protected` to `private`.

#### Before (2.1.0)
```php
class HtmlForm {
  /** @var Form */
  protected $form;
}
```

#### After (3.0.0)
```php
class HtmlForm {
  private FormInterface $form;
  private ErrorMessageFormatter $errorMessageFormatter;
}
```

**Action Required**: If you extended `HtmlForm` and accessed `$this->form`, use `$this->getForm()` instead.

### 5. Removed `__call()` Magic Method

The magic `__call()` method that proxied calls to the underlying form has been removed.

#### Before (2.1.0)
```php
// You could call Form methods directly on HtmlForm
$htmlForm->getOptions(); // Proxied to $form->getOptions()
```

#### After (3.0.0)
```php
// Must explicitly get the form first
$htmlForm->getForm()->getOptions();
```

**Action Required**: Replace direct method calls with `$htmlForm->getForm()->methodName()`.

### 6. `getItemIn()` Implementation Changes

The internal `getItemIn()` method now uses `FormInterface::getItem()` instead of `Form::getItemIn()`.

#### Before (2.1.0)
```php
// Used Form's getItemIn() method
protected function getItemIn($item_path) {
  return $this->form->getItemIn($item_path);
}
```

#### After (3.0.0)
```php
// Manually traverses using FormInterface::getItem()
protected function getItemIn(string|array $item_path): FormItemInterface {
  $path = is_array($item_path) ? $item_path : explode("/", $item_path);
  $current = $this->form;
  foreach ($path as $segment) {
    if ($current instanceof \Coroq\Form\FormInterface) {
      $current = $current->getItem($segment);
      if ($current === null) {
        throw new \LogicException("Item '$segment' not found in form");
      }
    }
    else {
      throw new \LogicException("Cannot traverse path - current item is not a FormInterface");
    }
  }
  return $current;
}
```

**Impact**: Better error messages when items are not found, but may have different exception types.

### 7. Bootstrap Integration Changes

#### Bootstrap 4
The `Bootstrap4` integration class now has strict type hints.

**Before (2.1.0)**
```php
class Bootstrap4 extends HtmlForm {
  public function input($item_path, $type): Html {
    // ...
  }
}
```

**After (3.0.0)**
```php
class Bootstrap4 extends HtmlForm {
  public function input(string|array $item_path, string $type): Html {
    // ...
  }
}
```

Also changed: `$this->form->getItemIn()` is now `$this->getItemIn()`.

#### Bootstrap 5 (NEW)
A new `Bootstrap5` integration class is available at `Coroq\HtmlForm\Integration\Bootstrap5`.

```php
use Coroq\HtmlForm\Integration\Bootstrap5;
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Form\BasicErrorMessages;

$formatter = new ErrorMessageFormatter();
$formatter->setMessages(BasicErrorMessages::get());
$htmlForm = new Bootstrap5($form, $formatter);
```

Key differences from Bootstrap 4:
- File inputs use `form-control` class (not `form-control-file`)
- Select elements use `form-select` class (not `form-control`)

## Migration Steps

### Step 1: Update Composer Dependencies

Update your `composer.json`:

```json
{
  "require": {
    "php": ">=8.0",
    "coroq/html-form": "^3.0",
    "coroq/form": "3.0.0-alpha5"
  }
}
```

Then run:
```bash
composer update coroq/html-form coroq/form
```

### Step 2: Update PHP Version

Ensure your project runs on PHP 8.0 or higher. Update your server/environment if needed.

### Step 3: Update All HtmlForm Instantiations

Find all places where you create `HtmlForm` instances and add the `ErrorMessageFormatter` parameter.

**Search pattern**: Look for `new HtmlForm(` or `new Bootstrap4(`

**Example change**:
```php
// Before
$htmlForm = new HtmlForm($form);

// After
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Form\BasicErrorMessages;

$formatter = new ErrorMessageFormatter();
$formatter->setMessages(BasicErrorMessages::get());
$htmlForm = new HtmlForm($form, $formatter);
```

### Step 4: Replace Direct Form Method Calls

Search for calls to form methods on `$htmlForm` and update them.

```php
// Before
$options = $htmlForm->getOptions();

// After
$options = $htmlForm->getForm()->getOptions();
```

**Common methods that need updating**:
- `getOptions()`
- `setOptions()`
- `getData()`
- `getItem()`
- `setData()`
- Any other `Form` methods not directly provided by `HtmlForm`

### Step 5: Update Custom Extensions

If you have custom classes extending `HtmlForm`:

1. Add strict type hints to all method overrides
2. Replace `$this->form` with `$this->getForm()`
3. Replace `$this->form->getItemIn()` with `$this->getItemIn()`
4. Update constructor to accept `ErrorMessageFormatter`

**Example**:
```php
// Before
class CustomHtmlForm extends HtmlForm {
  public function customMethod($item_path) {
    $item = $this->form->getItemIn($item_path);
    // ...
  }
}

// After
class CustomHtmlForm extends HtmlForm {
  public function customMethod(string|array $item_path): Html {
    $item = $this->getItemIn($item_path);
    // ...
  }
}
```

### Step 6: Update Bootstrap Integrations

If using Bootstrap 4:
```php
// Before
use Coroq\HtmlForm\Integration\Bootstrap4;
$htmlForm = new Bootstrap4($form);

// After
use Coroq\HtmlForm\Integration\Bootstrap4;
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Form\BasicErrorMessages;

$formatter = new ErrorMessageFormatter();
$formatter->setMessages(BasicErrorMessages::get());
$htmlForm = new Bootstrap4($form, $formatter);
```

If upgrading to Bootstrap 5:
```php
use Coroq\HtmlForm\Integration\Bootstrap5;
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Form\BasicErrorMessages;

$formatter = new ErrorMessageFormatter();
$formatter->setMessages(BasicErrorMessages::get());
$htmlForm = new Bootstrap5($form, $formatter);
```

### Step 7: Test Thoroughly

Run your test suite and manually test all forms:
- Verify form rendering
- Test validation and error messages
- Check that all input types render correctly
- Verify custom extensions still work

## New Features in 3.0.0

### Error Message Formatting
The new `ErrorMessageFormatter` gives you better control over error message formatting:

```php
use Coroq\Form\ErrorMessageFormatter;

$formatter = new ErrorMessageFormatter();
// Customize error messages as needed
$formatter->setMessages([
  'required' => 'This field is required',
  'minLength' => 'Minimum length is {minLength}',
  // ... more messages
]);
$htmlForm = new HtmlForm($form, $formatter);
```

### Bootstrap 5 Support
Native support for Bootstrap 5 with proper CSS classes:

```php
use Coroq\HtmlForm\Integration\Bootstrap5;

$htmlForm = new Bootstrap5($form, $formatter);
echo $htmlForm->inputText('username'); // Includes form-control class
echo $htmlForm->select('country');     // Includes form-select class
```

### Better Type Safety
Strict type hints throughout the codebase help catch errors earlier:

```php
// Type errors now caught:
$htmlForm->value(['invalid', 'path']); // OK
$htmlForm->value(123);                 // TypeError
```

### Improved Error Handling
Better exception messages when items are not found:

```php
// Now throws LogicException with clear message:
// "Item 'nonexistent' not found in form"
$htmlForm->value('nonexistent');
```

## Common Issues and Solutions

### Issue: "Too few arguments to function HtmlForm::__construct()"

**Cause**: Missing `ErrorMessageFormatter` parameter.

**Solution**: Add the formatter parameter:
```php
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Form\BasicErrorMessages;

$formatter = new ErrorMessageFormatter();
$formatter->setMessages(BasicErrorMessages::get());
$htmlForm = new HtmlForm($form, $formatter);
```

### Issue: "Call to undefined method HtmlForm::someFormMethod()"

**Cause**: Removed `__call()` magic method.

**Solution**: Use `getForm()`:
```php
// Before
$htmlForm->someFormMethod();

// After
$htmlForm->getForm()->someFormMethod();
```

### Issue: "Cannot access private property HtmlForm::$form"

**Cause**: Property changed from `protected` to `private`.

**Solution**: Use `getForm()` method:
```php
// Before (in subclass)
$this->form->something();

// After
$this->getForm()->something();
```

### Issue: TypeError with method parameters

**Cause**: Strict type declarations now enforced.

**Solution**: Ensure you're passing the correct types:
```php
// Wrong
$htmlForm->value(123);

// Correct
$htmlForm->value('field_name');
// or
$htmlForm->value(['nested', 'field']);
```

## Rollback Plan

If you need to rollback to 2.1.0:

```bash
composer require coroq/html-form:^2.1 coroq/form:^2.0
```

Then revert your code changes.

## Support and Resources

- **Documentation**: See README.md for current usage examples
- **Tests**: See test/FormTest.php for comprehensive examples
- **Issues**: Report issues at the project's repository

## Conclusion

While version 3.0.0 introduces breaking changes, the migration is straightforward:

1. Upgrade to PHP 8.0+
2. Add `ErrorMessageFormatter` to all `HtmlForm` constructor calls
3. Replace direct form method calls with `getForm()`
4. Add type hints to custom extensions
5. Update Bootstrap integrations

The benefits include better type safety, improved error handling, Bootstrap 5 support, and a more flexible interface-based architecture.
