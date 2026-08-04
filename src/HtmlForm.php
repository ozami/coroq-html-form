<?php
declare(strict_types=1);
namespace Coroq\HtmlForm;

use Coroq\Form\FormInterface;
use Coroq\Form\FormItem\FormItemInterface;
use Coroq\Form\FormItem\HasLengthRangeInterface;
use Coroq\Form\FormItem\HasNumericRangeInterface;
use Coroq\Form\ErrorMessageFormatter;
use Coroq\Html\Html;
use InvalidArgumentException;
use LogicException;

class HtmlForm {
  private FormInterface $form;
  private ErrorMessageFormatter $errorMessageFormatter;
  private string $itemPathDelimiter = "/";

  /**
   * Create a new HtmlForm instance
   */
  public function __construct(FormInterface $form, ErrorMessageFormatter $errorMessageFormatter) {
    $this->form = $form;
    $this->errorMessageFormatter = $errorMessageFormatter;
  }

  /**
   * Get the underlying form object
   */
  public function getForm(): FormInterface {
    return $this->form;
  }

  /**
   * Get the item path delimiter
   */
  public function getItemPathDelimiter(): string {
    return $this->itemPathDelimiter;
  }

  /**
   * Set the item path delimiter
   * Change it when an item name has to contain "/"
   */
  public function setItemPathDelimiter(string $delimiter): static {
    if ($delimiter === "") {
      throw new InvalidArgumentException("An item path delimiter cannot be empty");
    }
    $this->itemPathDelimiter = $delimiter;
    return $this;
  }

  /**
   * Traverse form to get item at path
   * @param string $item_path Path like "name" or "address/city"
   * @throws LogicException If path is invalid or item not found
   */
  protected function getItemIn(string $item_path): FormItemInterface {
    $current = $this->form;

    foreach (explode($this->itemPathDelimiter, $item_path) as $segment) {
      if (!($current instanceof FormInterface)) {
        throw new LogicException("Item '$item_path' not found in form");
      }
      $current = $current->getItem($segment);
      if ($current === null) {
        throw new LogicException("Item '$item_path' not found in form");
      }
    }

    return $current;
  }

  /**
   * Get form item value wrapped in Html object
   * @param string $item_path
   */
  public function value(string $item_path): Html {
    return (new Html())->append($this->getItemIn($item_path)->getValue());
  }

  /**
   * Format form item value using sprintf
   * @param string $item_path
   */
  public function format(string $item_path, string $format): Html {
    $value = $this->getItemIn($item_path)->getValue();
    if ($value == "") {
      return new Html();
    }
    return (new Html())->append(sprintf($format, $value));
  }

  /**
   * Format numeric value with number_format
   * @param string $item_path
   */
  public function number(string $item_path, int $decimals = 0, string $dec_point = ".", string $thousands_sep = ","): Html {
    $value = $this->getItemIn($item_path)->getValue();
    if ($value == "") {
      return new Html();
    }
    return (new Html())->append(number_format((float)$value, $decimals, $dec_point, $thousands_sep));
  }

  /**
   * Format date value with date formatting
   * @param string $item_path
   */
  public function date(string $item_path, string $format): Html {
    $value = $this->getItemIn($item_path)->getValue();
    if ($value == "") {
      return new Html();
    }
    $time = strtotime($value);
    if ($time === false) {
      throw new LogicException("Invaild date time string '$value'");
    }
    return (new Html())->append(date($format, $time));
  }

  /**
   * Get selected label(s) from select/multi-select item
   * @param string $item_path
   * @return Html|array<Html>
   */
  public function selected(string $item_path): Html|array {
    $item = $this->getItemIn($item_path);
    if (is_array($item->getValue())) {
      return array_map(function($label) {
        return (new Html())->append($label);
      }, $item->getSelectedLabel());
    }
    return (new Html())->append($item->getSelectedLabel());
  }

  /**
   * Generate input element with specified type
   *
   * A value attribute is a string, but an item value is mixed. A value with no
   * string form, such as the array a multi-select holds, sets no value
   * attribute; inputCheckable() supplies one per option in that case.
   *
   * @param string $item_path
   */
  public function input(string $item_path, string $type): Html {
    $item = $this->getItemIn($item_path);
    $value = $item->getValue();
    $canBeString = is_scalar($value) || $value instanceof \Stringable;
    return (new Html())
      ->tag("input")
      ->attr("type", $type)
      ->attr("name", $this->makeName($item_path))
      ->attr("value", $canBeString ? $value : null)
      ->attrs($this->getGeneralAttributes($item));
  }

  /**
   * Generate text input element
   * @param string $item_path
   */
  public function inputText(string $item_path): Html {
    return $this->input($item_path, "text");
  }

  /**
   * Generate number input element
   * @param string $item_path
   */
  public function inputNumber(string $item_path): Html {
    return $this->input($item_path, "number");
  }

  /**
   * Generate email input element
   * @param string $item_path
   */
  public function inputEmail(string $item_path): Html {
    return $this->input($item_path, "email");
  }

  /**
   * Generate tel input element
   * @param string $item_path
   */
  public function inputTel(string $item_path): Html {
    return $this->input($item_path, "tel");
  }

  /**
   * Generate date input element
   * @param string $item_path
   */
  public function inputDate(string $item_path): Html {
    return $this->input($item_path, "date");
  }

  /**
   * Generate hidden input element
   * @param string $item_path
   */
  public function inputHidden(string $item_path): Html {
    return $this->input($item_path, "hidden");
  }

