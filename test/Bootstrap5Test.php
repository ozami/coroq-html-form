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
    $input = $htmlForm->inputText("name");

    $this->assertStringContainsString("form-control", $input->getAttr("class"));
  }

  public function testInputNumberHasFormControl(): void {
    $form = new Form();
    $form->age = (new FormItem\IntegerInput())->setValue("25");
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputNumber("age");

    $this->assertStringContainsString("form-control", $input->getAttr("class"));
  }

  public function testInputFileHasFormControl(): void {
    $form = new Form();
    $form->upload = new FormItem\FileInput();
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputFile("upload");

    // Bootstrap 5 uses form-control for file inputs (not form-control-file like BS4)
    $this->assertStringContainsString("form-control", $input->getAttr("class"));
  }

  public function testTextareaHasFormControl(): void {
    $form = new Form();
    $form->bio = (new FormItem\TextInput())->setMultiline(true)->setValue("Bio");
    $htmlForm = $this->createHtmlForm($form);
    $textarea = $htmlForm->textarea("bio");

    $this->assertStringContainsString("form-control", $textarea->getAttr("class"));
  }

  public function testSelectHasFormSelect(): void {
    $form = new Form();
    $form->country = (new FormItem\Select())
      ->setOptions(["us" => "USA", "jp" => "Japan"])
      ->setValue("us");
    $htmlForm = $this->createHtmlForm($form);
    $select = $htmlForm->select("country");

    // Bootstrap 5 uses form-select for select elements
    $this->assertStringContainsString("form-select", $select->getAttr("class"));
  }

  public function testCheckboxHasFormCheckInput(): void {
    $form = new Form();
    $form->agree = (new FormItem\Select())
      ->setOptions(["yes" => "I agree"])
      ->setValue("yes");
    $htmlForm = $this->createHtmlForm($form);
    $checkbox = $htmlForm->inputCheckbox("agree", "yes");

    $this->assertStringContainsString("form-check-input", $checkbox->getAttr("class"));
  }

  public function testRadioHasFormCheckInput(): void {
    $form = new Form();
    $form->size = (new FormItem\Select())
      ->setOptions(["s" => "Small", "m" => "Medium"])
      ->setValue("m");
    $htmlForm = $this->createHtmlForm($form);
    $radio = $htmlForm->inputRadio("size", "m");

    $this->assertStringContainsString("form-check-input", $radio->getAttr("class"));
  }

  public function testInputWithErrorHasIsInvalid(): void {
    $form = new Form();
    $form->email = (new FormItem\EmailInput())
      ->setValue("invalid-email");
    $form->email->validate();

    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputEmail("email");

    $this->assertStringContainsString("is-invalid", $input->getAttr("class"));
  }

  public function testInputWithoutErrorHasNoIsInvalid(): void {
    $form = new Form();
    $form->email = (new FormItem\EmailInput())
      ->setValue("valid@example.com");
    $form->email->validate();

    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputEmail("email");

    $class = $input->getAttr("class");
    $this->assertStringNotContainsString("is-invalid", $class ?? "");
  }

  public function testErrorHasInvalidFeedback(): void {
    $form = new Form();
    $form->email = (new FormItem\EmailInput())
      ->setValue("invalid-email");
    $form->email->validate();

    $htmlForm = $this->createHtmlForm($form);
    $error = $htmlForm->error("email");

    $this->assertStringContainsString("invalid-feedback", $error->getAttr("class"));
    $this->assertEquals("div", $error->getTag());
  }

  public function testHiddenInputHasNoClasses(): void {
    $form = new Form();
    $form->token = (new FormItem\TextInput())->setValue("abc123");
    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputHidden("token");

    $class = $input->getAttr("class");
    $this->assertNull($class);
  }

  public function testMultipleClassesApplied(): void {
    $form = new Form();
    $form->username = (new FormItem\TextInput())
      ->setValue("")
      ->setRequired(true);
    $form->username->validate();

    $htmlForm = $this->createHtmlForm($form);
    $input = $htmlForm->inputText("username");

    $class = $input->getAttr("class");
    $this->assertStringContainsString("form-control", $class);
    $this->assertStringContainsString("is-invalid", $class);
  }
}
