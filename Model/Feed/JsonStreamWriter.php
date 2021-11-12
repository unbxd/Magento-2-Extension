<?php
/**
 * Copyright (c) 2020 Unbxd Inc.
 */

/**
 * 
 * @author Jags
 * @email jagadeesh@oceaniasolution.com
 * @team Oceania
 */
namespace Unbxd\ProductFeed\Model\Feed;

use Unbxd\ProductFeed\Model\Serializer;

class JsonStreamWriter {


        const JSON_OBJECT_START = '{';
        const JSON_OBJECT_END = '}';
        const JSON_ARRAY_START = '[';
        const JSON_ARRAY_END = ']';
        const JSON_COLON = ':';
        const JSON_COMMA = ',';
        const JSON_DOUBLE_QUOTE = '"';

    /**
     * @var Serializer
     */
    private $serializer;


    public function __construct(Serializer $serializer){
        $this->serializer = $serializer;
    }

    public function pushFirstItem($item,$fileManager){

        $fileManager->writeStream($this->serializer->serializeToJson($item));
        return $this;

    }

    public function pushItem($item,$fileManager){

        $this->nextItem($fileManager);
        $fileManager->writeStream($this->serializer->serializeToJson($item));
        return $this;
    }

    public function openArray($fileManager){

        $fileManager->writeStream(self::JSON_ARRAY_START);
        return $this;
    }

    public function nextItem($fileManager){

        $fileManager->writeStream(self::JSON_COMMA);
        return $this;
    }

    public function closeArray($fileManager){

        $fileManager->writeStream(self::JSON_ARRAY_END);
        return $this;
    }

    public function openJsonObject($fileManager){

        $fileManager->writeStream(self::JSON_OBJECT_START);
        return $this;
    }

    public function closeJsonObject($fileManager){

        $fileManager->writeStream(self::JSON_OBJECT_END);
        return $this;
    }

    public function setAttribute($attributeName,$fileManager){
        $fileManager->writeStream(self::JSON_DOUBLE_QUOTE);
        $fileManager->writeStream($attributeName);
        $fileManager->writeStream(self::JSON_DOUBLE_QUOTE);
        $fileManager->writeStream(self::JSON_COLON);
        return $this;
    }




}