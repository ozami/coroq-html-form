<?php
use Coroq\Html\Form as HtmlForm;
use Coroq\Html;
use Coroq\Form;
use Coroq\Input;

class FormTest extends PHPUnit_Framework_TestCase {
  public function testValue() {
    $form = new HtmlForm(new Form());
    $form->setItem("a", (new Input())->setValue("A"));
    $this->assertEquals(
      Html::escape("A"),
      $form->value("a")
    );
  }
}
