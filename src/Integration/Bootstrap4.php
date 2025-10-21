<?php
declare(strict_types=1);
namespace Coroq\HtmlForm\Integration;

use Coroq\Html\Html;
use Coroq\HtmlForm\HtmlForm;

class Bootstrap4 extends HtmlForm {
  /**
   * @param string|array<string> $item_path
   */
  public function input(string|array $item_path, string $type): Html {
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

  /**
   * @param string|array<string> $item_path
   */
  public function textarea(string|array $item_path): Html {
    $h = parent::textarea($item_path);
    $h->addClass("form-control");
    return $this->addValidationClass($h, $item_path);
  }

  /**
   * @param string|array<string> $item_path
   */
  public function select(string|array $item_path): Html {
    $h = parent::select($item_path);
    $h->addClass("form-control");
    return $this->addValidationClass($h, $item_path);
  }

  /**
   * @param string|array<string>|array<string|array<string>> $item_paths
   */
  public function error(string|array $item_paths): Html {
    return parent::error($item_paths)->tag("div")->addClass("invalid-feedback");
  }

  /**
   * @param string|array<string> $item_path
   */
  protected function addValidationClass(Html $h, string|array $item_path): Html {
    if ($this->getItemIn($item_path)->getError()) {
      $h->addClass("is-invalid");
    }
    return $h;
  }
}
