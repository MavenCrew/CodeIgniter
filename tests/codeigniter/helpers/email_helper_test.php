<?php

class Email_helper_test extends CI_TestCase
{
    public function set_up()
    {
        $this->helper('email');
    }

    public function test_valid_email()
    {
        $this->assertEquals(false, valid_email('test'));
        $this->assertEquals(false, valid_email('test@test@test.com'));
        $this->assertEquals(true, valid_email('test@test.com'));
        $this->assertEquals(true, valid_email('my.test@test.com'));
    }
}
