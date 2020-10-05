<?php
/**
 * Copyright (c) 2020 Unbxd Inc.
 */

/**
 * Init development:
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace Unbxd\ProductFeed\Model;

/**
 * Wrapper for Serialize
 *
 * Class Serializer
 * @package Unbxd\ProductFeed\Model
 */
class Serializer
{
    /**#@+
     * Constants for build json string for big data
     */
    const JSON_OBJECT_START = '{';
    const JSON_OBJECT_END = '}';
    const JSON_ARRAY_START = '[';
    const JSON_ARRAY_END = ']';
    const JSON_COLON = ':';
    const JSON_COMMA = ',';
    const JSON_DOUBLE_QUOTE = '"';
    /**#@-*/

    /**
     * @var null|\Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Framework\Unserialize\Unserialize
     */
    private $unserialize;

    /**
     * Serializer constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Unserialize\Unserialize $unserialize
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Unserialize\Unserialize $unserialize
    ) {
        if (interface_exists(\Magento\Framework\Serialize\SerializerInterface::class)) {
            // for magento later then 2.2
            $this->serializer = $objectManager->get(\Magento\Framework\Serialize\SerializerInterface::class);
        }
        $this->unserialize = $unserialize;
    }

    /**
     * Serialize data into string
     *
     * @param $data
     * @return false|string
     */
    public function serialize($data)
    {
        if ($this->serializer === null) {
            $result = json_encode($data);
            if (false === $result) {
                throw new \InvalidArgumentException(
                    'Unable to serialize value. Error: ' . json_last_error_msg()
                );
            }

            return $result;
        }

        try {
            return $this->serializer->serialize($data);
        } catch (\InvalidArgumentException $e) {
            return serialize($data);
        }
    }

    /**
     * Unserialize the given string
     *
     * @param $data
     * @return mixed
     */
    public function unserialize($data)
    {
        if ($this->serializer === null) {
            $result = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException(
                    'Unable to unserialize value. Error: ' . json_last_error_msg()
                );
            }

            return $result;
        }

