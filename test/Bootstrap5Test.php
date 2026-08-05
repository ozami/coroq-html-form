<?php
declare(strict_types=1);

use Coroq\Html\Html;
use Coroq\HtmlForm\Integration\Bootstrap5;
use Coroq\Form\Form;
use Coroq\Form\FormItem;
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Form\Error;
use PHPUnit\Framework\TestCase;

class Bootstrap5Test extends TestCase {
  private function createHtmlForm(Form $form): Bootstrap5 {
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
    return new Bootstrap5($form, $formatter);
  }

  public function testInputTextHasFormControl(): void {
    $form = new Form();
    $form->name = (new FormItem\TextInput())->setValue("test");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="text" name="name" value="test" required class="form-control">',
      (string)$htmlForm->inputText("name")
    );
  }

  public function testInputNumberHasFormControl(): void {
    $form = new Form();
    $form->age = (new FormItem\IntegerInput())->setValue("25");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="number" name="age" value="25" required class="form-control">',
      (string)$htmlForm->inputNumber("age")
    );
  }

  public function testInputFileHasFormControl(): void {
    $form = new Form();
    $form->upload = new FormItem\FileInput();
    $htmlForm = $this->createHtmlForm($form);
    // Bootstrap 5 uses form-control for file inputs (not form-control-file like BS4)
    $this->assertSame(
      '<input type="file" name="upload" value="" class="form-control">',
      (string)$htmlForm->inputFile("upload")
    );
  }

  public function testTextareaHasFormControl(): void {
    $form = new Form();
    $form->bio = (new FormItem\TextInput())->setMultiline(true)->setValue("Bio");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<textarea name="bio" required class="form-control">Bio</textarea>',
      (string)$htmlForm->textarea("bio")
    );
  }

  public function testSelectHasFormSelect(): void {
    $form = new Form();
    $form->country = (new FormItem\Select())
      ->setOptions(["us" => "USA", "jp" => "Japan"])
      ->setValue("us");
    $htmlForm = $this->createHtmlForm($form);
    // Bootstrap 5 uses form-select for select elements
    $this->assertSame(
      '<select name="country" required class="form-select"><option value="us" selected>USA</option><option value="jp">Japan</option></select>',
      (string)$htmlForm->select("country")
    );
  }

  public function testCheckboxHasFormCheckInput(): void {
    $form = new Form();
    $form->agree = (new FormItem\Select())
      ->setOptions(["yes" => "I agree"])
      ->setValue("yes");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="checkbox" name="agree" value="yes" class="form-check-input" checked>',
      (string)$htmlForm->inputCheckbox("agree", "yes")
    );
  }

  public function testRadioHasFormCheckInput(): void {
    $form = new Form();
    $form->size = (new FormItem\Select())
      ->setOptions(["s" => "Small", "m" => "Medium"])
      ->setValue("m");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="radio" name="size" value="m" required class="form-check-input" checked>',
      (string)$htmlForm->inputRadio("size", "m")
    );
  }

  public function testInputWithErrorHasIsInvalid(): void {
    $form = new Form();
    $form->email = (new FormItem\EmailInput())
      ->setValue("invalid-email");
    $form->email->validate();

    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="email" name="email" value="invalid-email" required class="form-control is-invalid">',
      (string)$htmlForm->inputEmail("email")
    );
  }

  public function testInputWithoutErrorHasNoIsInvalid(): void {
    $form = new Form();
    $form->email = (new FormItem\EmailInput())
      ->setValue("valid@example.com");
    $form->email->validate();

    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="email" name="email" value="valid@example.com" required class="form-control">',
      (string)$htmlForm->inputEmail("email")
    );
  }

  public function testErrorHasInvalidFeedback(): void {
    $form = new Form();
    $form->email = (new FormItem\EmailInput())
      ->setValue("invalid-email");
    $form->email->validate();

    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<div class="invalid-feedback"><div>Invalid email address</div></div>',
      (string)$htmlForm->error("email")
    );
  }

  public function testHiddenInputHasNoClasses(): void {
    $form = new Form();
    $form->token = (new FormItem\TextInput())->setValue("abc123");
    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="hidden" name="token" value="abc123" required>',
      (string)$htmlForm->inputHidden("token")
    );
  }

  public function testMultipleClassesApplied(): void {
    $form = new Form();
    $form->username = (new FormItem\TextInput())
      ->setValue("")
      ->setRequired(true);
    $form->username->validate();

    $htmlForm = $this->createHtmlForm($form);
    $this->assertSame(
      '<input type="text" name="username" value="" required class="form-control is-invalid">',
      (string)$htmlForm->inputText("username")
    );
  }
}
