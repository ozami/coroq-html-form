<?php
declare(strict_types=1);

use Coroq\Html\Html;
use Coroq\HtmlForm\HtmlForm;
use Coroq\Form\Form;
use Coroq\Form\FormItem;
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Form\Error;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase {
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
    $this->assertEquals(
      Html::escape("A"),
      $htmlForm->value("a")
    );
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
    $result = $htmlForm->inputText("locked");
    $this->assertTrue($result->getAttr("disabled"));
  }

  public function testInputWithReadonly(): void {
    $form = new Form();
    $form->readonly = (new FormItem\TextInput())
      ->setValue("readonly")
      ->setReadOnly(true);
    $htmlForm = $this->createHtmlForm($form);
    $result = $htmlForm->inputText("readonly");
    $this->assertTrue($result->getAttr("readonly"));
  }

  public function testInputOptional(): void {
    $form = new Form();
    $form->optional = (new FormItem\TextInput())
      ->setValue("optional")
      ->setRequired(false);
    $htmlForm = $this->createHtmlForm($form);
    $result = $htmlForm->inputText("optional");
    $this->assertNull($result->getAttr("required"));
  }

  // Radio buttons
  public function testInputRadios(): void {
    $form = new Form();
    $options = ["s" => "Small", "m" => "Medium", "l" => "Large"];
    $form->size = (new FormItem\Select())
      ->setOptions($options)
      ->setValue("m");
    $htmlForm = $this->createHtmlForm($form);
    $radios = $htmlForm->inputRadios("size");

    $this->assertCount(3, $radios);
    $this->assertEquals("s", $radios["s"]->getAttr("value"));
    $this->assertEquals("m", $radios["m"]->getAttr("value"));
    $this->assertEquals("l", $radios["l"]->getAttr("value"));
    $this->assertTrue($radios["m"]->getAttr("checked"));
    $this->assertNull($radios["s"]->getAttr("checked"));
  }

  // Multi-select
  public function testMultiSelect(): void {
    $form = new Form();
    $form->colors = (new FormItem\MultiSelect())
      ->setOptions(["r" => "Red", "g" => "Green", "b" => "Blue"])
      ->setValue(["r", "b"]);
    $htmlForm = $this->createHtmlForm($form);
    $select = $htmlForm->select("colors");

    $this->assertEquals("colors[]", $select->getAttr("name"));
    $this->assertTrue($select->getAttr("multiple"));
  }

  // Error display
  public function testError(): void {
    $form = new Form();
    $form->email = (new FormItem\EmailInput())
      ->setValue("invalid-email");
    $form->email->validate();

    $htmlForm = $this->createHtmlForm($form);
    $error = $htmlForm->error("email");

    $this->assertInstanceOf(Html::class, $error);
    $children = $error->getChildren();
    $this->assertNotEmpty($children);
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
    $error = $htmlForm->error(["email", "name"]);

    $this->assertInstanceOf(Html::class, $error);
    $children = $error->getChildren();
    $this->assertGreaterThanOrEqual(2, count($children));
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
    $input = $htmlForm->inputEmail("user/email");

    $this->assertEquals("user[email]", $input->getAttr("name"));
    $this->assertEquals("test@example.com", $input->getAttr("value"));
  }

  // makeName tests
  public function testMakeName(): void {
    $form = new Form();
    $htmlForm = $this->createHtmlForm($form);

    $this->assertEquals("name", $htmlForm->makeName("name"));
    $this->assertEquals("user[email]", $htmlForm->makeName("user/email"));
    $this->assertEquals("order[items][0]", $htmlForm->makeName("order/items/0"));
  }

  public function testMakeNameWithArray(): void {
    $form = new Form();
    $htmlForm = $this->createHtmlForm($form);

    $this->assertEquals("user[address][city]", $htmlForm->makeName(["user", "address", "city"]));
  }

  // New FormItem types in 3.0.0-alpha2
  public function testInputUrl(): void {
    $form = new Form();
    $form->website = (new FormItem\UrlInput())->setValue("https://example.com");
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputUrl("website");

    $this->assertEquals("url", $input->getAttr("type"));
    $this->assertEquals("https://example.com", $input->getAttr("value"));
  }

  public function testInputBoolean(): void {
    $form = new Form();
    $form->agree = (new FormItem\BooleanInput())->setValue("1");
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputBoolean("agree");

    $this->assertEquals("checkbox", $input->getAttr("type"));
    $this->assertEquals("1", $input->getAttr("value"));
    $this->assertTrue($input->getAttr("checked"));
  }

  public function testInputBooleanUnchecked(): void {
    $form = new Form();
    $form->agree = (new FormItem\BooleanInput())->setValue("");
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputBoolean("agree");

    $this->assertEquals("checkbox", $input->getAttr("type"));
    $this->assertNull($input->getAttr("checked"));
  }

  public function testInputBooleanWithCustomValue(): void {
    $form = new Form();
    $form->agree = (new FormItem\BooleanInput())->setValue("yes");
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputBoolean("agree", "yes");

    $this->assertEquals("yes", $input->getAttr("value"));
    $this->assertTrue($input->getAttr("checked"));
  }

  // Additional input type tests
  public function testInputTel(): void {
    $form = new Form();
    $form->phone = (new FormItem\TextInput())->setValue("+1-555-1234");
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputTel("phone");

    $this->assertEquals("tel", $input->getAttr("type"));
    $this->assertEquals("+1-555-1234", $input->getAttr("value"));
  }

  public function testInputDate(): void {
    $form = new Form();
    $form->birthday = (new FormItem\DateInput())->setValue("2024-01-15");
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputDate("birthday");

    $this->assertEquals("date", $input->getAttr("type"));
    $this->assertEquals("2024-01-15", $input->getAttr("value"));
  }

  public function testInputHidden(): void {
    $form = new Form();
    $form->token = (new FormItem\TextInput())->setValue("secret123");
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputHidden("token");

    $this->assertEquals("hidden", $input->getAttr("type"));
    $this->assertEquals("secret123", $input->getAttr("value"));
  }

  public function testInputPassword(): void {
    $form = new Form();
    $form->pass = (new FormItem\TextInput())->setValue("mypassword");
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputPassword("pass");

    $this->assertEquals("password", $input->getAttr("type"));
    $this->assertEquals("mypassword", $input->getAttr("value"));
  }

  public function testInputFile(): void {
    $form = new Form();
    $form->upload = (new FormItem\TextInput())->setValue("");
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputFile("upload");

    $this->assertEquals("file", $input->getAttr("type"));
    $this->assertEquals("upload", $input->getAttr("name"));
  }

  public function testInputEmail(): void {
    $form = new Form();
    $form->email = (new FormItem\EmailInput())->setValue("test@example.com");
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputEmail("email");

    $this->assertEquals("email", $input->getAttr("type"));
    $this->assertEquals("test@example.com", $input->getAttr("value"));
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

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Invaild date time string");
    $htmlForm->date("created", "Y-m-d");
  }

  // Test error with no errors
  public function testErrorWithNoErrors(): void {
    $form = new Form();
    $form->name = (new FormItem\TextInput())->setValue("John");
    $htmlForm = $this->createHtmlForm($form);
    $error = $htmlForm->error("name");

    $this->assertInstanceOf(Html::class, $error);
    $this->assertEmpty($error->getChildren());
  }

  // Test nested path with array notation
  public function testNestedPathWithArrayNotation(): void {
    $form = new Form();
    $form->user = new Form();
    $form->user->profile = new Form();
    $form->user->profile->name = (new FormItem\TextInput())->setValue("Alice");

    $htmlForm = $this->createHtmlForm($form);
    $this->assertEquals(
      (new Html())->append("Alice"),
      $htmlForm->value(["user", "profile", "name"])
    );
  }

  // Test inputCheckbox individual method
  public function testInputCheckbox(): void {
    $form = new Form();
    $form->color = (new FormItem\Select())
      ->setOptions(["r" => "Red", "g" => "Green"])
      ->setValue("r");
    $htmlForm = $this->createHtmlForm($form);
    $checkbox = $htmlForm->inputCheckbox("color", "r");

    $this->assertEquals("checkbox", $checkbox->getAttr("type"));
    $this->assertEquals("r", $checkbox->getAttr("value"));
    $this->assertTrue($checkbox->getAttr("checked"));
  }

  // Test inputRadio individual method
  public function testInputRadio(): void {
    $form = new Form();
    $form->size = (new FormItem\Select())
      ->setOptions(["s" => "Small", "m" => "Medium"])
      ->setValue("m");
    $htmlForm = $this->createHtmlForm($form);
    $radio = $htmlForm->inputRadio("size", "m");

    $this->assertEquals("radio", $radio->getAttr("type"));
    $this->assertEquals("m", $radio->getAttr("value"));
    $this->assertTrue($radio->getAttr("checked"));
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
    $this->expectExceptionMessage("Cannot traverse path - current item is not a FormInterface");
    $htmlForm->value("name/invalid");
  }
}
