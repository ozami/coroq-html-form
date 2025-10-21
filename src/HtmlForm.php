<?php
declare(strict_types=1);
namespace Coroq\HtmlForm;

use Coroq\Form\FormInterface;
use Coroq\Form\FormItem\FormItemInterface;
use Coroq\Form\FormItem\HasLengthRange;
use Coroq\Form\FormItem\HasNumericRange;
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Html\Html;

class HtmlForm {
  private FormInterface $form;
  private ErrorMessageFormatter $errorMessageFormatter;

  public function __construct(FormInterface $form, ErrorMessageFormatter $errorMessageFormatter) {
    $this->form = $form;
    $this->errorMessageFormatter = $errorMessageFormatter;
  }

  public function getForm(): FormInterface {
    return $this->form;
  }

  /**
   * Traverse form to get item at path
   * @param string|array<string> $item_path Path like "name" or "address/city" or ["address", "city"]
   * @throws \LogicException If path is invalid or item not found
   */
  protected function getItemIn(string|array $item_path): FormItemInterface {
    $path = is_array($item_path) ? $item_path : explode("/", $item_path);

    $current = $this->form;

    foreach ($path as $segment) {
      // Use FormInterface::getItem() method
      if ($current instanceof \Coroq\Form\FormInterface) {
        $current = $current->getItem($segment);
        if ($current === null) {
          throw new \LogicException("Item '$segment' not found in form");
        }
      }
      else {
        throw new \LogicException("Cannot traverse path - current item is not a FormInterface");
      }
    }

    if (!($current instanceof FormItemInterface)) {
      throw new \LogicException("Path does not resolve to a FormItemInterface");
    }

    return $current;
  }

  /**
   * @param string|array<string> $item_path
   */
  public function value(string|array $item_path): Html {
    return (new Html())->append($this->getItemIn($item_path)->getValue());
  }

  /**
   * @param string|array<string> $item_path
   */
  public function format(string|array $item_path, string $format): Html {
    $value = $this->getItemIn($item_path)->getValue();
    if ($value == "") {
      return new Html();
    }
    return (new Html())->append(sprintf($format, $value));
  }

  /**
   * @param string|array<string> $item_path
   */
  public function number(string|array $item_path, int $decimals = 0, string $dec_point = ".", string $thousands_sep = ","): Html {
    $value = $this->getItemIn($item_path)->getValue();
    if ($value == "") {
      return new Html();
    }
    return (new Html())->append(number_format((float)$value, $decimals, $dec_point, $thousands_sep));
  }

