<?php

/**
 * @desc formattter for fluentd message
 */
class FluentMsgFormatter
{
    /**
     * @param $tag tag for fluentd
     * @param $data data for fluentd
     * @desc format data for type `forward`
     * @return "[ "tag", "timestamp", { log_data_hash } ]"
     */
    static public function for_forward($tag, $data)
    {
        return json_encode(array($tag, time(), $data));
    }
}

