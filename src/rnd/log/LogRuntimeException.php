<?php
/**
 * @author: Marko Mikulic <mikulic.marko@spark.ba>
 */

namespace rnd\log;


/**
 * LogRuntimeException represents an exception caused by problems with log delivery.
 *
 * @author Bizley <pawel@positive.codes>
 * @since 2.0.14
 */
class LogRuntimeException extends \rnd\base\Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Log Runtime';
    }
}