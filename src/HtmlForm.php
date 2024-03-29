<?php
namespace Coroq\HtmlForm;

use Coroq\Form\Form;
use Coroq\Html\Html;

class HtmlForm {
  /** @var Form */
  private $form;

  /**
   * @param Form $form
   */
  public function __construct(Form $form) {
    $this->form = $form;
  }

  public function getForm(): Form {
    return $this->form;
  }

  /**
   * @param string|array $item_path
   * @return Html
   */
  public function value($item_path) {
    return (new Html())->append($this->form->getItemIn($item_path)->getValue());
  }

  /**
   * @param string|array $item_path
   * @param string $format
   */
  public function format($item_path, $format): Html {
    $value = $this->form->getItemIn($item_path)->getValue();
    if ($value == "") {
      return new Html();
    }
    return (new Html())->append(sprintf($format, $value));
  }

  /**
   * @param string|array $item_path
   * @param int $decimals
   * @param string $dec_point
   * @param string $thousands_sep
   * @return Html
   */
  public function number($item_path, $decimals = 0, $dec_point = ".", $thousands_sep = ","): Html {
    $value = $this->form->getItemIn($item_path)->getValue();
    if ($value == "") {
      return new Html();
    }
    return (new Html())->append(number_format($value, $decimals, $dec_point, $thousands_sep));
  }

  /**
   * @param string|array $item_path
   * @param string $format
   */
  public function date($item_path, $format): Html {
    $value = $this->form->getItemIn($item_path)->getValue();
    if ($value == "") {
      return new Html();
    }
    $time = strtotime($value);
    if ($time === false) {
      throw new \RuntimeException("Invaild date time string '$value'");
    }
    return (new Html())->append(date($format, $time));
  }

  /**
   * @param string|array $item_path
   * @return Html|array<Html>
   */
  public function selected($item_path) {
    $item = $this->form->getItemIn($item_path);
    if (is_array($item->getValue())) {
      return array_map(function($label) {
        return (new Html())->append($label);
      }, $item->getSelectedLabel());
    }
    return (new Html())->append($item->getSelectedLabel());
  }

  /**
   * @param string|array $item_path
   * @param string $type
   */
  public function input($item_path, $type): Html {
    $item = $this->form->getItemIn($item_path);
    return (new Html())
      ->tag("input")
      ->attr("type", $type)
      ->attr("name", $this->makeName($item_path))
      ->attr("value", $item->getValue())
      ->attrs($this->getGeneralAttributesFromInput($item));
  }

  /**
   * @param string|array $item_path
   */
  public function inputText($item_path): Html {
    return $this->input($item_path, "text");
  }

  /**
   * @param string|array $item_path
   */
  public function inputNumber($item_path): Html {
    return $this->input($item_path, "number");
  }

  /**
   * @param string|array $item_path
   */
  public function inputEmail($item_path): Html {
    return $this->input($item_path, "email");
  }

  /**
   * @param string|array $item_path
   */
  public function inputTel($item_path): Html {
    return $this->input($item_path, "tel");
  }

  /**
   * @param string|array $item_path
   */
  public function inputDate($item_path): Html {
    return $this->input($item_path, "date");
  }

  /**
   * @param string|array $item_path
   */
  public function inputHidden($item_path): Html {
    return $this->input($item_path, "hidden");
  }

  /**
   * @param string|array $item_path
   */
  public function inputPassword($item_path): Html {
    return $this->input($item_path, "password");
  }

  /**
   * @param string|array $item_path
   */
  public function inputFile($item_path): Html {
    return $this->input($item_path, "file");
  }

  /**
   * @param string|array $item_path
   */
  public function textarea($item_path): Html {
    $item = $this->form->getItemIn($item_path);
    return (new Html())
      ->tag("textarea")
      ->attr("name", $this->makeName($item_path))
      ->attrs($this->getGeneralAttributesFromInput($item))
      ->append($item->getValue());
  }

  /**
   * @param string|array $item_path
   * @param string $value
   */
  public function inputCheckbox($item_path, $value): Html {
    return $this->inputCheckable($item_path, "checkbox", $value);
  }

  public function inputCheckboxes($item_path): array {
    return $this->inputCheckables($item_path, "checkbox");
  }

  public function inputRadio($item_path, $value): Html {
    return $this->inputCheckable($item_path, "radio", $value);
  }

  public function inputRadios($item_path): array {
    return $this->inputCheckables($item_path, "radio");
  }

  public function inputCheckable($item_path, $type, $value): Html {
    $h = $this->input($item_path, $type);
    $item = $this->form->getItemIn($item_path);
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

  public function inputCheckables($item_path, $type): array {
    $fn = [$this, "input$type"];
    $inputs = [];
    foreach ($this->form->getItemIn($item_path)->getOptions() as $value => $label) {
      $input = call_user_func($fn, $item_path, $value);
      $input->attr("title", $label);
      if ($type == "checkbox") {
        $input->attr("required", false);
      }
      $inputs[$value] = $input;
    }
    return $inputs;
  }

  public function select($item_path): Html {
    $item = $this->form->getItemIn($item_path);
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

  public function options($item_path): array {
    $item = $this->form->getItemIn($item_path);
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

  public function error($item_paths): Html {
    $errors = [];
    foreach ((array)$item_paths as $item_path) {
      $item = $this->form->getItemIn($item_path);
      $error = $item->getErrorString();
      if ($error) {
        $errors[] = $error;
      }
    }
    $errors = array_unique($errors);
    $errors = array_map(function($error) {
      return (new Html())
        ->append($error)
        ->tag("div");
    }, $errors);
    return (new Html())->children($errors);
  }

  public function makeName($item_path): string {
    $item_path = explode("/", $item_path);
    $name = array_shift($item_path);
    foreach ($item_path as $node) {
      $name .= "[$node]";
    }
    return $name;
  }

  protected function getGeneralAttributesFromInput($input): array {
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
}
