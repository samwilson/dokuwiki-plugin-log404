<?php
/**
 * DokuWiki Plugin log404 (Admin Component)
 *
 * @license GPL 3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author  Sam Wilson <sam@samwilson.id.au>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

/**
 * 
 */
class admin_plugin_log404 extends DokuWiki_Admin_Plugin {

    public function handle() {
        global $ID;
        if (isset($_GET['delete'])) {
            $log = $this->loadHelper('log404');
            $log->deleteRecord($_GET['delete']);
            msg("Records for ".$_GET['delete']." have been removed from the 404 log.");
            send_redirect(wl($ID, array('do'=>'admin', 'page'=>$this->getPluginName()), TRUE, '&'));
        }
    }

    public function html() {
        ptln('<h1>'.$this->getLang('menu').'</h1>');
        $log = $this->loadHelper('log404');
        $log->load();
        ptln('<ol class='.$this->getPluginName().'>');
        foreach ($log->getRecords() as $id => $detail) {
            ptln($this->getHtml($id, $detail));
        }
        ptln('</ol>');
    }

    protected function getHtml($id, $data) {
        global $ID;
        $delUrl = wl($ID, array('do'=>'admin', 'page'=>$this->getPluginName(), 'delete'=>$id));
        $title = '<strong class="title">'.$data['count'].' <code>'.$id.'</code></strong> '
               . ' <a href="'.wl($id).'">[Go to page]</a>'
               . ' <a href="'.$delUrl.'">[Delete '.$data['count'].' log entries]</a>'
               . ' <a href="'.$delUrl.'">[Add to <em>ignore list</em>]</a>'
               . '</span>';
        $out = $title.'<ol>';
        foreach ($data['hits'] as $hit) {
            $line = $hit['date'];
            if (!empty($hit['referer'])) {
                $line .= ' <em>Referer:</em> <a href="'.$hit['referer'].'">'.$hit['referer'].'</a>';
            }
            if (!empty($hit['user_agent'])) {
                $line .= ' <em>User Agent:</em> '.$hit['user_agent'];
            }
            $out .= "<li>$line</li>";
        }
        $out .= '</ol>';
        return "<li>$out</li>";
    }

}
