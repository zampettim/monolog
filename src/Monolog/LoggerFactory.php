<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog;

use Psr\Log\InvalidArgumentException;

/**
 * Monolog Factory implementation that provides Logger instances that can 
 * be configured in a number of ways.
 *
 * @author Marc Zampetti <marc@zampettis.com>
 */
class LoggerFactory extends Base
{
    /**
     * Attempts to load the configuration for the handlers and processors
     * from a JSON file.
     *
     * The configuration file is searched for using the following logic.
     * For all of these cases, if a file is involved, it is checked that it
     * can be read and that it can be decoded using json_decode. If not,
     * then the file is considered to be non-existent and the next rule 
     * is used.
     *   1) The $filename parameter contains the full path to a file.
     *   2) The $filename parameter contains the name of a file to search 
     *      for in the include_path.
     *   3) The $filename parameter is contains 
     *   4) The $config option was supplied in the constructor.
     *   5) The environment variable MONOLOG_CFG is defined, and contains
     *      the full path to a file.
     *   6) The environment variable MONOLOG_CFG is defined, and contains 
     *      the name of the file to search for in the include_path.
     *   7) The PHP configuration setting monolog.config is defined, and 
     *      contains the full path a file.
     *   8) The PHP configuration setting monolog.config is defined, and 
     *      contains the name of a file to search for in the include_path.
     *   9) The file monolog.cfg is found in the include_path.
     *
     * @return the configuration array that was loaded.
     * @throws InvalidArgumentException if no configuration information
     * can be found using the above rules.
     */
    public static function loadConfigFromFile($filename = null)
    {
      $data = null;

      // Figure out if we have the configuration 
      // information already, and if not, try to load it
      // from the appropriate config file.
      if ( isset($filename) ) 
      {
        $data = file_get_contents($filename, FILE_USE_INCLUDE_PATH);
      }

      if ( $data === FALSE )
      {
        $filename = $_ENV['MONOLOG_CFG'];
        if ( isset($filename) ) 
        {
          $data = file_get_contents($filename, FILE_USE_INCLUDE_PATH);
        }
      }

      if ( $data === FALSE ) 
      {
        $filename = ini_get('monolog.config');
        if ( isset($filename) ) 
        {
          $data = file_get_contents($filename, FILE_USE_INCLUDE_PATH);
        }
      }

      if ( ($data === FALSE) or (! isset($data)) )
      {
        // We couldn't find the configuration file or there wasn't
        // anything in the file.
        throw new InvalidArgumentException("Unable to load configuration");
      }

      $config = json_decode($data, true);

      // Process the configuration info if we have it.
      if ( ! isset($config) )
      {
        $msg = 'Unable to parse configuration. JSON Error: ';

        switch (json_last_error()) 
        {
          case JSON_ERROR_NONE:
            $msg .= 'No errors';
            break;

          case JSON_ERROR_DEPTH:
            $msg .= 'Max depth reached';
            break;

          case JSON_ERROR_STATE_MISMATCH:
            $msg .= 'Underflow or the modes mismatch';
            break;

          case JSON_ERROR_CTRL_CHAR:
            $msg .= 'Unexpected control character found';
            break;

          case JSON_ERROR_SYNTAX:
            $msg .= 'Syntax error, malformed JSON';
            break;

          case JSON_ERROR_UTF8:
            $msg .= 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;

          default:
            $msg .= 'Unknown error';
            break;
        }

        throw new InvalidArgumentException($msg);
      }

      // Return the configuration information we found.
      return $config;
    }

    /**
     * Instantiate a Logger instance with the given name, utilizing the
     * provided configuration information, which contains the Handlers 
     * and Processors that should be attached to the Logger.
     *
     * @param string $name - The Logger name or 'channel' to use.
     * @param array $config - The configuration array to use. 
     *
     * @return a Logger instance to use.
     */
    public static function getLogger($name, array $config = null)
    {
      $logger = new Logger($name);

      if ( isset($config) )
      {
        foreach($config['handlers'] as $handler)
        {
          $class = new \ReflectionClass("\\Monolog\\Handler\\" . $handler['class']);

          $isntance = null;

          if ( isset($handler['parameters']) )
          {
            // We need to convert the level parameter to its integer value.
            $level = static::convertLevel($handler['parameters']['level']);
            $handler['parameters']['level'] = $level;

            // Instantiate the handler and add it to the logger.
            $instance = $class->newInstanceArgs($handler['parameters']);
          }
          else {
            $instance = $class->newInstance();
          }

          if ( isset($instance) )
            $logger->pushHandler($instance);
        }
      }

      return $logger;
    }

    /**
     * Converts the level from the string name to integer value.
     *
     * @param mixed $level - The level. Can be the integer or string value.
     *
     * @return the integer value that corresponds to provided input. 
     * Returns Logger::DEBUG when the provided value cannot be 
     * mapped otherwise.
     */
    private static function convertLevel($level)
    {
      // If the $level is an int, an done of the defined levels.
      if ( is_int($level) and array_key_exists($level, parent::$levels) )
      {
        return $level;
      }

      $result = array_search($level, parent::$levels);

      if ( isset($result) )
        return $result;

      return(Logger::DEBUG);
    }

    /**
     * A simple configuration that instantiates the Logger using
     * the rules as defined in the loadConfigFromFile() method.
     *
     * @param string $name - The Logger name or 'channel' to use. 
     *                       Defaults to 'monolog'.
     *
     * @return a Logger instance to use.
     */
    public static function getDefaultLogger($name = 'monolog')
    {
      return(static::getLogger($name, static::loadConfigFromFile()));
    }
}
