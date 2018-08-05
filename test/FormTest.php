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
  
  public function testInputCheckboxes() {
    $form = new HtmlForm(new Form());
    $input = (new Select())
      ->setOptions(["a" => "A", "b" => "B", "c" => "C"])
      ->setValue("b");
    $form->setItem("x", $input);
    $this->assertEquals(
      array_map(function($value) {
        $h = (new Html())
          ->tag("input")
          ->attr("type", "checkbox")
          ->attr("name", "x")
          ->attr("value", $value);
        if ($value == "b") {
          $h->attr("checked", true);
        }
        return $h;
      }, ["a", "b", "c"]),
      $form->inputCheckboxes("x")
    );
  }

  public function testInputCheckboxesForMultiSelect() {
    $form = new HtmlForm(new Form());
    $input = (new MultiSelect())
      ->setOptions(["a" => "A", "b" => "B", "c" => "C"])
      ->setValue(["a", "b"]);
    $form->setItem("x", $input);
    $this->assertEquals(
      array_map(function($value) {
        $h = (new Html())
          ->tag("input")
          ->attr("type", "checkbox")
          ->attr("name", "x[]")
          ->attr("value", $value);
        if ($value == "a" || $value == "b") {
          $h->attr("checked", true);
        }
        return $h;
      }, ["a", "b", "c"]),
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
