<?php
namespace Coroq\Html\Form;
use Coroq\Html;

class Bootstrap4 extends \Coroq\Html\Form {
  public function input($item_path, $type) {
    $h = parent::input($item_path, $type);
    if ($type == "file") {
      $h->addClass("form-control-file");
    }
    elseif ($type == "checkbox" || $type == "radio") {
      $h->addClass("form-check-input");
    }
    elseif ($type == "hidden") {
      // do nothing
    }
    else {
      $h->addClass("form-control");
    }
    return $this->addValidationClass($h, $item_path);
  }

  public function textarea($item_path) {
    $h = parent::textarea($item_path);
    $h->addClass("form-control");
    return $this->addValidationClass($h, $item_path);
  }

  public function select($item_path) {
    $h = parent::select($item_path);
    $h->addClass("form-control");
    return $this->addValidationClass($h, $item_path);
  }

  public function error($item_paths) {
    return parent::error($item_paths)->tag("div")->addClass("invalid-feedback");
  }

  protected function addValidationClass($h, $item_path) {
    if ($this->form->getItemIn($item_path)->getError()) {
      $h->addClass("is-invalid");
    }
    return $h;
  }
}