        try {
            return $this->serializer->unserialize($data);
        } catch (\InvalidArgumentException $e) {
            return unserialize($data);
        }
    }

    /**
     * Alternative to json_encode() to handle big arrays
     * Regular json_encode would return NULL due to memory issues.
     *
     * @param mixed $value
     * @return string
     */
    public function serializeToJson(&$value)
    {
        if (is_array($value)) {
            return $this->encodeArray($value);
        }

        return $this->encodeBasicDataType($value);
    }

    /**
     * JSON encode an array value.
     * Recursively encodes each value of an array and returns a JSON encoded array string.
     *
     * @param array $array
     * @return string
     */
    private function encodeArray(&$array)
    {
        $tmpArray = [];
        // check for associative array
        if (!empty($array) && (array_keys($array) !== range(0, count($array) - 1))) {
            // associative array
            $result = self::JSON_OBJECT_START;
            foreach ($array as $key => $value) {
                $key = (string) $key;
                $tmpArray[] = $this->encodeString($key) . self::JSON_COLON . $this->serializeToJson($value);
            }
            $result .= implode(self::JSON_COMMA, $tmpArray);
            $result .= self::JSON_OBJECT_END;
        } else {
            // indexed array
            $result = self::JSON_ARRAY_START;
            $length = count($array);
            for ($i = 0; $i < $length; $i++) {
                $tmpArray[] = $this->serializeToJson($array[$i]);
            }
            $result .= implode(self::JSON_COMMA, $tmpArray);
            $result .= self::JSON_ARRAY_END;
        }

        return $result;
    }

    /**
     * JSON encode a basic data type (string, number, boolean, null)
     * If value type is not a string, number, boolean, or null, the string
     * 'null' is returned.
     *
     * @param  mixed $value
     * @return string
     */
    private function encodeBasicDataType(&$value)
    {
        $result = 'null';
        if (is_int($value) || is_float($value)) {
            $result = (string) $value;
            $result = str_replace(',', '.', $result);
        } elseif (is_string($value)) {
            $result = $this->encodeString($value);
        } elseif (is_bool($value)) {
            $result = $value ? 'true' : 'false';
        }

        return $result;
    }

    /**
     * JSON encode a string value by escaping characters as necessary
     *
     * @param string $string
     * @return string
     */
    private function encodeString(&$string)
    {
        // escape these characters with a backslash or unicode escape:
        // " \ / \n \r \t \b \f
        $search  = ['\\', "\n", "\t", "\r", "\b", "\f", '"', '\'', '&', '<', '>', '/'];
        $replace = ['\\\\', '\\n', '\\t', '\\r', '\\b', '\\f', '\\u0022', '\\u0027', '\\u0026',  '\\u003C', '\\u003E', '\\/'];
        $string  = str_replace($search, $replace, $string);

        // escape certain ASCII characters:
        // 0x08 => \b
        // 0x0c => \f
        $string = str_replace([chr(0x08), chr(0x0C)], ['\b', '\f'], $string);
        $this->encodeUnicodeString($string);
        return self::JSON_DOUBLE_QUOTE . $string . self::JSON_DOUBLE_QUOTE;
    }

    public function encodeUnicodeString($value)
    {
        $strlen_var = strlen($value);
        $ascii = "";

        /**
         * Iterate over every character in the string,
         * escaping with a slash or encoding to UTF-8 where necessary
         */
        for ($i = 0; $i < $strlen_var; $i++) {
            $ord_var_c = ord($value[$i]);

            switch (true) {
                case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                    // characters U-00000000 - U-0000007F (same as ASCII)
                    $ascii .= $value[$i];
                    break;

                case (($ord_var_c & 0xE0) == 0xC0):
                    // characters U-00000080 - U-000007FF, mask 110XXXXX
                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                    $char = pack('C*', $ord_var_c, ord($value[$i + 1]));
                    $i += 1;
                    $utf16 = $this->utf82utf16($char);
                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                    break;

                case (($ord_var_c & 0xF0) == 0xE0):
                    // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                    $char = pack(
                        'C*',
                        $ord_var_c,
                        ord($value[$i + 1]),
                        ord($value[$i + 2])
                    );
                    $i += 2;
                    $utf16 = $this->utf82utf16($char);
                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                    break;

                case (($ord_var_c & 0xF8) == 0xF0):
                    // characters U-00010000 - U-001FFFFF, mask 11110XXX
                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                    $char = pack(
                        'C*',
                        $ord_var_c,
                        ord($value[$i + 1]),
                        ord($value[$i + 2]),
                        ord($value[$i + 3])
                    );
                    $i += 3;
                    $utf16 = $this->utf82utf16($char);
                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                    break;

                case (($ord_var_c & 0xFC) == 0xF8):
                    // characters U-00200000 - U-03FFFFFF, mask 111110XX
                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                    $char = pack(
                        'C*',
                        $ord_var_c,
                        ord($value[$i + 1]),
                        ord($value[$i + 2]),
                        ord($value[$i + 3]),
                        ord($value[$i + 4])
                    );
                    $i += 4;
                    $utf16 = $this->utf82utf16($char);
                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                    break;

                case (($ord_var_c & 0xFE) == 0xFC):
                    // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                    // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                    $char = pack(
                        'C*',
                        $ord_var_c,
                        ord($value[$i + 1]),
                        ord($value[$i + 2]),
                        ord($value[$i + 3]),
                        ord($value[$i + 4]),
                        ord($value[$i + 5])
                    );
                    $i += 5;
                    $utf16 = $this->utf82utf16($char);
                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                    break;
            }
        }
        return $ascii;
    }
    public function utf82utf16($utf8)
    {
        // Check for mb extension otherwise do by hand.
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        }
        switch (strlen($utf8)) {
            case 1:
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return $utf8;

            case 2:
                // return a UTF-16 character from a 2-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x07 & (ord($utf8[0]) >> 2))
                     . chr((0xC0 & (ord($utf8[0]) << 6))
                         | (0x3F & ord($utf8[1])));

            case 3:
                // return a UTF-16 character from a 3-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr((0xF0 & (ord($utf8[0]) << 4))
                         | (0x0F & (ord($utf8[1]) >> 2)))
                     . chr((0xC0 & (ord($utf8[1]) << 6))
                         | (0x7F & ord($utf8[2])));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }
}
