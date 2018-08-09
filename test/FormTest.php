<?php
use Coroq\Html\Form as HtmlForm;
use Coroq\Html;
use Coroq\Form;
use Coroq\Input;
use Coroq\Input\Select;
use Coroq\Input\MultiSelect;
  
class FormTest extends PHPUnit_Framework_TestCase {
  public function testValue() {
    $form = new HtmlForm(new Form());
    $form->setItem("a", (new Input())->setValue("A"));
    $this->assertEquals(
      Html::escape("A"),
      $form->value("a")
    );
  }
  
  public function testInputText() {
    $form = new HtmlForm(new Form());
    $input = (new Input())
      ->setValue("X");
    $form->setItem("x", $input);
    $h = (new Html())
      ->tag("input")
      ->attr("type", "text")
      ->attr("name", "x")
      ->attr("value", "X")
      ->attr("required", true);
    $this->assertEquals($h, $form->inputText("x"));
  }

  public function testTextarea() {
    $form = new HtmlForm(new Form());
    $input = (new Input())
      ->setValue("X\nY\nZ");
    $form->setItem("x", $input);
    $h = (new Html())
      ->tag("textarea")
      ->attr("name", "x")
      ->attr("required", true)
      ->append("X\nY\nZ");
    $this->assertEquals($h, $form->textarea("x"));
  }

  public function testInputCheckboxes() {
    $form = new HtmlForm(new Form());
    $options = ["a" => "A", "b" => "B", "c" => "C"];
    $input = (new Select())
      ->setOptions($options)
      ->setValue("b");
    $form->setItem("x", $input);
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
    $form = new HtmlForm(new Form());
    $options = ["a" => "A", "b" => "B", "c" => "C"];
    $input = (new MultiSelect())
      ->setOptions($options)
      ->setValue(["a", "b"]);
    $form->setItem("x", $input);
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
    $form = new HtmlForm(new Form());
    $input = (new Select())
      ->setOptions(["a" => "A", "b" => "B", "c" => "C"])
      ->setValue("b");
    $form->setItem("x", $input);
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
