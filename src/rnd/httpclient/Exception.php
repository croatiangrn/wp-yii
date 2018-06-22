<?php
/**
 * @author: Marko Mikulic <mikulic.marko@spark.ba>
 */

namespace rnd\httpclient;


/**
 * Exception represents an exception that is caused during HTTP requests.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 */
class Exception extends \rnd\base\Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'HTTP Client Exception';
    }
}