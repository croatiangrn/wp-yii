<?php
/**
 * @author: Marko Mikulic <mikulic.marko@spark.ba>
 */

namespace rnd\httpclient;

use rnd\base\BaseObject;
use rnd\helpers\Json;


/**
 * JsonParser parses HTTP message content as JSON.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 */
class JsonParser extends BaseObject implements ParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function parse(Response $response)
    {
        return Json::decode($response->getContent());
    }
}