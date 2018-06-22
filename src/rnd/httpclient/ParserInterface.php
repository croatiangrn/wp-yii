<?php
/**
 * @author: Marko Mikulic <mikulic.marko@spark.ba>
 */

namespace rnd\httpclient;


/**
 * ParserInterface represents HTTP response message parser.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 */
interface ParserInterface
{
    /**
     * Parses given HTTP response instance.
     * @param Response $response HTTP response instance.
     * @return array parsed content data.
     */
    public function parse(Response $response);
}