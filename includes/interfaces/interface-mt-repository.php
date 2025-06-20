<?php
/**
 * Repository Interface
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Interfaces;

interface MT_Repository_Interface {
    public function find($id);
    public function find_all($args = array());
    public function create($data);
    public function update($id, $data);
    public function delete($id);
}