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
        unlink($log->filename());

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
}
