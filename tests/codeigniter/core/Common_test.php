<?php

class Common_test extends CI_TestCase
{
    public function test_is_php()
    {
        $this->assertEquals(true, is_php('1.2.0'));
        $this->assertEquals(false, is_php('9999.9.9'));
    }

    // ------------------------------------------------------------------------

    public function test_stringify_attributes()
    {
        $this->assertEquals(' class="foo" id="bar"', _stringify_attributes(['class' => 'foo', 'id' => 'bar']));

        $atts = new stdClass();
        $atts->class = 'foo';
        $atts->id = 'bar';
        $this->assertEquals(' class="foo" id="bar"', _stringify_attributes($atts));

        $atts = new stdClass();
        $this->assertEquals('', _stringify_attributes($atts));

        $this->assertEquals(' class="foo" id="bar"', _stringify_attributes('class="foo" id="bar"'));

        $this->assertEquals('', _stringify_attributes([]));
    }

    // ------------------------------------------------------------------------

    public function test_stringify_js_attributes()
    {
        $this->assertEquals('width=800,height=600', _stringify_attributes(['width' => '800', 'height' => '600'], true));

        $atts = new stdClass();
        $atts->width = 800;
        $atts->height = 600;
        $this->assertEquals('width=800,height=600', _stringify_attributes($atts, true));
    }

    // ------------------------------------------------------------------------

    public function test_html_escape()
    {
        $this->assertEquals(
            html_escape('Here is a string containing "quoted" text.'),
            'Here is a string containing &quot;quoted&quot; text.'
        );
    }
}
