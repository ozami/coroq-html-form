<?php
namespace Coroq\Html;
use \Coroq\Html;

class Form {
  /** @var \Coroq\Form $form */
  protected $form;

  /**
   * @param \Coroq\Form $form
   */
  public function __construct(\Coroq\Form $form) {
    $this->form = $form;
  }

  /**
   * @param string|array $item_path
   * @return \Coroq\Html
   */
  public function value($item_path) {
    return Html::escape($this->getItemIn($item_path)->getValue());
  }

  /**
   * @param string|array $item_path
   * @param string $format
   * @return \Coroq\Html
   */
  public function format($item_path, $format) {
    $value = $this->getItemIn($item_path)->getValue();
    if ($value == "") {
      return Html::escape("");
    }
    return Html::escape(sprintf($format, $value));
  }

  public function number($item_path, $decimals = 0, $dec_point = ".", $thousands_sep = ",") {
    $value = $this->getItemIn($item_path)->getValue();
    if ($value == "") {
      return Html::escape("");
    }
    return Html::escape(number_format($value, $decimals, $dec_point, $thousands_sep));
  }

  public function date($item_path, $format) {
    $value = $this->getItemIn($item_path)->getValue();
    if ($value == "") {
      return Html::escape("");
    }
    $time = strtotime($value);
    if ($time === false) {
      throw new \RuntimeException("Invaild date time string '$value'");
    }
    return Html::escape(date($time, $format));
  }

  /**
   * @param string|array $item_path
   * @return \Coroq\Html
   */
  public function selected($item_path) {
    $item = $this->getItemIn($item_path);
    if (is_array($item->getValue())) {
      return array_map(function($label) {
        return Html::escape($label);
      }, $item->getSelectedLabel());
    }
    return Html::escape($item->getSelectedLabel());
  }

  /**
   * @param string|array $item_path
   * @param string $type
   * @return \Coroq\Html
   */
  public function input($item_path, $type) {
    $item = $this->getItemIn($item_path);
    return (new Html())
      ->tag("input")
      ->attr("type", $type)
      ->attr("name", $this->makeName($item_path))
      ->attr("value", $item->getValue());
  }

  public function inputText($item_path) {
    return $this->input($item_path, "text");
  }

  public function inputEmail($item_path) {
    return $this->input($item_path, "email");
  }

  public function inputDate($item_path) {
    $html = $this->input($item_path, "date");
  }

  public function inputHidden($item_path) {
    return $this->input($item_path, "hidden");
  }

  public function inputPassword($item_path) {
    return $this->input($item_path, "password");
  }

  public function inputFile($item_path) {
    return $this->input($item_path, "file");
  }

  public function textarea($item_path) {
    return Html::escape($this->getItemIn($item_path)->getValue())
      ->tag("textarea")
      ->attr("name", $this->makeName($item_path));
  }

  public function inputCheckbox($item_path, $value) {
    return $this->inputCheckable($item_path, "checkbox", $value);
  }

  public function inputCheckboxes($item_path) {
    return $this->inputCheckables($item_path, "checkbox");
  }

  public function inputRadio($item_path, $value) {
    return $this->inputCheckable($item_path, "radio", $value);
  }

  public function inputRadios($item_path) {
    return $this->inputCheckables($item_path, "radio");
  }

  public function inputCheckable($item_path, $type, $value) {
    $h = $this->input($item_path, $type);
    $item = $this->getItemIn($item_path);
    $value = $item->getValue();
    if (is_array($value)) {
      $h->attr("name", $this->makeName($item_path) . "[]");
    }
    if (in_array("$value", (array)$value)) {
      $h->attr("checked", true);
    }
    return $h;
  }

  public function inputCheckables($item_path, $type) {
    $fn = [$this, "input$type"];
    return array_map(function($value) use ($item_path, $fn) {
      return call_user_func($fn, $item_path, $value);
    }, array_keys($this->getItemIn($item_path)->getOptions()));
  }

  public function select($item_path) {
    $h = new Html()
      ->tag("select")
      ->children($this->options($item_path));
    if (is_array($this->getItemIn($item_path)->getValue())) {
      $h->attr("name", $this->makeName($item_path) . "[]");
      $h->attr("multiple", true);
    }
    else {
      $h->attr("name", $this->makeName($item_path));
    }
    return $h;
  }

  public function options($item_path) {
    $item = $this->getItemIn($item_path);
    $selected = (array)$item->getValue();
    $options = [];
    foreach ($item->getOptions() as $value => $label) {
      $attrs = compact("value");
      if (in_array("$value", $selected)) {
        $attrs["selected"] = true;
      }
      $options[] = Html::escape($label)->attrs($attrs);
    }
    return $options;
  }

  public function makeName($item_path) {
    $item_path = explode($this->options["path_separator"], $item_path);
    $name = array_shift($item_path);
    foreach ($item_path as $node) {
      $name .= "[$node]";
    }
    return $name;
  }

  public function __call($name, $args) {
    return call_user_func_array([$this->form, $name], $args);
  }
}
