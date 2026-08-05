# coroq/html-form

Generates HTML form elements from `coroq/form` objects with validation attributes and error handling.

## Installation

```bash
composer require coroq/html-form
```

Requires PHP 8.0+, `coroq/form` 3.x, and `coroq/html` 1.x.

## Basic Usage

```php
use Coroq\Form\Form;
use Coroq\Form\FormItem;
use Coroq\HtmlForm\HtmlForm;
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Form\Error;

// Create form
$form = new Form();
$form->username = (new FormItem\TextInput())
    ->setMinLength(3)
    ->setMaxLength(20);
$form->email = new FormItem\EmailInput();
$form->age = (new FormItem\IntegerInput())
    ->setMin(18)
    ->setMax(100);

// Create HTML form generator
$formatter = new ErrorMessageFormatter();
$formatter->setMessages([
    Error\EmptyError::class => 'This field is required',
    Error\InvalidEmailError::class => 'Invalid email address',
    Error\Error::class => 'Invalid value', // a base class matches last
]);
$htmlForm = new HtmlForm($form, $formatter);

// Generate inputs
echo $htmlForm->inputText('username');
// <input type="text" name="username" value="" required maxlength="20" minlength="3">

echo $htmlForm->inputEmail('email');
// <input type="email" name="email" value="" required>

echo $htmlForm->inputNumber('age');
// <input type="number" name="age" value="" required max="100" min="18">
```

## Input Types

```php
// Text inputs
$htmlForm->inputText('name');
$htmlForm->inputEmail('email');
$htmlForm->inputPassword('password');
$htmlForm->inputUrl('website');
$htmlForm->inputTel('phone');
$htmlForm->inputNumber('quantity');
$htmlForm->inputDate('birthdate');
$htmlForm->inputHidden('token');
$htmlForm->inputFile('upload');

// Textarea
$htmlForm->textarea('bio');

// Boolean checkbox
$form->agree = new FormItem\BooleanInput();
echo $htmlForm->inputBoolean('agree');
// <input type="checkbox" name="agree" value="1" required>
```

## Select and Options

```php
$form->country = (new FormItem\Select())
    ->setOptions([
        'us' => 'United States',
        'jp' => 'Japan',
        'uk' => 'United Kingdom'
    ])
    ->setValue('jp');

echo $htmlForm->select('country');
// <select name="country" required>
//   <option value="us">United States</option>
//   <option value="jp" selected>Japan</option>
//   <option value="uk">United Kingdom</option>
// </select>

// Multi-select
$form->colors = (new FormItem\MultiSelect())
    ->setOptions(['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'])
    ->setValue(['r', 'b']);

echo $htmlForm->select('colors');
// <select name="colors[]" required multiple>...</select>
```

## Checkboxes and Radios

```php
$form->size = (new FormItem\Select())
    ->setOptions(['s' => 'Small', 'm' => 'Medium', 'l' => 'Large'])
    ->setValue('m');

// Individual checkbox
echo $htmlForm->inputCheckbox('size', 's');
// <input type="checkbox" name="size" value="s">

// All checkboxes
foreach ($htmlForm->inputCheckboxes('size') as $value => $checkbox) {
    echo $checkbox; // Has title attribute with label
}

// Radio buttons
foreach ($htmlForm->inputRadios('size') as $value => $radio) {
    echo $radio;
}
```

## Nested Forms

```php
$form->address = new Form();
$form->address->city = new FormItem\TextInput();
$form->address->postal = new FormItem\TextInput();

echo $htmlForm->inputText('address/city');
// <input type="text" name="address[city]" value="" required>

echo $htmlForm->inputText('address/postal');
// <input type="text" name="address[postal]" value="" required>
```

An item name cannot contain the delimiter, `[` or `]`. Use
`setItemPathDelimiter()` to change the delimiter from `/`.

## Displaying Values

