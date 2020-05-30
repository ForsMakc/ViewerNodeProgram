<?php
namespace ViewerNodeNamespace;

class PocketData {

    //Заголовки узла представления
    const CONNECT = "CONNECT";
    const CLOSE = "CLOSE";
    const NODE = "NODE";
    const PROJECT = "PROJECT";
    const TEST = "TEST";

    //Заголовки сервера
    const OK = "OK";
    const FAIL = "FAIL";

    //Заголовки считывающего узла
    const DATA = "DATA";

    protected $pocket;

    function __construct($header = "") {
        $this->pocket = new Class($header) {
            public $header;
            public $structData;
            public $metaData;
            public $valuesData;
            public $keysMapData;
            public $binaryData;

            function __construct($header = "") {
                $this->header = $header;
                $this->structData = null;
                $this->metaData = null;
                $this->valuesData = null;
                $this->keysMapData = null;
                $this->binaryData = null;
            }
        };
    }

    function setPocket($pocketMsg) {
        $pocket = json_decode(trim($pocketMsg));
        $this->pocket->header = $pocket->header;
        $this->pocket->structData = $pocket->structData;
        $this->pocket->metaData = $pocket->metaData;
        $this->pocket->valuesData = $pocket->valuesData;
        $this->pocket->keysMapData = $pocket->keysMapData;
        $this->pocket->binaryData = $pocket->binaryData;
        return $this;
    }

    function getPocket($noEol = false) {
        $eol = (!$noEol) ? PHP_EOL . PHP_EOL : "";
        return json_encode($this->pocket) . $eol;
    }

    function getHeader() {
        return $this->pocket->header;
    }

    function getMetaData() {
        return $this->pocket->metaData;
    }

    function __toString() {
        return $this->getPocket();
    }

    public function getNodeId() {
        return $this->pocket->metaData->nodeId;
    }


}
