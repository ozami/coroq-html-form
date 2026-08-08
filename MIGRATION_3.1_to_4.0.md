# Migration Guide: coroq/html-form 3.1.0 to 4.0.0

4.0 narrows `HtmlForm` to what it is for. It generates elements; every public
method returns `Html` or an array of `Html`, apart from `makeName()` and the
delimiter pair. Reading values out of the form is the form's job, and escaping
is `h()`'s.

## Requirements

|              | 3.1.0        | 4.0.0             |
| ------------ | ------------ | ----------------- |
| PHP          | 8.0+         | 8.0+              |
| `coroq/form` | ^3.0         | ^3.0              |
| `coroq/html` | ^0.5         | ^1.0 \|\| ^2.0    |

The constructor is unchanged.

## Breaking Changes

### 1. The value display methods are gone

`value()`, `format()`, `number()`, `date()` and `selected()` are removed. Each
read a value out of the form and wrapped it in `Html`. The value comes from the
form item now, and `h()` escapes it - the same `h()` you already write around
everything else.

**Escape the replacement.** The old methods returned auto-escaped `Html`. A
replacement without `h()` puts unescaped submitted data on the page.

```php
use function Coroq\Html\h;
```

#### `value()`

```php
// Before
echo $htmlForm->value('price');

// After
echo h($form->price->getValue());
```

#### `date()`

`getParsedValue()` returns the value in its own type - `?DateTimeImmutable` on
a `DateInput`, `?float` on a `NumberInput`, `?int` on an `IntegerInput`. No
reparsing, and `h(null)` renders nothing:

```php
// Before
echo $htmlForm->date('created', 'F d, Y');

// After
echo h($form->created->getParsedValue()?->format('F d, Y'));
```

An item that is not a `DateInput` has no `getParsedValue()` of its own, so a
date held in a `TextInput` still needs `strtotime()`. Modelling it as a
`DateInput` is the better fix.

#### `number()` and `format()`

Both rendered nothing for an empty value. `getParsedValue()` returns `null`
there, so guard the call - otherwise an empty price formats as `0.00` or
`Price: $`:

```php
// Before
echo $htmlForm->number('price', 2, '.', ',');
echo $htmlForm->format('price', 'Price: $%s');

// After
$price = $form->price->getParsedValue();
echo h($price === null ? '' : number_format($price, 2, '.', ','));
echo h($price === null ? '' : sprintf('Price: $%s', $price));
```

#### `selected()`

It returned `Html` for a select and an array of `Html` for a multi-select:

```php
// Before
echo $htmlForm->selected('country');
echo implode(', ', $htmlForm->selected('colors'));

// After
echo h($form->country->getSelectedLabel());
echo h(implode(', ', $form->colors->getSelectedLabel()));
```

`MultiSelect::getSelectedLabel()` is singular but returns an array of labels.
That is coroq/form's naming, not a typo here.

### 2. `getForm()` is gone

Whoever constructs an `HtmlForm` holds the form already, so pass it along
instead of reaching back through:

```php
// Before
$htmlForm->getForm()->email->hasError();

// After
$form->email->hasError();
```

This supersedes the 2.1-to-3.0 guide, which told you to replace the removed
`__call()` proxy with `$htmlForm->getForm()->methodName()`. Call the form
directly.

### 3. Item paths are strings

An array path could only express a name containing `/`, and in exchange every
signature was wider and a "not found" error printed `Array`:

```php
// Before
$htmlForm->inputText(['address', 'city']);

// After
$htmlForm->inputText('address/city');
```

If a name has to contain `/`, change the delimiter instead:

```php
$htmlForm->setItemPathDelimiter('.');
$htmlForm->inputText('address.city');
```

`getItemPathDelimiter()` and `setItemPathDelimiter()` are new in 4.0. A name
cannot contain the delimiter, `[` or `]`.

### 4. `error()` is variadic

```php
// Before
$htmlForm->error(['email', 'username']);

// After
$htmlForm->error('email', 'username');
$htmlForm->error(...$paths);   // when you have an array
```

### 5. `inputCheckable()` and `inputCheckables()` are protected

They hold the shared logic behind `inputCheckbox()`, `inputRadio()`,
`inputCheckboxes()` and `inputRadios()`. Call those:

```php
// Before
$htmlForm->inputCheckable('size', 'checkbox', 's');

// After
$htmlForm->inputCheckbox('size', 's');
```

A subclass can still override `inputCheckable()` to change checkboxes and
radios together.

### 6. `getGeneralAttributesFromInput()` is `getGeneralAttributes()`

Only affects subclasses. It stays protected; the parameter is named `$item`.

```php
// Before
protected function getGeneralAttributesFromInput(FormItemInterface $input): array

// After
protected function getGeneralAttributes(FormItemInterface $item): array
```

`Bootstrap4` and `Bootstrap5` are otherwise unchanged apart from following the
signature changes above - `input()`, `textarea()`, `select()` and
`addValidationClass()` take a `string` path, and `error()` is variadic.

### 7. Rendered output

Two elements render differently. Nothing else does.

**An unbounded numeric item no longer carries `min` and `max`.** The old guard
compared against `INF`, which nothing returns, so every unbounded
`IntegerInput` shipped the integer limits:

```html
<!-- Before -->
<input type="number" name="count" value="" required max="9223372036854775807" min="-9223372036854775808">
<!-- After -->
<input type="number" name="count" value="" required>
```

**`inputCheckbox()` no longer emits `required`.** `required` on one box of a
group demands that every box be checked. `inputCheckboxes()` already cleared
it, so rendering the boxes one at a time to control layout used to behave
differently from rendering them together:

```html
<!-- Before -->
<input type="checkbox" name="size" value="s" required>
<!-- After -->
<input type="checkbox" name="size" value="s">
```

`inputBoolean()` is a single checkbox that genuinely must be checked, so it
keeps `required`.

Attribute order did not change, and neither did `value=""` on an empty input.
The 3.1.0 README claimed otherwise in its output comments; those comments were
wrong when they were written.

### 8. coroq/html 0.5 to 1.0 or 2.0

`html-form` itself needs no change from you here, but code of yours that uses
`coroq/html` directly does. 1.0 removed every tag helper, `when()`, `apply()`,
`each()`, `options()`, `scriptData()`, `externalLink()`, `time()` and `p()`,
and made `Html` and `NoEscape` final. 2.0 removed `getTag()`, `getAttr()`,
`getAttrs()` and `getChildren()` - nothing leaves an `Html` but rendered
output. See that package's CHANGELOG.

You can move to 4.0 on `coroq/html` 1.x and upgrade to 2.x separately; the
constraint allows either.

## Checklist

1. Update the constraint to `"coroq/html-form": "^4.0"` and run `composer update`.
2. Replace `value()`, `format()`, `number()`, `date()` and `selected()` calls,
   wrapping each replacement in `h()`.
3. Replace `getForm()` with the form you already hold.
4. Convert array item paths to delimited strings.
5. Spread the array argument to `error()`.
6. Rename `getGeneralAttributesFromInput()` in any subclass.
7. Update tests that pin exact HTML for unbounded numeric inputs or for
   `inputCheckbox()`.
