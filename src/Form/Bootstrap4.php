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
    $errors = [];
    foreach ((array)$item_paths as $item_path) {
      $item = $this->getItemIn($item_path);
      $error = $item->getError();
      if ($error) {
        $errors[] = "$error";
      }
    }
    $errors = array_unique($errors);
    $errors = array_map(function($error) {
      return (new Html())
        ->append($error)
        ->tag("p")
        ->addClass("invalid-feedback");
    }, $errors);
    return (new Html())->children($errors);
  }

  protected function addValidationClass($h, $item_path) {
    if ($this->form->getItemIn($item_path)->getError()) {
      $h->addClass("is-invalid");
    }
    return $h;
  }
}
