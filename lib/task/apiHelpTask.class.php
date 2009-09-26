<?php

class apiHelpTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
     $this->addArguments(array(
       new sfCommandArgument('class_or_function', sfCommandArgument::REQUIRED, 'Class or function name'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', 'frontend', sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
    ));

    $this->namespace        = 'api';
    $this->name             = 'help';
    $this->briefDescription = '';
    $this->detailedDescription = <<<EOF
The [api:help|INFO] task does things.
Call it with:

  [php symfony api:help|INFO]
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
    }
    elseif (class_exists($classOrFunction))
    {
      $this->printClassHelp($classOrFunction);  
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

  protected function printClassHelp($className)
  {
    $rc = new ReflectionClass($className);

    $docParser = new sfPhpDocParser();
    $docParser->parse($rc->getDocComment());

    printf("Help on class %s\n\n", $this->formatBold($className));
    printf("%s\n", $this->formatBold('NAME'));
    printf("  %s\n\n", $docParser->getShortDescription() ? $docParser->getShortDescription() : $className);
    printf("%s\n", $this->formatBold('FILE'));
    if ($rc->isInternal())
    {
      print("  Internal PHP class\n\n");
    }
    else
    {
      printf("  %s\n\n", str_replace(sfConfig::get('sf_symfony_lib_dir'), 'SF_SYMFONY_LIB_DIR', $rc->getFileName()));
    }

    if ($docParser->getLongDescription())
    {
      printf("%s\n", $this->formatBold('DESCRIPTION'));
      printf("  %s\n", $docParser->getLongDescription());
    }

    printf("%s\n", $this->formatBold('METHODS'));

    foreach($rc->getMethods() as $rm)
    {
       $parser = new sfPhpDocParser();
       $parser->parse($rm->getDocComment());
       $desc = $parser->getShortDescription();
       
       printf("  %s\n", $this->getFunctionSynopsis($this->formatBold($rm->getName()), $parser));

       if ($desc)
       {
          printf("    %s\n\n", $desc);
       }
    }
  }

  protected function printMethodHelp($class, $method)
  {
    printf("Help on %s::%s() method\n\n", $this->formatBold($class), $this->formatBold($method));
    $rm = new ReflectionMethod($class, $method);
    $parser = new sfPhpDocParser();
    $parser->parse($rm->getDocComment());


    printf("%s\n", $this->formatBold('SYNOPSIS'));
    printf("  %s\n\n", $this->getFunctionSynopsis($method, $parser));

    printf("%s\n", $this->formatBold('DESCRIPTION'));
    $desc = preg_replace(array("/\n\s+/", '#</?code>#'), array("\n     ", '  '), $parser->getLongDescription());
    printf("  %s\n", $desc);


    if (count($parser->getParams()))
    {
      printf("%s\n", $this->formatBold('PARAMETERS'));

      foreach ($parser->getParams() as $name => $values)
      {
        printf("  %s %s %s\n", $values['type'], $this->formatBold($name), $values['description']);
      }

      print "\n";
    }

    if ($return = $parser->getReturn())
    {
      printf("%s\n", $this->formatBold('RETURN'));
      printf("  %s %s\n", $return['type'], $return['description']);
    }

    print "\n";
  }

  protected function printFunctionHelp($function)
  {
    print "TODO\n";
  }

  protected function getFunctionSynopsis($function, sfPhpDocParser $parser)
  {
    $params = array();
    
    foreach ($parser->getParams() as $name => $values)
    {
      $params[] = sprintf($values['type'].' '.$name);
    }

    return sprintf("%s %s(%s)", ($return = $parser->getReturn()) ? $return['type'] : 'void' , $function, join(', ', $params));
  }

  protected function formatBold($msg)
  {
    return $this->formatter->format($msg, array('bold' => true));
  }
}
