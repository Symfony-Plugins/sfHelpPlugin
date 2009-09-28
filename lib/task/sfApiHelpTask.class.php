<?php

/**
 * sfApiHelpTask
 *
 * @package     sfHelpPlugin
 * @subpackage  task
 * @author      Noël GUILBERT <noelguilbert@gmail.com>
 * @version     SVN: $Id: sfApiHelpTask.class.php
 */
class sfApiHelpTask extends sfBaseTask
{
  protected function configure()
  {
     $this->addArguments(array(
       new sfCommandArgument('class_or_function', sfCommandArgument::REQUIRED, 'Class or function name'),
       new sfCommandArgument('application', sfCommandArgument::OPTIONAL, 'The application name', 'frontend'),
    ));

    $this->addOptions(array(
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
    ));

    $this->namespace        = 'doc';
    $this->name             = 'api';
    $this->briefDescription = 'Symfony API help utility';
    $this->detailedDescription = <<<EOF
The [doc:api|INFO] task is the CLI help utility
Call it with:

  You can get the API documentation for classes, methods or function:

  [php symfony doc:api sfForm|INFO]
  [php symfony doc:api sfForm::configure|INFO]
  [php symfony doc:api url_for|INFO]

EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $classOrFunction = $arguments['class_or_function'];

    if (false !== strpos($classOrFunction, '::'))
    {
      list($class, $method) = split('::', $classOrFunction);

      if (method_exists($class, $method))
      {
        $this->printMethodHelp($class, $method);
      }
      else
      {
        throw new InvalidArgumentException(sprintf('Method %s not found', $classOrFunction));
      }
    }
    elseif (class_exists($classOrFunction))
    {
      echo $this->getClassHelp($classOrFunction);
    }
    elseif (function_exists($classOrFunction))
    {
      $this->printFunctionHelp($classOrFunction);
    }
    else
    {
      $helpers = str_replace('Helper.php', '', sfFinder::type('file')->relative()->name('*Helper.php')->in(sfConfig::get('sf_symfony_lib_dir').'/helper'));
      $i = 0;
      while (!function_exists($classOrFunction) and isset($helpers[$i]))
      {
        $this->configuration->loadHelpers(array($helpers[$i++]));
      }

      if (function_exists($classOrFunction))
      {
        $this->printFunctionHelp($classOrFunction);
      }
      else
      {
        throw new InvalidArgumentException(sprintf('Class or function %s not found', $classOrFunction));
      }
    }
  }

  /**
   * Returns the class help
   *
   * @param  string The class name
   * @return string The class help
   */
  protected function getClassHelp($className)
  {
    $rc = new ReflectionClass($className);

    $docParser = new sfPhpDocParser($rc->getDocComment());
    
    $output = '';

    $output .= sprintf("Help on class %s\n\n", $this->formatBold($className));
    $output .= sprintf("%s\n", $this->formatBold('NAME'));
    $output .= sprintf("  %s\n\n", $docParser->getShortDescription() ? $docParser->getShortDescription() : $className);
    $output .= sprintf("%s\n", $this->formatBold('FILE'));

    if ($rc->isInternal())
    {
      $output .= "  Internal PHP class\n\n";
    }
    else
    {
      $output .= sprintf("  %s\n\n", str_replace(sfConfig::get('sf_symfony_lib_dir'), 'SF_SYMFONY_LIB_DIR', $rc->getFileName()));
    }

    if ($docParser->getLongDescription())
    {
      $output .= sprintf("%s\n", $this->formatBold('DESCRIPTION'));
      $output .= sprintf("  %s\n", $docParser->getLongDescription());
    }

    $output .= sprintf("%s\n", $this->formatBold('METHODS'));

    foreach($rc->getMethods() as $rm)
    {
       $parser = new sfPhpDocParser($rm->getDocComment());
       $desc = $parser->getShortDescription();
       
       $output .= sprintf("  %s\n", $this->getFunctionSynopsis(/*$this->formatBold($rm->getName())*/ $rm, $parser));

       if ($desc)
       {
          $output .= sprintf("    %s\n\n", $desc);
       }
    }

    return $output;
  }

  /**
   * Prints the help for the given method
   *
   * @param string $class  A class name
   * @param string $method A method name
   *
   * @return void
   */
  protected function printMethodHelp($class, $method)
  {
    printf("Help on %s::%s() method\n\n", $this->formatBold($class), $this->formatBold($method));
    $rm = new ReflectionMethod($class, $method);

    echo $this->getFunctionHelp($rm);
  }

  /**
   * Prints the help for the given function
   *
   * @param string $function The function name
   * @return void
   */
  protected function printFunctionHelp($function)
  {
    $rf = new ReflectionFunction($function);

    echo $this->getFunctionHelp($rf);
  }

  /**
   * Returns help for the given function or method
   *
   * @param  Reflection $reflectionObject An instance of ReflectionFunction or ReflectionMethod
   * @return string Help
   */
  protected function getFunctionHelp($reflectionObject)
  {
    $parser = new sfPhpDocParser($reflectionObject->getDocComment());

    $output  = '';

    $output .= sprintf("%s\n", $this->formatBold('SYNOPSIS'));
    $output .= sprintf("  %s\n\n", $this->getFunctionSynopsis($reflectionObject, $parser));

    if ($parser->getLongDescription())
    {
      $output .= sprintf("%s\n", $this->formatBold('DESCRIPTION'));
      $desc = preg_replace(array("/\n\s/", '#</?code>#'), array("\n    ", '  '), $parser->getLongDescription());
      $output .= sprintf("  %s\n", $desc);
    }


    if (count($parser->getParams($reflectionObject->getParameters())))
    {
      $output .= sprintf("%s\n", $this->formatBold('PARAMETERS'));

      foreach ($parser->getParams() as $name => $values)
      {
        $output .= sprintf("  %s %s %s\n", $values['type'], $this->formatBold($name), $values['description']);
      }

      print "\n";
    }

    if ($return = $parser->getReturn())
    {
      $output .= sprintf("%s\n", $this->formatBold('RETURN'));
      $output .= sprintf("  %s %s\n", $return['type'], $return['description']);
    }

    $output .= sprintf("\n");

    return $output;
  }

  /**
   * Returns the function synopsys for a given function name
   * 
   * @param ReflectionObject $function Instance of ReflectionFunction or ReflectionMethod
   * @param sfPhpDocParser $parser An instance of sfPhpDocParser
   * @return string Function synopsys
   */
  protected function getFunctionSynopsis($function, sfPhpDocParser $parser)
  {
    $params = array();
    
    foreach ($parser->getParams($function->getParameters()) as $name => $values)
    {
      $params[] = sprintf($values['type'].' '.$name);
    }

    return sprintf("%s %s(%s)", ($return = $parser->getReturn()) ? $return['type'] : 'void' , $this->formatBold($function->getName()), join(', ', $params));
  }

  /**
   * Formats the given text in bold using the current formatter
   *
   * @param  string $text
   * @return string
   */
  protected function formatBold($msg)
  {
    return $this->formatter->format($msg, array('bold' => true));
  }
}
