<?php
declare(strict_types=1);

use Coroq\Html\Html;
use Coroq\HtmlForm\HtmlForm;
use Coroq\Form\Form;
use Coroq\Form\FormItem;
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Form\Error;
use PHPUnit\Framework\TestCase;

class HtmlFormTest extends TestCase {
  private function createHtmlForm(Form $form): HtmlForm {
    $formatter = new ErrorMessageFormatter();
    $formatter->setMessages([
      Error\EmptyError::class => 'This field is required',
      Error\InvalidError::class => 'Invalid value',
      Error\InvalidEmailError::class => 'Invalid email address',
      Error\TooShortError::class => 'Too short',
      Error\TooLongError::class => 'Too long',
      Error\TooSmallError::class => 'Too small',
      Error\TooLargeError::class => 'Too large',
      Error\NotIntegerError::class => 'Must be an integer',
    ]);
    return new HtmlForm($form, $formatter);
  }

  public function testValue(): void {
    $form = new Form();
    $form->a = (new FormItem\TextInput())->setValue("A");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame("A", (string)$htmlForm->value("a"));
  }

  public function testInputUsesAValueThatCanBeAString(): void {
    $money = new class {
      public function __toString(): string {
        return "JPY 1,000";
      }
    };
    $form = new Form();
    $form->s = (new FormItem\Input())->setValue($money);
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="text" name="s" value="JPY 1,000" required>',
      (string)$htmlForm->inputText("s")
    );
  }

  public function testInputSetsNoValueWhenTheValueHasNoStringForm(): void {
    $form = new Form();
    $form->o = (new FormItem\Input())->setValue(new \stdClass());
    $form->a = (new FormItem\MultiSelect())->setOptions(["a" => "A"])->setValue(["a"]);
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame('<input type="text" name="o" required>', (string)$htmlForm->inputText("o"));
    $this->assertSame('<input type="text" name="a" required>', (string)$htmlForm->inputText("a"));
  }

  public function testInputText(): void {
    $form = new Form();
    $form->x = (new FormItem\TextInput())
      ->setValue("X");
    $htmlForm = $this->createHtmlForm($form);
    $h = (new Html())
      ->tag("input")
      ->attr("type", "text")
      ->attr("name", "x")
      ->attr("value", "X")
      ->attr("required", true);
    $this->assertEquals($h, $htmlForm->inputText("x"));
  }

  public function testTextarea(): void {
    $form = new Form();
    $form->x = (new FormItem\TextInput())
      ->setMultiline(true)
      ->setValue("X\nY\nZ");
    $htmlForm = $this->createHtmlForm($form);
    $h = (new Html())
      ->tag("textarea")
      ->attr("name", "x")
      ->attr("required", true)
      ->append("X\nY\nZ");
    $this->assertEquals($h, $htmlForm->textarea("x"));
  }

  public function testInputCheckboxes(): void {
    $form = new Form();
    $options = ["a" => "A", "b" => "B", "c" => "C"];
    $form->x = (new FormItem\Select())
      ->setOptions($options)
      ->setValue("b");
    $htmlForm = $this->createHtmlForm($form);
    $expected = [];
    foreach ($options as $value => $label) {
      $h = (new Html())
        ->tag("input")
        ->attr("type", "checkbox")
        ->attr("name", "x")
        ->attr("value", $value)
        ->attr("required", false)
        ->attr("title", $label);
      if ($value == "b") {
        $h->attr("checked", true);
      }
      $expected[$value] = $h;
    }
    $this->assertEquals(
      $expected,
      $htmlForm->inputCheckboxes("x")
    );
  }

  public function testInputCheckboxesForMultiSelect(): void {
    $form = new Form();
    $options = ["a" => "A", "b" => "B", "c" => "C"];
    $form->x = (new FormItem\MultiSelect())
      ->setOptions($options)
      ->setValue(["a", "b"]);
    $htmlForm = $this->createHtmlForm($form);
    $expected = [];
    foreach ($options as $value => $label) {
      $h = (new Html())
        ->tag("input")
        ->attr("type", "checkbox")
        ->attr("name", "x[]")
        ->attr("value", $value)
        ->attr("required", false)
        ->attr("title", $label);
      if ($value == "a" || $value == "b") {
        $h->attr("checked", true);
      }
      $expected[$value] = $h;
    }
    $this->assertEquals(
      $expected,
      $htmlForm->inputCheckboxes("x")
    );
  }

  public function testOneCheckboxOfAGroupIsNotRequired(): void {
    $form = new Form();
    $form->size = (new FormItem\Select())
      ->setOptions(["s" => "Small", "m" => "Medium"])
      ->setValue("m");
    $form->agree = new FormItem\BooleanInput();
    $htmlForm = $this->createHtmlForm($form);

    // required on one box of a group would demand that every box be checked
    $this->assertSame(
      '<input type="checkbox" name="size" value="s">',
      (string)$htmlForm->inputCheckbox("size", "s")
    );
    // a radio group is required as a whole, and a lone boolean checkbox is its own group
    $this->assertSame(
      '<input type="radio" name="size" value="s" required>',
      (string)$htmlForm->inputRadio("size", "s")
    );
    $this->assertSame(
      '<input type="checkbox" name="agree" value="1" required>',
      (string)$htmlForm->inputBoolean("agree")
    );
  }

  public function testSelect(): void {
    $form = new Form();
    $form->x = (new FormItem\Select())
      ->setOptions(["a" => "A", "b" => "B", "c" => "C"])
      ->setValue("b");
    $htmlForm = $this->createHtmlForm($form);
    $h = (new Html())
      ->tag("select")
      ->attr("name", "x")
      ->attr("required", true)
      ->children(array_map(function($value) {
        $h = (new Html())
          ->tag("option")
          ->attr("value", $value)
          ->append(strtoupper($value));
        if ($value == "b") {
          $h->attr("selected", true);
        }
        return $h;
      }, ["a", "b", "c"]));
    $this->assertEquals($h, $htmlForm->select("x"));
  }

  // Value display methods
  public function testFormat(): void {
    $form = new Form();
    $form->price = (new FormItem\NumberInput())->setValue("99.99");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertEquals(
      (new Html())->append("Price: $99.99"),
      $htmlForm->format("price", "Price: $%s")
    );
  }

  public function testNumber(): void {
    $form = new Form();
    $form->amount = (new FormItem\NumberInput())->setValue("1234.5678");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertEquals(
      (new Html())->append("1,234.57"),
      $htmlForm->number("amount", 2, ".", ",")
    );
  }

  public function testDate(): void {
    $form = new Form();
    $form->created = (new FormItem\DateInput())->setValue("2024-01-15");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertEquals(
      (new Html())->append("January 15, 2024"),
      $htmlForm->date("created", "F d, Y")
    );
  }

  // Validation attributes tests
  public function testInputTextWithLengthConstraints(): void {
    $form = new Form();
    $form->username = (new FormItem\TextInput())
      ->setValue("john")
      ->setMinLength(3)
      ->setMaxLength(20);
    $htmlForm = $this->createHtmlForm($form);
    $h = (new Html())
      ->tag("input")
      ->attr("type", "text")
      ->attr("name", "username")
      ->attr("value", "john")
      ->attr("required", true)
      ->attr("minlength", 3)
      ->attr("maxlength", 20);
    $this->assertEquals($h, $htmlForm->inputText("username"));
  }

  public function testAnUnboundedNumericItemHasNoMinOrMax(): void {
    $form = new Form();
    $form->age = new FormItem\IntegerInput();
    $form->price = new FormItem\NumberInput();
    $htmlForm = $this->createHtmlForm($form);

    // an unbounded item reports the integer limit or null, neither of which is a bound
    $this->assertSame(
      '<input type="number" name="age" value="" required>',
      (string)$htmlForm->inputNumber("age")
    );
    $this->assertSame(
      '<input type="number" name="price" value="" required>',
      (string)$htmlForm->inputNumber("price")
    );
  }

  public function testInputNumberWithRange(): void {
    $form = new Form();
    $form->age = (new FormItem\IntegerInput())
      ->setValue("25")
      ->setMin(18)
      ->setMax(100);
    $htmlForm = $this->createHtmlForm($form);
    $h = (new Html())
      ->tag("input")
      ->attr("type", "number")
      ->attr("name", "age")
      ->attr("value", "25")
      ->attr("required", true)
      ->attr("min", 18)
      ->attr("max", 100);
    $this->assertEquals($h, $htmlForm->inputNumber("age"));
  }

  // Disabled, readonly, required attributes
  public function testInputWithDisabled(): void {
    $form = new Form();
    $form->locked = (new FormItem\TextInput())
      ->setValue("locked")
      ->setDisabled(true);
    $htmlForm = $this->createHtmlForm($form);
    // coroq/form 3.0.1 made a disabled item report its empty value, so the
    // value attribute is empty however the item was set.
    $this->assertSame(
      '<input type="text" name="locked" value="" required disabled>',
      (string)$htmlForm->inputText("locked")
    );
  }

  public function testInputWithReadonly(): void {
    $form = new Form();
    $form->readonly = (new FormItem\TextInput())
      ->setValue("readonly")
      ->setReadOnly(true);
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="text" name="readonly" value="readonly" required readonly>',
      (string)$htmlForm->inputText("readonly")
    );
  }

  public function testInputOptional(): void {
    $form = new Form();
    $form->optional = (new FormItem\TextInput())
      ->setValue("optional")
      ->setRequired(false);
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="text" name="optional" value="optional">',
      (string)$htmlForm->inputText("optional")
    );
  }

  // Radio buttons
  public function testInputRadios(): void {
    $form = new Form();
    $options = ["s" => "Small", "m" => "Medium", "l" => "Large"];
    $form->size = (new FormItem\Select())
      ->setOptions($options)
      ->setValue("m");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      [
        "s" => '<input type="radio" name="size" value="s" required title="Small">',
        "m" => '<input type="radio" name="size" value="m" required checked title="Medium">',
        "l" => '<input type="radio" name="size" value="l" required title="Large">',
      ],
      array_map("strval", $htmlForm->inputRadios("size"))
    );
  }

  // Multi-select
  public function testMultiSelect(): void {
    $form = new Form();
    $form->colors = (new FormItem\MultiSelect())
      ->setOptions(["r" => "Red", "g" => "Green", "b" => "Blue"])
      ->setValue(["r", "b"]);
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<select name="colors[]" required multiple>'
        . '<option value="r" selected>Red</option>'
        . '<option value="g">Green</option>'
        . '<option value="b" selected>Blue</option>'
        . '</select>',
      (string)$htmlForm->select("colors")
    );
  }

  // Error display
  public function testError(): void {
    $form = new Form();
    $form->email = (new FormItem\EmailInput())
      ->setValue("invalid-email");
    $form->email->validate();

    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      "<div>Invalid email address</div>",
      (string)$htmlForm->error("email")
    );
  }

  public function testErrorWithMultiplePaths(): void {
    $form = new Form();
    $form->email = (new FormItem\EmailInput())
      ->setValue("invalid-email");
    $form->name = (new FormItem\TextInput())
      ->setValue("")
      ->setRequired(true);

    $form->email->validate();
    $form->name->validate();

    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      "<div>Invalid email address</div><div>This field is required</div>",
      (string)$htmlForm->error("email", "name")
    );
  }

  // Nested form paths
  public function testNestedFormPath(): void {
    $form = new Form();
    $form->address = new Form();
    $form->address->city = (new FormItem\TextInput())->setValue("Tokyo");

    $htmlForm = $this->createHtmlForm($form);
    $this->assertEquals(
      (new Html())->append("Tokyo"),
      $htmlForm->value("address/city")
    );
  }

  public function testNestedFormInputName(): void {
    $form = new Form();
    $form->user = new Form();
    $form->user->email = (new FormItem\EmailInput())->setValue("test@example.com");

    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="email" name="user[email]" value="test@example.com" required>',
      (string)$htmlForm->inputEmail("user/email")
    );
  }

  // makeName tests
  public function testMakeName(): void {
    $form = new Form();
    $htmlForm = $this->createHtmlForm($form);

    $this->assertEquals("name", $htmlForm->makeName("name"));
    $this->assertEquals("user[email]", $htmlForm->makeName("user/email"));
    $this->assertEquals("order[items][0]", $htmlForm->makeName("order/items/0"));
  }

  public function testItemPathDelimiterIsNotHardCoded(): void {
    $form = new Form();
    $form->user = new Form();
    $form->user->email = (new FormItem\EmailInput())->setValue("test@example.com");

    $htmlForm = $this->createHtmlForm($form);
    $htmlForm->setItemPathDelimiter(".");

    $this->assertEquals(".", $htmlForm->getItemPathDelimiter());
    $this->assertEquals("user[email]", $htmlForm->makeName("user.email"));
    $this->assertEquals(
      (new Html())->append("test@example.com"),
      $htmlForm->value("user.email")
    );
  }

  public function testEmptyItemPathDelimiterIsRejected(): void {
    $htmlForm = $this->createHtmlForm(new Form());

    $this->expectException(InvalidArgumentException::class);
    $htmlForm->setItemPathDelimiter("");
  }

  // New FormItem types in 3.0.0-alpha2
  public function testInputUrl(): void {
    $form = new Form();
    $form->website = (new FormItem\UrlInput())->setValue("https://example.com");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="url" name="website" value="https://example.com" required>',
      (string)$htmlForm->inputUrl("website")
    );
  }

  public function testInputBoolean(): void {
    $form = new Form();
    $form->agree = (new FormItem\BooleanInput())->setValue("1");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="checkbox" name="agree" value="1" required checked>',
      (string)$htmlForm->inputBoolean("agree")
    );
  }

  public function testInputBooleanUnchecked(): void {
    $form = new Form();
    $form->agree = (new FormItem\BooleanInput())->setValue("");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="checkbox" name="agree" value="1" required>',
      (string)$htmlForm->inputBoolean("agree")
    );
  }

  public function testInputBooleanWithCustomValue(): void {
    $form = new Form();
    $form->agree = (new FormItem\BooleanInput())->setValue("yes");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="checkbox" name="agree" value="yes" required checked>',
      (string)$htmlForm->inputBoolean("agree", "yes")
    );
  }

  // Additional input type tests
  public function testInputTel(): void {
    $form = new Form();
    $form->phone = (new FormItem\TextInput())->setValue("+1-555-1234");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="tel" name="phone" value="+1-555-1234" required>',
      (string)$htmlForm->inputTel("phone")
    );
  }

  public function testInputDate(): void {
    $form = new Form();
    $form->birthday = (new FormItem\DateInput())->setValue("2024-01-15");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="date" name="birthday" value="2024-01-15" required>',
      (string)$htmlForm->inputDate("birthday")
    );
  }

  public function testInputHidden(): void {
    $form = new Form();
    $form->token = (new FormItem\TextInput())->setValue("secret123");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="hidden" name="token" value="secret123" required>',
      (string)$htmlForm->inputHidden("token")
    );
  }

  public function testInputPassword(): void {
    $form = new Form();
    $form->pass = (new FormItem\TextInput())->setValue("mypassword");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="password" name="pass" value="mypassword" required>',
      (string)$htmlForm->inputPassword("pass")
    );
  }

  public function testInputFile(): void {
    $form = new Form();
    $form->upload = (new FormItem\TextInput())->setValue("");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="file" name="upload" value="" required>',
      (string)$htmlForm->inputFile("upload")
    );
  }

  public function testInputEmail(): void {
    $form = new Form();
    $form->email = (new FormItem\EmailInput())->setValue("test@example.com");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="email" name="email" value="test@example.com" required>',
      (string)$htmlForm->inputEmail("email")
    );
  }

  // Test getForm method
  public function testGetForm(): void {
    $form = new Form();
    $htmlForm = $this->createHtmlForm($form);

    $this->assertSame($form, $htmlForm->getForm());
  }

  // Test selected() with array values
  public function testSelectedWithMultipleValues(): void {
    $form = new Form();
    $form->colors = (new FormItem\MultiSelect())
      ->setOptions(["r" => "Red", "g" => "Green", "b" => "Blue"])
      ->setValue(["r", "b"]);
    $htmlForm = $this->createHtmlForm($form);
    $selected = $htmlForm->selected("colors");

    $this->assertIsArray($selected);
    $this->assertCount(2, $selected);
    $this->assertEquals((new Html())->append("Red"), $selected[0]);
    $this->assertEquals((new Html())->append("Blue"), $selected[1]);
  }

  public function testSelectedWithSingleValue(): void {
    $form = new Form();
    $form->size = (new FormItem\Select())
      ->setOptions(["s" => "Small", "m" => "Medium", "l" => "Large"])
      ->setValue("m");
    $htmlForm = $this->createHtmlForm($form);
    $selected = $htmlForm->selected("size");

    $this->assertInstanceOf(Html::class, $selected);
    $this->assertEquals((new Html())->append("Medium"), $selected);
  }

  // Test format with empty value
  public function testFormatWithEmptyValue(): void {
    $form = new Form();
    $form->price = (new FormItem\NumberInput())->setValue("");
    $htmlForm = $this->createHtmlForm($form);

    $this->assertEquals(new Html(), $htmlForm->format("price", "Price: $%s"));
  }

  // Test number with empty value
  public function testNumberWithEmptyValue(): void {
    $form = new Form();
    $form->amount = (new FormItem\NumberInput())->setValue("");
    $htmlForm = $this->createHtmlForm($form);

    $this->assertEquals(new Html(), $htmlForm->number("amount", 2));
  }

  // Test date with empty value
  public function testDateWithEmptyValue(): void {
    $form = new Form();
    $form->created = (new FormItem\DateInput())->setValue("");
    $htmlForm = $this->createHtmlForm($form);

    $this->assertEquals(new Html(), $htmlForm->date("created", "Y-m-d"));
  }

  // Test date with invalid value
  public function testDateWithInvalidValue(): void {
    $form = new Form();
    $form->created = (new FormItem\DateInput())->setValue("invalid-date");
    $htmlForm = $this->createHtmlForm($form);

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage("Invaild date time string");
    $htmlForm->date("created", "Y-m-d");
  }

  // Test error with no errors
  public function testErrorWithNoErrors(): void {
    $form = new Form();
    $form->name = (new FormItem\TextInput())->setValue("John");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame("", (string)$htmlForm->error("name"));
  }

  // Test nested path with array notation
  public function testDeeplyNestedPath(): void {
    $form = new Form();
    $form->user = new Form();
    $form->user->profile = new Form();
    $form->user->profile->name = (new FormItem\TextInput())->setValue("Alice");

    $htmlForm = $this->createHtmlForm($form);
    $this->assertEquals(
      (new Html())->append("Alice"),
      $htmlForm->value("user/profile/name")
    );
  }

  // Test inputCheckbox individual method
  public function testInputCheckbox(): void {
    $form = new Form();
    $form->color = (new FormItem\Select())
      ->setOptions(["r" => "Red", "g" => "Green"])
      ->setValue("r");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="checkbox" name="color" value="r" checked>',
      (string)$htmlForm->inputCheckbox("color", "r")
    );
  }

  // Test inputRadio individual method
  public function testInputRadio(): void {
    $form = new Form();
    $form->size = (new FormItem\Select())
      ->setOptions(["s" => "Small", "m" => "Medium"])
      ->setValue("m");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="radio" name="size" value="m" required checked>',
      (string)$htmlForm->inputRadio("size", "m")
    );
  }

  // Test getItemIn error conditions
  public function testGetItemInWithInvalidPath(): void {
    $form = new Form();
    $form->name = (new FormItem\TextInput())->setValue("Test");
    $htmlForm = $this->createHtmlForm($form);

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage("Item 'nonexistent' not found in form");
    $htmlForm->value("nonexistent");
  }

  public function testGetItemInCannotTraverseNonFormInterface(): void {
    $form = new Form();
    // Create a TextInput, which is a FormItemInterface but not a FormInterface
    $form->name = (new FormItem\TextInput())->setValue("Test");
    $htmlForm = $this->createHtmlForm($form);

    // Try to traverse into a TextInput (which is not a FormInterface, so we can't traverse deeper)
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage("Item 'name/invalid' not found in form");
    $htmlForm->value("name/invalid");
  }
}
