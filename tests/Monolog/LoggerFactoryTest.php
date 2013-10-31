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
 
class LoggerFactoryTest extends \PHPUnit_Framework_TestCase
{
  /**
   * @covers Monolog\LoggerFactory::getDefaultLogger
   */
  public function testLoggerFromFile()
  {
    $logger = \Monolog\LoggerFactory::getLogger();
    $this->assertTrue(isset($logger));
  }

  public function testGetLogger()
  {
    $config = \Monolog\LoggerFactory::loadConfigFromFile('./monolog.cfg');
    $logger = \Monolog\LoggerFactory::getLogger($config);
    $this->assertTrue(isset($logger));
  }
}
