<?php
namespace FatPanda\Illuminate\WordPress\Providers\Session;

use SessionHandlerInterface;
use WP_Session_Tokens;

/**
 * A Laravel session handler that reads and writes session data
 * through the transients API.
 */
class WordPressSessionHandler implements SessionHandlerInterface{

    /**
     * Lifetime of the session, in minutes.
     * @param int
     */
    protected $lifetime;

    public function __construct($lifetime)
    {
        $this->lifetime = $lifetime;
    }

    protected function key($sessionId)
    {
        return "_wp_session_{$sessionId}";
    }

    /**
     * Empty stub. Only useful for file-based session handling.
     */
    public function open($savePath, $sessionName) {}

	/**
	 * Empty stub. Only useful for file-based session handling.
	 */
    public function close() {}

    public function read($sessionId) 
    {   
        return get_transient($this->key($sessionId));
    }

    public function write($sessionId, $data) 
    {
        set_transient($this->key($sessionId), $data, $this->lifetime * 60);
    }

    public function destroy($sessionId) 
    {
        delete_transient($this->key($sessionId));
    }

    public function gc($lifetime) 
    {

    }
}