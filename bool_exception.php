<?php

namespace types;

use \Exception;

/**
 * used for return value type hinting when... a function either returns a bool or an Exception. Offcourse people can just throw the Exception a disregard the return value. But then that return type isn't strictly true, right?
 * Union with a bool is the hardest because a bool is a primitive
 * 
 * WORKS EXCEPT when accessing bool value. The getters and setters here work around class differences. True union would mean that
 * 
 * $exception_style = new ExceptionBoolUnion(new Exception('Ajax Chelsea'));
 * call any method / property and works.
 * but: not for throw $exception_style. Which would be the whole point.
 * $bool_style = new ExceptionBoolUnion(null, true);
 * call any method / property and works (there are none);
 * but: echo $bool_style; does not give the bool value.
 * 
 * This matters because the return type hinting requires initialization. 
 * Which is never the case for return type hinting. 
 * 
 * ☹🤬
 */
class ExceptionBoolUnion
{

  /**
   * holds bool value 
   */
  private $_bool;
  /**
   * holds exception value
   */
  private $_exception;

  /**
   * either bool or exception
   */
  private string $status;

  function __construct(Exception $exception = null, bool $bool = null)
  {

    if (is_null($exception) && is_null($bool)) {
      throw new Exception($this->class . " with neither exception or bool set");
    }

    // it's an exception!
    if ($exception instanceof Exception) {
      $this->status = 'exception';
      // throw in order to activate Exception
      try {
        throw $exception;
      } catch (Exception $thrown_exception) {
        $this->_exception = $thrown_exception;
      }
      return $this;
    }

    // so we're boolean today.
    $this->status = 'bool';
    $this->_bool = $bool;
    return $this;
  }

  /**
   * proxy all gets when boolean and return 
   * EXCEPT the one... that the boolean requires.
   */
  public function __get($name)
  {
    if ($this->status === 'bool') {
      return $this->_bool;
    }

    if (!property_exists(Exception::class, $name)) {
      throw new Exception($name . " does nog exist on Exception.");
    }
    return $this->_exception->$name;
  }

  /**
   * proxy all setters when boolean and return 
   */
  public function __set($name, $value)
  {
    $private_set_func_name = "set_$this->status";
    $this->$private_set_func_name($name, $value);
  }

  /**
   * disallows setting anything else on the Exception besides
   * properties allready set natively.
   */
  private function set_exception($name, $value)
  {
    if (!property_exists(Exception::class, $name)) {
      return false; // @TODO moet boolException worden,
    }
    $this->_exception->$name = $value;
    return $value;
  }

  /**
   * Really messy... bools and exceptions are so different
   * assume anything is fine, if name or value are bools?
   */
  private function set_bool($name, $value): void
  {
    if (is_bool($name)) {
      $this->_bool = $name;
      return;
    }
    if (is_bool($value)) {
      $this->_bool = $value;
      return;
    }
    throw new Exception("Trying to set a boolean to $value on $name? ridiculous");
  }

  /**
   * booleans dont have functions.
   */
  public function __call(string $func_name, array $arguments)
  {
    $private_call_func_name = "call_" . $this->status;
    return $this->$private_call_func_name($func_name, $arguments);
  }

  /**
   * calls Exception native methods and passes arguments.
   * like getMessage()
   */
  private function call_exception(string $func_name, array $arguments)
  {
    if (method_exists(Exception::class, $func_name)) {
      return $this->_exception->$func_name($arguments);
    }
    throw new Exception('geen rekening ook mee gehouden ');
  }

  /**
   * silly people like TRUE->('yes')
   */
  private function call_bool(string $func_name, array $arguments)
  {
    if (method_exists(\bool::class, $func_name)) {
      throw new Exception("gefeliciteerd. je hebt een methode op een boolean aangeroepen in PHP en PHP ervan overtuigt dat de boolean die methode kent.Wat wou je nu doen, $func_name?");
    }
    throw new Exception("$func_name is geen methode van bool.");
  }
}
