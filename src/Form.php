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

  /**
   * @param string|array $item_path
   * @param int $decimals
   * @param string $dec_point
   * @param string $thousands_sep
   * @return \Coroq\Html
   */
  public function number($item_path, $decimals = 0, $dec_point = ".", $thousands_sep = ",") {
    $value = $this->getItemIn($item_path)->getValue();
    if ($value == "") {
      return Html::escape("");
    }
    return Html::escape(number_format($value, $decimals, $dec_point, $thousands_sep));
  }

  /**
   * @param string|array $item_path
   * @param string $format
   * @return \Coroq\Html
   */
  public function date($item_path, $format) {
    $value = $this->getItemIn($item_path)->getValue();
    if ($value == "") {
      return Html::escape("");
    }
    $time = strtotime($value);
    if ($time === false) {
      throw new \RuntimeException("Invaild date time string '$value'");
    }
    return Html::escape(date($format, $time));
  }

  /**
   * @param string|array $item_path
   * @return \Coroq\Html|array<\Coroq\Html>
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
      ->attr("value", $item->getValue())
      ->attrs($this->getGeneralAttributesFromInput($item));
  }

  /**
   * @param string|array $item_path
   * @return \Coroq\Html
   */
  public function inputText($item_path) {
    return $this->input($item_path, "text");
  }

  /**
   * @param string|array $item_path
   * @return \Coroq\Html
   */
  public function inputEmail($item_path) {
    return $this->input($item_path, "email");
  }

  /**
   * @param string|array $item_path
   * @return \Coroq\Html
   */
  public function inputTel($item_path) {
    return $this->input($item_path, "tel");
  }

  /**
   * @param string|array $item_path
   * @return \Coroq\Html
   */
  public function inputDate($item_path) {
    return $this->input($item_path, "date");
  }

  /**
   * @param string|array $item_path
   * @return \Coroq\Html
   */
  public function inputHidden($item_path) {
    return $this->input($item_path, "hidden");
  }

  /**
   * @param string|array $item_path
   * @return \Coroq\Html
   */
  public function inputPassword($item_path) {
    return $this->input($item_path, "password");
  }

  /**
   * @param string|array $item_path
   * @return \Coroq\Html
   */
  public function inputFile($item_path) {
    return $this->input($item_path, "file");
  }

  /**
   * @param string|array $item_path
   * @return \Coroq\Html
   */
  public function textarea($item_path) {
    $item = $this->getItemIn($item_path);
    return (new Html())
      ->tag("textarea")
      ->attr("name", $this->makeName($item_path))
      ->attrs($this->getGeneralAttributesFromInput($item))
      ->append($item->getValue());
  }

  /**
   * @param string|array $item_path
   * @param string $value
   * @return \Coroq\Html
   */
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
    $selected = $item->getValue();
    if (is_array($selected)) {
      $h->attr("name", $this->makeName($item_path) . "[]");
    }
    $h->attr("value", $value);
    if (in_array("$value", (array)$selected, true)) {
      $h->attr("checked", true);
    }
    return $h;
  }

  public function inputCheckables($item_path, $type) {
    $fn = [$this, "input$type"];
    $inputs = [];
    foreach ($this->getItemIn($item_path)->getOptions() as $value => $label) {
      $input = call_user_func($fn, $item_path, $value);
      $input->attr("title", $label);
      if ($type == "checkbox") {
        $input->attr("required", false);
      }
      $inputs[$value] = $input;
    }
    return $inputs;
  }

  public function select($item_path) {
    $item = $this->getItemIn($item_path);
    $h = (new Html())
      ->tag("select")
      ->attrs($this->getGeneralAttributesFromInput($item))
      ->children($this->options($item_path));
    if (is_array($item->getValue())) {
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
      $options[] = (new Html())
        ->tag("option")
        ->attrs($attrs)
        ->append($label);
    }
    return $options;
  }

  public function makeName($item_path) {
    $options = $this->form->getOptions();
    $item_path = explode($options["path_separator"], $item_path);
    $name = array_shift($item_path);
    foreach ($item_path as $node) {
      $name .= "[$node]";
    }
    return $name;
  }

  protected function getGeneralAttributesFromInput($input) {
    $attrs = [];
    if ($input->isRequired()) {
      $attrs["required"] = true;
    }
    if ($input->isReadOnly()) {
      $attrs["readonly"] = true;
    }
    if ($input->isDisabled()) {
      $attrs["disabled"] = true;
    }
    if (method_exists($input, "getMaxLength")) {
      $max_length = $input->getMaxLength();
      if ($max_length < PHP_INT_MAX) {
        $attrs["maxlength"] = $max_length;
      }
    }
    if (method_exists($input, "getMinLength")) {
      $min_length = $input->getMinLength();
      if ($min_length > 0) {
        $attrs["minlength"] = $min_length;
      }
    }
    if (method_exists($input, "getMax")) {
      $attrs["max"] = $input->getMax();
    }
    if (method_exists($input, "getMin")) {
      $attrs["min"] = $input->getMin();
    }
    return $attrs;
  }

  public function __call($name, $args) {
    return call_user_func_array([$this->form, $name], $args);
  }
}
