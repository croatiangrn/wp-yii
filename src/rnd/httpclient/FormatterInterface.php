<?php
/**
 * @author: Marko Mikulic <mikulic.marko@spark.ba>
 */

namespace rnd\httpclient;


interface FormatterInterface
{
    /**
     * Formats given HTTP request message.
     * @param Request $request HTTP request instance.
     * @return Request formatted request.
     */
    public function format(Request $request);
}