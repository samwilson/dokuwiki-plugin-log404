<?php

/**
 * DokuWiki Plugin log404 (Action Component)
 *
 * @license GPL 3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author  Sam Wilson <sam@samwilson.id.au>
 */
// must be run within Dokuwiki
if (!defined('DOKU_INC'))
    die();

class action_plugin_log404 extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for the PREPROCESS event.
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'AFTER', $this, 'handle');
    }

    /**
     * Write to the 404 log file if this is a non-existant page
     *
     * @param Doku_Event $event  Not used
     * @param mixed      $param  Not used
     * @return void
     */
    public function handle(Doku_Event &$event, $param) {
        global $INFO, $ACT, $ID;
        $validActions = array('show', 'notfound');
        if ($INFO['exists'] || !in_array($ACT, $validActions)) {
            return;
        }
        $log = $this->loadHelper('log404');
        $log->save($ID);
    }

}

// vim:ts=4:sw=4:et:
