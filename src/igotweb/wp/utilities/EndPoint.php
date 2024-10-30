<?php
/**
 * Used by RewriteUtils.
 *
 * @since      1.0.0
 * @package    igotweb\wp\utilities
 * @author     Nicolas Igot <nicolas@igot-web.com>
 */

namespace igotweb_wp_mp_links\igotweb\wp\utilities;

class EndPoint {
    private $path;
    private $callback;
    private $level;

    function __construct(string $path, callable $callback, int $level = EP_ROOT) {
        $this->path = $path;
        $this->callback = $callback;
        $this->level = $level;
    }

    public function setPath(string $path) {
        $this->path = $path;
    }
    public function getPath():string {
        return $this->path;
    }

    public function setCallback(callable $callback) {
        $this->callback = $callback;
    }
    public function getCallback():callable {
        return $this->callback;
    }
    
    public function setLevel(int $level) {
        $this->level = $level;
    }
    public function getLevel():int {
        return $this->level;
    }
}

?>