  /**
   * Generate password input element
   * @param string $item_path
   */
  public function inputPassword(string $item_path): Html {
    return $this->input($item_path, "password");
  }

  /**
   * Generate file input element
   * @param string $item_path
   */
  public function inputFile(string $item_path): Html {
    return $this->input($item_path, "file");
  }

  /**
   * Generate URL input element
   * @param string $item_path
   */
  public function inputUrl(string $item_path): Html {
    return $this->input($item_path, "url");
  }

  /**
   * Generate textarea element
   * @param string $item_path
   */
  public function textarea(string $item_path): Html {
    $item = $this->getItemIn($item_path);
    return (new Html())
      ->tag("textarea")
      ->attr("name", $this->makeName($item_path))
      ->attrs($this->getGeneralAttributes($item))
      ->append($item->getValue());
  }

  /**
   * Generate single checkbox input element
   * @param string $item_path
   */
  public function inputCheckbox(string $item_path, string $value): Html {
    return $this->inputCheckable($item_path, "checkbox", $value);
  }

  /**
   * Generate boolean checkbox input element
   * @param string $item_path
   */
  public function inputBoolean(string $item_path, string $value = "1"): Html {
    $item = $this->getItemIn($item_path);
    $h = $this->input($item_path, "checkbox");
    $h->attr("value", $value);
    if ($item->getValue()) {
      $h->attr("checked", true);
    }
    return $h;
  }

  /**
   * Generate all checkbox elements for a form item
   * @param string $item_path
   * @return array<string|int, Html>
   */
  public function inputCheckboxes(string $item_path): array {
    return $this->inputCheckables($item_path, "checkbox");
  }

  /**
   * Generate single radio button input element
   * @param string $item_path
   */
  public function inputRadio(string $item_path, string $value): Html {
    return $this->inputCheckable($item_path, "radio", $value);
  }

  /**
   * Generate all radio button elements for a form item
   * @param string $item_path
   * @return array<string|int, Html>
   */
  public function inputRadios(string $item_path): array {
    return $this->inputCheckables($item_path, "radio");
  }

  /**
   * Generate checkable input element (checkbox or radio)
   * Reached through inputCheckbox() and inputRadio(); override it to change both
   * @param string $item_path
   */
  protected function inputCheckable(string $item_path, string $type, string $value): Html {
    $item = $this->getItemIn($item_path);
    $selected = $item->getValue();
    $h = $this->input($item_path, $type);
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
   * Generate all checkable elements (checkboxes or radios) for a form item
   * Reached through inputCheckboxes() and inputRadios()
   * @param string $item_path
   * @return array<string|int, Html>
   */
  protected function inputCheckables(string $item_path, string $type): array {
    $inputs = [];
    foreach ($this->getItemIn($item_path)->getOptions() as $value => $label) {
      $input = $this->inputCheckable($item_path, $type, "$value");
      $input->attr("title", $label);
      if ($type == "checkbox") {
        $input->attr("required", false);
      }
      $inputs[$value] = $input;
    }
    return $inputs;
  }

  /**
   * Generate select element
   * @param string $item_path
   */
  public function select(string $item_path): Html {
    $item = $this->getItemIn($item_path);
    $isArray = is_array($item->getValue());
    $h = (new Html())
      ->tag("select")
      ->attr("name", $this->makeName($item_path) . ($isArray ? "[]" : ""))
      ->attrs($this->getGeneralAttributes($item))
      ->children($this->options($item_path));
    if ($isArray) {
      $h->attr("multiple", true);
    }
    return $h;
  }

  /**
   * Generate option elements for a select
   * @param string $item_path
   * @return array<Html>
   */
  public function options(string $item_path): array {
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
   * Generate error message elements for form items
   * Several paths make one block for fields shown side by side; duplicate messages appear once
   * @param string ...$item_paths
   */
  public function error(string ...$item_paths): Html {
    $errors = [];
    foreach ($item_paths as $item_path) {
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
   * Convert item path to HTML name attribute with array notation
   * An item name cannot contain the delimiter, "[" or "]"
   * @param string $item_path
   */
  public function makeName(string $item_path): string {
    $path = explode($this->itemPathDelimiter, $item_path);
    $name = array_shift($path);
    foreach ($path as $node) {
      $name .= "[$node]";
    }
    return $name;
  }

  /**
   * Extract HTML state and validation attributes from form item
   * @return array<string, mixed>
   */
  protected function getGeneralAttributes(FormItemInterface $item): array {
    $attrs = [];
    if ($item->isRequired()) {
      $attrs["required"] = true;
    }
    if ($item->isReadOnly()) {
      $attrs["readonly"] = true;
    }
    if ($item->isDisabled()) {
      $attrs["disabled"] = true;
    }

    if ($item instanceof HasLengthRangeInterface) {
      $max_length = $item->getMaxLength();
      if ($max_length < PHP_INT_MAX) {
        $attrs["maxlength"] = $max_length;
      }
      $min_length = $item->getMinLength();
      if ($min_length > 0) {
        $attrs["minlength"] = $min_length;
      }
    }

    if ($item instanceof HasNumericRangeInterface) {
      $max = $item->getMax();
      if ($max !== INF) {
        $attrs["max"] = $max;
      }
      $min = $item->getMin();
      if ($min !== -INF) {
        $attrs["min"] = $min;
      }
    }

    return $attrs;
  }
}
