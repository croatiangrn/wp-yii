<?php
/**
 * @author: Marko Mikulic <mikulic.marko@spark.ba>
 */

namespace rnd\httpclient;


use rnd\base\BaseObject;
use rnd\helpers\Json;

class JsonFormatter extends BaseObject implements FormatterInterface
{
    /**
     * @var int the encoding options. For more details please refer to
     * <http://www.php.net/manual/en/function.json-encode.php>.
     */
    public $encodeOptions = 0;


    /**
     * {@inheritdoc}
     */
    public function format(Request $request)
    {
        $request->getHeaders()->set('Content-Type', 'application/json; charset=UTF-8');
        if (($data = $request->getData()) !== null) {
            $request->setContent(Json::encode($request->getData(), $this->encodeOptions));
        }
        return $request;
    }
}