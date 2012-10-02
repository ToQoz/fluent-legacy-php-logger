<?php

require dirname(__FILE__) . "/../src/FluentLogger/FluentMsgFormatter.php";

class FluentMsgFormatterTest extends PHPUnit_Framework_TestCase
{
    public function test_for_forward()
    {
        $tag_name = "tag_str";
        $data_array = array( "foo" => "bar");
        $json_msg = FluentMsgFormatter::for_forward(
            $tag_name, $data_array
        );

        $unpacked = json_decode($json_msg, true);
        $this->assertEquals($tag_name, $unpacked[0]);
        $this->assertEquals($data_array["foo"], $unpacked[2]["foo"]);
    }

}