  /**
   * @param string|array<string> $item_path
   */
  public function date(string|array $item_path, string $format): Html {
    $value = $this->getItemIn($item_path)->getValue();
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
   * @param string|array<string> $item_path
   * @return Html|array<Html>
   */
  public function selected(string|array $item_path): Html|array {
    $item = $this->getItemIn($item_path);
    if (is_array($item->getValue())) {
      return array_map(function($label) {
        return (new Html())->append($label);
      }, $item->getSelectedLabel());
    }
    return (new Html())->append($item->getSelectedLabel());
  }

  /**
   * @param string|array<string> $item_path
   */
  public function input(string|array $item_path, string $type): Html {
    $item = $this->getItemIn($item_path);
    return (new Html())
      ->tag("input")
      ->attr("type", $type)
      ->attr("name", $this->makeName($item_path))
      ->attr("value", $item->getValue())
      ->attrs($this->getGeneralAttributesFromInput($item));
  }

  /**
   * @param string|array<string> $item_path
   */
  public function inputText(string|array $item_path): Html {
    return $this->input($item_path, "text");
  }

  /**
   * @param string|array<string> $item_path
   */
  public function inputNumber(string|array $item_path): Html {
    return $this->input($item_path, "number");
  }

  /**
   * @param string|array<string> $item_path
   */
  public function inputEmail(string|array $item_path): Html {
    return $this->input($item_path, "email");
  }

  /**
   * @param string|array<string> $item_path
   */
  public function inputTel(string|array $item_path): Html {
    return $this->input($item_path, "tel");
  }

  /**
   * @param string|array<string> $item_path
   */
  public function inputDate(string|array $item_path): Html {
    return $this->input($item_path, "date");
  }

  /**
   * @param string|array<string> $item_path
   */
  public function inputHidden(string|array $item_path): Html {
    return $this->input($item_path, "hidden");
  }

  /**
   * @param string|array<string> $item_path
   */
  public function inputPassword(string|array $item_path): Html {
    return $this->input($item_path, "password");
  }

  /**
   * @param string|array<string> $item_path
   */
  public function inputFile(string|array $item_path): Html {
    return $this->input($item_path, "file");
  }

  /**
   * @param string|array<string> $item_path
   */
  public function inputUrl(string|array $item_path): Html {
    return $this->input($item_path, "url");
  }

  /**
   * @param string|array<string> $item_path
   */
  public function textarea(string|array $item_path): Html {
    $item = $this->getItemIn($item_path);
    return (new Html())
      ->tag("textarea")
      ->attr("name", $this->makeName($item_path))
      ->attrs($this->getGeneralAttributesFromInput($item))
      ->append($item->getValue());
  }

  /**
   * @param string|array<string> $item_path
   */
  public function inputCheckbox(string|array $item_path, string $value): Html {
    return $this->inputCheckable($item_path, "checkbox", $value);
  }

  /**
   * Boolean checkbox - for BooleanInput (single checkbox without value)
   * @param string|array<string> $item_path
   */
  public function inputBoolean(string|array $item_path, string $value = "1"): Html {
    $item = $this->getItemIn($item_path);
    $h = $this->input($item_path, "checkbox");
    $h->attr("value", $value);
    if ($item->getValue()) {
      $h->attr("checked", true);
    }
    return $h;
  }

  /**
   * @param string|array<string> $item_path
   * @return array<string|int, Html>
   */
  public function inputCheckboxes(string|array $item_path): array {
    return $this->inputCheckables($item_path, "checkbox");
  }

  /**
   * @param string|array<string> $item_path
   */
  public function inputRadio(string|array $item_path, string $value): Html {
    return $this->inputCheckable($item_path, "radio", $value);
  }

  /**
   * @param string|array<string> $item_path
   * @return array<string|int, Html>
   */
  public function inputRadios(string|array $item_path): array {
    return $this->inputCheckables($item_path, "radio");
  }

  /**
   * @param string|array<string> $item_path
   */
  public function inputCheckable(string|array $item_path, string $type, string $value): Html {
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

  /**
   * @param string|array<string> $item_path
   * @return array<string|int, Html>
   */
  public function inputCheckables(string|array $item_path, string $type): array {
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

  /**
   * @param string|array<string> $item_path
   */
  public function select(string|array $item_path): Html {
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

  /**
   * @param string|array<string> $item_path
   * @return array<Html>
   */
  public function options(string|array $item_path): array {
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

  /**
   * @param string|array<string>|array<string|array<string>> $item_paths
   */
  public function error(string|array $item_paths): Html {
    $errors = [];
    foreach ((array)$item_paths as $item_path) {
      $item = $this->getItemIn($item_path);
      $errorObj = $item->getError();
      if ($errorObj) {
        $errorMessage = $this->errorMessageFormatter->format($errorObj);
        if ($errorMessage) {
          $errors[] = $errorMessage;
        }
      }
    }
    $errors = array_unique($errors);
    $errors = array_map(function(string $error): Html {
      return (new Html())
        ->append($error)
        ->tag("div");
    }, $errors);
    return (new Html())->children($errors);
  }

  /**
   * @param string|array<string> $item_path
   */
  public function makeName(string|array $item_path): string {
    $path = is_string($item_path) ? explode("/", $item_path) : $item_path;
    $name = array_shift($path);
    foreach ($path as $node) {
      $name .= "[$node]";
    }
    return $name;
  }

  /**
   * @return array<string, mixed>
   */
  protected function getGeneralAttributesFromInput(FormItemInterface $input): array {
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

    // Check for HasLengthRange interface
    if ($input instanceof HasLengthRange) {
      $max_length = $input->getMaxLength();
      if ($max_length < PHP_INT_MAX) {
        $attrs["maxlength"] = $max_length;
      }
      $min_length = $input->getMinLength();
      if ($min_length > 0) {
        $attrs["minlength"] = $min_length;
      }
    }

    // Check for HasNumericRange interface
    if ($input instanceof HasNumericRange) {
      $max = $input->getMax();
      if ($max !== INF) {
        $attrs["max"] = $max;
      }
      $min = $input->getMin();
      if ($min !== -INF) {
        $attrs["min"] = $min;
      }
    }

    return $attrs;
  }
}
