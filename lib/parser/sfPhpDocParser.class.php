<?php

/**
 * sfPhpDocParser is a tool to parse doc block comments of functions and classes
 *
 * @package     sfHelpPlugin
 * @subpackage  parser
 * @author      Noël GUILBERT <noelguilbert@gmail.com>
 * @version     SVN: $Id: sfPhpDocParser.class.php
 */
class sfPhpDocParser
{
  protected
    $shortDescription,
    $longDescription,
    $params = array(),
    $authors = array(),
    $return,
    $licence,
    $link,
    $todo;

  public function __construct($docBlock)
  {
    $this->parse($docBlock);
  }

  /**
   * Parses the given doc block
   *
   * The annotations @param, @author, @return and the descriptions (short and long) will be parsed
   *
   * @param string docBlock
   */
  public function parse($docBlock)
  {
    $lines = $this->cleanupDocBlock($docBlock);

    foreach ($lines as $line)
    {
      if (!$line)
      {
        continue;
      }
      elseif (preg_match('/@param\s+([^ ]+)\s+([^ ]+)\s+(.*)/', $line, $match))
      {
        $this->params[$match[2]] = array(
          'type'        => $match[1],
          'description' => $match[3],
        );
      }
      elseif (preg_match('/@return\s+([^ ]+)\s*(.*)/', $line, $match))
      {
        $this->return = array(
          'type'        => $match[1], 
          'description' => $match[2]
        );
      }
      elseif(preg_match('/@author\s+(.*)/', $line, $match))
      {
        $this->authors[] = $match[1];
      }
      elseif (!preg_match('/@\w+/', $line))
      {
        if (is_null($this->shortDescription))
        {
          $this->shortDescription = $line;
        }
        else
        {
          $this->longDescription .= $line."\n";
        }
      }
    }
  }

  /**
   * Returns the short description
   *
   * @return string
   */
  public function getShortDescription()
  {
    return $this->shortDescription;
  }

  /**
   * Returns the long description
   *
   * @return string
   */
  public function getLongDescription()
  {
    return $this->longDescription;
  }

  /**
   * Returns the function or method parameters
   *
   * @param  array $params Array of ReflectionParameters
   * @return array Array of parameters
   */
  public function getParams($params = array())
  {
    if (count($params))
    {
      foreach ($params as $reflectionParam)
      {
        if (!isset($this->params['$'.$reflectionParam->getName()]))
        {
          $param = array('$'.$reflectionParam->getName() => array('type' => '', 'description' => ''));
          $this->params = array_merge($this->params, $param);
        }
      }
    }

    return $this->params;
  }

  /**
   * Returns the @return parameter
   *
   * @return array 
   */ 
  public function getReturn()
  {
    return $this->return;
  }

  /**
   * Returns the authors
   *
   * @return array
   */
  public function getAuthors()
  {
    return $this->authors;
  }

  /**
   * Clean the given doc block
   *
   * @param  string $docBlock
   * @return string
   */
  protected function cleanupDocBlock($docBlock)
  {
    $docBlock = preg_replace(
      array(
        '#^\s*/\*\*\s*#', # doc start /**
        '#^\s*\*/\s*#',   # doc end */
        '#^\s*\*\s?#',    # doc line *
        '#\s*$#'          # trim spaces
      ),
      '',
      split("\n", $docBlock)
    );

    return $docBlock;
  }
}
