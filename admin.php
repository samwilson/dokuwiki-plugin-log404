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
 * The admin class handles the display and user-manipulation of the 404 log.
 */
class admin_plugin_log404 extends DokuWiki_Admin_Plugin {

    public function handle() {
        global $ID;
        if (isset($_GET['delete'])) {
            $log = $this->loadHelper('log404');
            $log->deleteRecord($_GET['delete']);
            msg(sprintf($this->getLang('deleted'), $_GET['delete']));
            send_redirect(wl($ID, array('do'=>'admin', 'page'=>$this->getPluginName()), true, '&'));
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
        $ignoreUrl = wl($ID, array('do'=>'admin', 'page'=>$this->getPluginName(), 'ignore'=>$id));
        $title = '<strong class="title">'.$data['count'].' <code>'.$id.'</code></strong> '
               . ' <a href="'.wl($id).'">'.$this->getLang('go-to-page').'</a>'
               . ' <a href="'.$delUrl.'">'.sprintf($this->getLang('delete'), $data['count']).'</a>'
               . ' <a href="'.$ignoreUrl.'">'.$this->getLang('ignore').'</a>'
               . '</span>';
        $out = $title.'<ol>';
        foreach ($data['hits'] as $hit) {
            $line = $hit['date'];
            if (!empty($hit['ip'])) {
                $line .= ' <em>'.$this->getLang('ip').'</em> '.$hit['ip'];
            }
            if (!empty($hit['referer'])) {
                $line .= ' <em>'.$this->getLang('referer').'</em> <a href="'.$hit['referer'].'">'.$hit['referer'].'</a>';
            }
            if (!empty($hit['user_agent'])) {
                $line .= ' <em>'.$this->getLang('user-agent').'</em> '.$hit['user_agent'];
            }
            // The line should never actually be empty, but still...
            if (!empty($line)) {
                $out .= "<li>$line</li>";
            }
        }
        $out .= '</ol>';
        return "<li>$out</li>";
    }

}
