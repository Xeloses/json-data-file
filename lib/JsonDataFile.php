<?php

/*
 * JSON data file storage.
 *
 * @author     Xeloses (https://github.com/Xeloses)
 * @package    JsonDataFile (https://github.com/Xeloses/json-data-file)
 * @version    1.0.2
 * @copyright  Xeloses 2018-2020
 * @license    MIT (http://en.wikipedia.org/wiki/MIT_License)
 */

namespace Xeloses\JsonDataFile;

use Xeloses\JsonDataFile\Exceptions\JsonDataFileException;

/**
 * JsonDataFile class.
 *
 * @package JsonDataFile
 *
 * @method void   save()
 * @method bool   has(string $name)
 * @method bool   arrayHas(string $array_name, mixed $value)
 * @method bool   arrayHasKey(string $array_name, string|int $key)
 * @method mixed  get(string $name, ?mixed $default)
 * @method mixed  arrayGet(string $array_name, string|int $key, ?mixed $default)
 * @method void   set(string $name, mixed $value)
 * @method void   arrayAdd(string $array_name, mixed $value, ?string|int $key)
 * @method void   arraySet(string $array_name, mixed $value, ?string|int $key)
 * @method void   remove(string $name)
 * @method void   arrayRemove(string $name, string|int $key)
 * @method void   arrayRemoveValue(string $name, $value)
 * @method string getFilename()
 * @method void   setOption(string $name, bool $value)
 *
 * Note: methods "array*()" are usable to avid "Indirect modification" error.
 */
class JsonDataFile
{
    /**
     * Data.
     *
     * @var object
     */
    protected $data = null;

    /**
     * Data storage file.
     *
     * @var string
     */
    protected $filename = null;

    /**
     * Options.
     *
     * @var array
     */
    protected $options = [
        'encode_spec_chars' => true,
        'raw_text' => false,
    ];

    /**
     * Constructor.
     *
     * Reads stored data from file (if exists) or initializes object data with default dataset.
     *
     * @param string       $filename
     * @param array|object $default
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $filename, $default = [])
    {
        if(!$filename)
        {
            throw new \InvalidArgumentException('File name required.');
        }
        elseif(!is_file($filename))
        {
            if(!is_dir(dirname($filename)))
            {
                throw new \InvalidArgumentException('Invalid file path "'.dirname($filename).'\" (directory not exists).');
            }
            elseif(!preg_match('/^[\w\.\-\~]+$/',basename($filename)))
            {
                throw new \InvalidArgumentException('Invalid file name "'.basename($filename).'" (only english letters, numbers, underscore, dash and dot allowed).');
            }

            $this->data = is_object($default) ? $default : (is_array($default) ? (object)$default : null);
        }
        else
        {
            $this->data = json_decode(file_get_contents($filename),false,512,JSON_BIGINT_AS_STRING);
        }

        $this->filename = $filename;
    }

    /**
     * Save data to file.
     *
     * @return void
     *
     * @throws JsonDataFileException
     */
    public function save(): void
    {
        if(!$this->filename)
        {
            return;
        }

        $json = self::__toString();

        if(json_last_error() != JSON_ERROR_NONE)
        {
            throw new JsonDataFileException('Error attempt to save data: bad/corrupted data.');
        }

        if(file_put_contents($this->filename,$json) === false)
        {
            throw new JsonDataFileException('Error attempt to save data: could not write to file.');
        }
    }

    /**
     * Check member with specific name is exists in data.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->data && array_key_exists($name,$this->data);
    }

    /**
     * Check array member includes specified item.
     *
     * @param string $array_name
     * @param mixed  $value
     *
     * @return bool
     */
    public function arrayHas(string $array_name, $value): bool
    {
        return $this->data && $this->has($array_name) && !is_null($value) && is_array($this->data->{$array_name}) && in_array($value,$this->data->{$array_name});
    }

    /**
     * Check array member includes item with specified key.
     *
     * @param string     $array_name
     * @param string|int $key
     *
     * @return bool
     */
    public function arrayHasKey(string $array_name, $key): bool
    {
        return $this->data && $this->has($array_name) && !is_null($key) && is_array($this->data->{$array_name}) && array_key_exists($key,$this->data->{$array_name});
    }

    /**
     * Get value.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        if(!$this->has($name))
        {
            return $default;
        }

        return $this->data->{$name};
    }

    /**
     * Get item from array member of data.
     *
     * @param string     $array_name
     * @param string|int $key
     * @param mixed      $default
     *
     * @return mixed
     */
    public function arrayGet(string $array_name, $key, $default = null)
    {
        if(!$this->arrayHasKey($array_name,$key))
        {
            return $default;
        }

        return $this->data->{$array_name}[$key];
    }

    /**
     * Set value.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function set(string $name, $value = null): void
    {
        $this->data->{$name} = $value;
    }

    /**
     * Add new item in array member of data.
     *
     * @param string     $array_name
     * @param mixed      $value
     * @param string|int $key
     *
     * @return void
     */
    public function arrayAdd(string $array_name, $value, $key = null): void
    {
        if(!$this->data || (is_null($key) && is_null($value)) || (is_null($key) && $this->arrayHas($array_name,$value)) || (!is_null($key) && $this->arrayHasKey($array_name,$key)))
        {
            return;
        }

        if(!is_null($key))
        {
            $this->data->{$array_name}[$key] = $value;
        }
        else
        {
            $this->data->{$array_name}[] = $value;
        }
    }

    /**
     * Add or update item in array member of data.
     *
     * @param string     $array_name
     * @param mixed      $value
     * @param string|int $key
     *
     * @return void
     */
    public function arraySet(string $array_name, $value, $key = null): void
    {
        if(!$this->data || (is_null($key) && is_null($value)))
        {
            return;
        }

        if(!is_null($key))
        {
            $this->data->{$array_name}[$key] = $value;
        }
        else
        {
            $this->data->{$array_name}[] = $value;
        }
    }

    /**
     * Remove value.
     *
     * @param string $name
     *
     * @return void
     */
    public function remove(string $name): void
    {
        if($this->has($name))
        {
            unset($this->data->{$name});
        }
    }

    /**
     * Remove item from array member of data.
     *
     * @param string     $array_name
     * @param string|int $key
     *
     * @return void
     */
    public function arrayRemove(string $name, $key): void
    {
        if($this->arrayHasKey($array_name,$key))
        {
            unset($this->data->{$array_name}[$key]);
        }
    }

    /**
     * Remove specified item from array member of data.
     *
     * @param string $array_name
     * @param mixed  $value
     *
     * @return void
     */
    public function arrayRemoveValue(string $name, $value): void
    {
        if($this->has($array_name) && is_array($this->data->{$array_name}))
        {
            $n = array_search($value,$this->data->{$array_name});
            if($n !== false)
            {
                unset($this->data->{$array_name}[$n]);
            }
        }
    }

    /**
     * Handles dynamic get calls to the object.
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Set option.
     *
     * @param string $name
     * @param bool   $value
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function setOption(string $name, bool $value): void
    {
        if(!array_key_exists($name,$this->options))
        {
            throw new \InvalidArgumentException('Invalid option name.');
        }

        $this->options[$name] = $value;
    }

    /**
     * Handles dynamic get calls to the object.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Handles dynamic set calls to the object.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->set($name,$value);
    }

    /**
     * Handles dynamic attempts to convert object to string.
     *
     * @return string
     */
    public function __toString(): string
    {
        $opt = $this->options['raw_text'] ? JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE : ($this->options['encode_spec_chars'] ? JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT : 0);

        return json_encode($session,JSON_NUMERIC_CHECK|$opt);
    }
}
?>
