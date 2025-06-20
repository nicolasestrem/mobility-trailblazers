<?php
/**
 * Service Interface
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Interfaces;

interface MT_Service_Interface {
    public function process($data);
    public function validate($data);
    public function get_errors();
}