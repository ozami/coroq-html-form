<?php
declare(strict_types=1);

use Coroq\Html\Html;
use Coroq\HtmlForm\HtmlForm;
use Coroq\Form\Form;
use Coroq\Form\FormItem;
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Form\BasicErrorMessages;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase {
  private function createHtmlForm(Form $form): HtmlForm {
    $formatter = new ErrorMessageFormatter();
    $formatter->setMessages(BasicErrorMessages::get());
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
}
