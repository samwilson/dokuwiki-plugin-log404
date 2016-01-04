<?php

/**
 * General tests for the log404 plugin
 *
 * @group plugin_log404
 * @group plugins
 */
class general_plugin_log404_test extends DokuWikiTest {

    protected $pluginsEnabled = array('log404');

    /**
     * Simple test to make sure the plugin.info.txt is in correct format
     */
    public function test_plugininfo() {
        $file = __DIR__.'/../plugin.info.txt';
        $this->assertFileExists($file);
        $info = confToHash($file);

        $this->assertArrayHasKey('base', $info);
        $this->assertArrayHasKey('author', $info);
        $this->assertArrayHasKey('email', $info);
        $this->assertArrayHasKey('date', $info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('desc', $info);
        $this->assertArrayHasKey('url', $info);

        $this->assertEquals('log404', $info['base']);
        $this->assertRegExp('/^https?:\/\//', $info['url']);
        $this->assertTrue(mail_isvalid($info['email']));
        $this->assertRegExp('/^\d\d\d\d-\d\d-\d\d$/', $info['date']);
        $this->assertTrue(false !== strtotime($info['date']));
    }

    /**
     * Test that the CSV is created and the appropriate record is written to it.
     */
    public function test_csv() {
        $log = plugin_load('helper', 'log404');

        // Make sure there's nothing there to start with
        $this->assertEquals(0, $log->recordCount());

        // Execute a GET request
        $request1 = new TestRequest();
        $request1->setServer('HTTP_USER_AGENT', 'An agent');
        $request1->get(array('id' => 'page-that-does-not-exist'));

        // Now there should be one record.
        $log->load();
        $this->assertFileExists($log->filename(), "File exists");
        $this->assertEquals(1, $log->recordCount());
        // Which should have one hit, from 'An agent'
        $record1 = $log->getRecord('page-that-does-not-exist');
        $this->assertEquals(1, $record1['count']);
        $this->assertEquals('An agent', $record1['hits'][0]['user_agent']);

        // Hit the same file again, and increment the count
        $request2 = new TestRequest();
        $request2->setServer('HTTP_USER_AGENT', 'An agent');
        $request2->get(array('id' => 'page-that-does-not-exist'));
        $log->load();
        $record2 = $log->getRecord('page-that-does-not-exist');
        $this->assertEquals(2, $record2['count']);
    }

    /**
     * Test that log records are ordered by hit count.
     */
    public function test_ordering() {
        $log = plugin_load('helper', 'log404');
        // Reset the log
        @unlink($log->filename());

        // Hit page A twice, then page B once, and page A will be first
        $request1 = new TestRequest();
        $request1->get(array('id' => 'a'));
        $request2 = new TestRequest();
        $request2->get(array('id' => 'a'));
        $request3 = new TestRequest();
        $request3->get(array('id' => 'b'));
        $log->load();
        $firstRecord = array_keys($log->getRecords());
        $this->assertEquals('a', array_shift($firstRecord));

        // Then add two more for B, and it should now be first
        $request4 = new TestRequest();
        $request4->get(array('id' => 'b'));
        $request5 = new TestRequest();
        $request5->get(array('id' => 'b'));
        $log->load();
        $newFirstRecord = array_keys($log->getRecords());
        $this->assertEquals('b', array_shift($newFirstRecord));
    }

    /**
     * Test that we can delete log entries, based on their page ID.
     */
    public function test_delete() {
        $log = plugin_load('helper', 'log404');
        // Reset the log
        @unlink($log->filename());
        // Create two requests for page A, then one for B
        $request1 = new TestRequest();
        $request1->get(array('id' => 'a'));
        $request2 = new TestRequest();
        $request2->get(array('id' => 'a'));
        $request3 = new TestRequest();
        $request3->get(array('id' => 'b'));
        // Then delete page A from the log
        $log->deleteRecord('a');
        // And check that it's not there, and that B is
        $this->assertFalse($log->getRecord('a'));
        $b = $log->getRecord('b');
        $this->assertEquals(1, $b['count']);
    }

    /**
     * Test that when deleting, multiple hits of non-deleted pages do remain.
     */
    public function test_delete_hits() {
        $log = plugin_load('helper', 'log404');
        // Reset the log
        @unlink($log->filename());
        // Create two requests for page A, then one for B, one for A, one for B.
        $request1 = new TestRequest();
        $request1->get(array('id' => 'a'));
        $request2 = new TestRequest();
        $request2->get(array('id' => 'a'));
        $request3 = new TestRequest();
        $request3->get(array('id' => 'b'));
        $request4 = new TestRequest();
        $request4->get(array('id' => 'a'));
        $request5 = new TestRequest();
        $request5->get(array('id' => 'b'));
        // Then delete page B from the log
        $log->deleteRecord('b');
        // And check that it's not there, and that A still has 3 hits
        $this->assertFalse($log->getRecord('b'));
        $a = $log->getRecord('a');
        $this->assertEquals(3, $a['count']);
    }

    /**
     * Test that deleting a record doesn't break other data in the log
     */
    public function test_delete_leaves_data() {
        $log = plugin_load('helper', 'log404');
        // Reset the log
        @unlink($log->filename());

        // Request a page, providing data
        $request1 = new TestRequest();
        $request1->setServer('HTTP_REFERER', 'Wherefrom');
        $request1->setServer('HTTP_USER_AGENT', 'An agent');
        $request1->get(array('id' => 'page-that-does-not-exist'));
        // Request and then delete another page
        $request2 = new TestRequest();
        $request2->get(array('id' => 'a'));
        $log->deleteRecord('a');

        // Check that our data remains
        $log->load();
        $a = $log->getRecord('page-that-does-not-exist');
        $this->assertEquals('Wherefrom', $a['hits'][0]['referer']);
        $this->assertEquals('An agent', $a['hits'][0]['user_agent']);
    }

    /**
     * Test that, if provided, an IP address is logged.
     * @link https://github.com/samwilson/dokuwiki-plugin-log404/issues/1
     */
    public function test_ip_address() {
        $log = plugin_load('helper', 'log404');
        // Reset the log
        @unlink($log->filename());

        // Request a page.
        $request1 = new TestRequest();
        //$request1->setServer('HTTP_USER_AGENT', '198.51.100.35');
        $request1->get(array('id' => 'page-that-does-not-exist'));

        // Check the log. This IP is set in _test/bootstrap.php
        $log->load();
        $a = $log->getRecord('page-that-does-not-exist');
        $this->assertEquals('87.142.120.6', $a['hits'][0]['ip']);
    }

}
