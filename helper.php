<?php
/**
 * DokuWiki Plugin log404 (Helper Component)
 *
 * @license GPL 3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author  Sam Wilson <sam@samwilson.id.au>
 */
class helper_plugin_log404 extends DokuWiki_Plugin {

    /** @var array Loaded data */
    private $data;

    public function getMethods() {
        $methods = array();
        $methods[] = array(
            'name' => 'filename',
            'desc' => 'Get the filename of the log file used to store 404 data.',
            'params' => array(),
            'return' => array('filename' => 'string'),
        );
        $methods[] = array(
            'name' => 'getRecords',
            'desc' => 'Get a multi-dimensional array of 404 log data.',
            'params' => array(),
            'return' => array('records' => 'array'),
        );
        return $methods;
    }

    public function load() {
        $this->data = array();
        if (!file_exists($this->filename())) {
            return;
        }
        $log = fopen($this->filename(), 'r');
        while ($line = fgetcsv($log, 800)) { // Is 800 okay for max line length?
            if (!isset($this->data[$line[1]])) {
                $this->data[$line[1]] = array(
                    'count' => 0,
                    'hits' => array(),
                );
            }
            $this->data[$line[1]]['count'] ++;
            $this->data[$line[1]]['hits'][] = array(
                'date' => $line[0],
                'referer' => $line[2],
                'user_agent' => $line[3],
            );
        }
        uasort($this->data, array($this, 'compareCounts'));
    }

    public function save($id) {
        $datetime = date('Y-m-d H:i:s');
        $page = cleanID($id);
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $logline = $datetime.','.$page.',"'.$referer.'","'.$agent.'"'.PHP_EOL;
        if (!io_saveFile($this->filename(), $logline, true)) {
            msg("Unable to write log404 file.");
        }
    }

    public function getRecords() {
        return $this->data;
    }

    public function getRecord($id) {
        return (isset($this->data[$id])) ? $this->data[$id] : false;
    }

    public function filename() {
        global $conf;
        return fullpath($conf['metadir'].DIRECTORY_SEPARATOR.'log404.csv');
    }

    public function recordCount() {
        return count($this->getRecords());
    }

    protected function compareCounts($a, $b) {
        return $b['count'] - $a['count'];
    }

}

// vim:ts=4:sw=4:et:
