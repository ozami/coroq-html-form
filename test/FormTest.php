<?php
use Coroq\Html\Html;
use Coroq\HtmlForm\HtmlForm;
use Coroq\Form\Form;
use Coroq\Form\Input;
use Coroq\Form\Input\Select;
use Coroq\Form\Input\MultiSelect;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase {
  public function testValue() {
    $form = new Form();
    $form->a = (new Input())->setValue("A");
    $form = new HtmlForm($form);
    $this->assertEquals(
      Html::escape("A"),
      $form->value("a")
    );
  }
  
  public function testInputText() {
    $form = new Form();
    $form->x = (new Input())
      ->setValue("X");
    $form = new HtmlForm($form);
    $h = (new Html())
      ->tag("input")
      ->attr("type", "text")
      ->attr("name", "x")
      ->attr("value", "X")
      ->attr("required", true);
    $this->assertEquals($h, $form->inputText("x"));
  }

  public function testTextarea() {
    $form = new Form();
    $form->x = (new Input())
      ->setValue("X\nY\nZ");
    $form = new HtmlForm($form);
    $h = (new Html())
      ->tag("textarea")
      ->attr("name", "x")
      ->attr("required", true)
      ->append("X\nY\nZ");
    $this->assertEquals($h, $form->textarea("x"));
  }

  public function testInputCheckboxes() {
    $form = new Form();
    $options = ["a" => "A", "b" => "B", "c" => "C"];
    $form->x = (new Select())
      ->setOptions($options)
      ->setValue("b");
    $form = new HtmlForm($form);
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
      $form->inputCheckboxes("x")
    );
  }

  public function testInputCheckboxesForMultiSelect() {
    $form = new Form();
    $options = ["a" => "A", "b" => "B", "c" => "C"];
    $form->x = (new MultiSelect())
      ->setOptions($options)
      ->setValue(["a", "b"]);
    $form = new HtmlForm($form);
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
      $form->inputCheckboxes("x")
    );
  }
  
  public function testSelect() {
    $form = new Form();
    $form->x = (new Select())
      ->setOptions(["a" => "A", "b" => "B", "c" => "C"])
      ->setValue("b");
    $form = new HtmlForm($form);
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
    $this->assertEquals($h, $form->select("x"));
  }
}
