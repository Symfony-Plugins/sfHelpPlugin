<?php

class sfPhpDocParser
{
  protected
    $shortDescription,
    $longDescription,
    $params = array(),
    $author,
    $return,
    $licence,
    $link,
    $todo;

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
      // TODO author
      // TODO licence
      // TODO link
      // TODO see
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

  public function getShortDescription()
  {
    return $this->shortDescription;
  }

  public function getLongDescription()
  {
    return $this->longDescription;
  }

  public function getParams()
  {
    return $this->params;
  }

  public function getReturn()
  {
    return $this->return;
  }

  protected function cleanupDocBlock($docBlock)
  {
    $docBlock = preg_replace(
      array(
        '#\s*/\*\*\s*#', # doc start /**
        '#\s*\*/\s*#',   # doc end */
        '#\s*\*#',       # doc line *
      ),
      '',
      split("\n", $docBlock)
    );

    return array_map('trim', $docBlock);
  }

  
}