```php
$form->price = (new FormItem\NumberInput())->setValue('1234.56');
$form->created = (new FormItem\DateInput())->setValue('2024-01-15');

// Plain value
echo $htmlForm->value('price');
// 1234.56

// Formatted number
echo $htmlForm->number('price', 2, '.', ',');
// 1,234.56

// Formatted date
echo $htmlForm->date('created', 'F d, Y');
// January 15, 2024

// Custom format
echo $htmlForm->format('price', 'Price: $%s');
// Price: $1234.56
```

## Error Handling

```php
$form->email = (new FormItem\EmailInput())->setValue('invalid-email');
$form->email->validate();

// Display errors
echo $htmlForm->error('email');
// <div>Invalid email address</div>

// One block for several fields
echo $htmlForm->error('email', 'username');

// Check for errors
if ($form->email->hasError()) {
    // ...
}
```

## Form State Attributes

```php
// Optional field
$form->optional = (new FormItem\TextInput())
    ->setRequired(false);

// Disabled field
$form->locked = (new FormItem\TextInput())
    ->setDisabled(true);

// Read-only field
$form->computed = (new FormItem\TextInput())
    ->setReadOnly(true);

echo $htmlForm->inputText('optional');
// <input type="text" name="optional" value="">

echo $htmlForm->inputText('locked');
// <input type="text" name="locked" value="" required disabled>
```

## Bootstrap Integration

### Bootstrap 4

```php
use Coroq\HtmlForm\Integration\Bootstrap4;

$htmlForm = new Bootstrap4($form, $formatter);

echo $htmlForm->inputText('username');
// <input type="text" name="username" value="" required maxlength="20" minlength="3" class="form-control">

echo $htmlForm->select('country');
// <select name="country" required class="form-control">...</select>

// With validation errors
$form->email->validate(); // Fails
echo $htmlForm->inputEmail('email');
// <input type="email" name="email" value="" required class="form-control is-invalid">

echo $htmlForm->error('email');
// <div class="invalid-feedback"><div>Invalid email address</div></div>
```

### Bootstrap 5

```php
use Coroq\HtmlForm\Integration\Bootstrap5;

$htmlForm = new Bootstrap5($form, $formatter);

echo $htmlForm->select('country');
// <select name="country" required class="form-select">...</select>
// Note: Bootstrap 5 uses form-select instead of form-control
```

## Complete Example

```php
// Setup form
$form = new Form();
$form->username = (new FormItem\TextInput())
    ->setMinLength(3)
    ->setMaxLength(20);
$form->email = new FormItem\EmailInput();
$form->password = (new FormItem\TextInput())
    ->setMinLength(8);
$form->country = (new FormItem\Select())
    ->setOptions(['us' => 'USA', 'jp' => 'Japan', 'uk' => 'UK']);
$form->agree = new FormItem\BooleanInput();

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form->setValue($_POST);
    if ($form->validate()) {
        // Process form
        $data = $form->getValue();
    }
}

// Create HTML generator
$formatter = new ErrorMessageFormatter();
$formatter->setMessages([
    Error\EmptyError::class => 'This field is required',
    Error\InvalidEmailError::class => 'Invalid email address',
    Error\Error::class => 'Invalid value', // a base class matches last
]);
$htmlForm = new HtmlForm($form, $formatter);
?>

<form method="post">
    <div>
        <label>Username</label>
        <?= $htmlForm->inputText('username') ?>
        <?= $htmlForm->error('username') ?>
    </div>

    <div>
        <label>Email</label>
        <?= $htmlForm->inputEmail('email') ?>
        <?= $htmlForm->error('email') ?>
    </div>

    <div>
        <label>Password</label>
        <?= $htmlForm->inputPassword('password') ?>
        <?= $htmlForm->error('password') ?>
    </div>

    <div>
        <label>Country</label>
        <?= $htmlForm->select('country') ?>
        <?= $htmlForm->error('country') ?>
    </div>

    <div>
        <?= $htmlForm->inputBoolean('agree') ?>
        <label>I agree to terms</label>
        <?= $htmlForm->error('agree') ?>
    </div>

    <button type="submit">Submit</button>
</form>
```

## License

MIT
