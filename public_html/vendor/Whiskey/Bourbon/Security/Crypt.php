<?php


namespace Whiskey\Bourbon\Security;


use InvalidArgumentException;
use Whiskey\Bourbon\Exception\Security\Crypt\AlgorithmNotSupportedException;
use Whiskey\Bourbon\Exception\Storage\CorruptedDataException;


/**
 * Crypt class
 * @package Whiskey\Bourbon\Security
 */
class Crypt
{


    protected $_default_key          = 'bd305e29843227105aa7c820ddef8e2a6b4c88831abd84a8702370d401b44245';
    protected $_encryption_algorithm = 'aes-256-cbc';
    protected $_algorithms           = [];


    /**
     * Set the default key
     * @param  string $key Key
     * @return bool        Whether the key was set
     */
    public function setDefaultKey($key = '')
    {

        $key = (string)$key;

        if ($key != '')
        {

            $this->_default_key = $key;

            return true;

        }

        return false;

    }


    /**
     * Check if an algorithm is supported
     * @param  string $algorithm Name of algorithm
     * @return bool              Whether the algorithm is supported
     */
    public function isAlgorithmSupported($algorithm = '')
    {

        if (empty($this->_algorithms))
        {
            $this->_algorithms = array_map('strtolower', hash_algos());
        }

        return in_array($algorithm, $this->_algorithms);

    }


    /**
     * Hash a string incorporating a salt (if not passed, the contents of
     * $this->_default_key will be used instead)
     * @param  string $string    String to hash
     * @param  string $salt      Optional salt
     * @param  string $algorithm Hash algorithm
     * @return string            Hashed string
     * @throws AlgorithmNotSupportedException if the algorithm is not supported
     */
    public function hash($string = '', $salt = null, $algorithm = 'sha512')
    {

        if (!$this->isAlgorithmSupported($algorithm))
        {
            throw new AlgorithmNotSupportedException('Unsupported algorithm \'' . $algorithm . '\'');
        }
    
        if (is_null($salt))
        {
            $salt = $this->_default_key;
        }
        
        return hash($algorithm, $salt . $string . $salt, false);
    
    }


    /**
     * Create a HMAC hash of a string
     * @param  string $string    String to hash
     * @param  string $salt      Optional salt
     * @param  string $algorithm Hash algorithm
     * @return string            Hashed string
     * @throws AlgorithmNotSupportedException if the algorithm is not supported
     */
    public function hashHmac($string = '', $salt = null, $algorithm = 'sha512')
    {

        if (!$this->isAlgorithmSupported($algorithm))
        {
            throw new AlgorithmNotSupportedException('Unsupported algorithm \'' . $algorithm . '\'');
        }

        if (is_null($salt))
        {
            $salt = $this->_default_key;
        }
        
        return hash_hmac($algorithm, $string, $salt, false);

    }


    /**
     * Encrypt a string, incorporating a custom key (if not passed, the
     * contents of $this->_default_key will be used instead)
     * @param  string $string String to encrypt
     * @param  string $key    Optional key
     * @return string         Encrypted string, Base64-encoded
     */
    public function encrypt($string = '', $key = null)
    {

        /*
         * Generate an IV
         */
        $iv_size = openssl_cipher_iv_length($this->_encryption_algorithm);
        $iv      = openssl_random_pseudo_bytes($iv_size);

        /*
         * Generate and trim the key
         */
        $key = ($key !== null) ? $key : $this->_default_key;
        $key = hash('sha512', $key);
        $key = mb_substr($key, 0, $iv_size);
        
        $result         = openssl_encrypt($string, $this->_encryption_algorithm, $key, OPENSSL_RAW_DATA, $iv);
        $encoded_result = base64_encode($result);
        
        /*
         * Return a package containing the encrypted string, the initialisation
         * vector and a HMAC hash of the result
         */
        return base64_encode(json_encode(
            [
                'result' => $encoded_result,
                'iv'     => base64_encode($iv),
                'hash'   => $this->hashHmac($encoded_result)
            ]));

    }


    /**
     * Decrypt a string, incorporating a custom key (if not passed, the
     * contents of $this->_default_key will be used instead)
     * @param  string $string String encrypted by _encrypt()
     * @param  string $key    Optional key
     * @return string         Decrypted string
     * @throws CorruptedDataException if the data has been tampered with
     * @throws InvalidArgumentException if the input string or key are not valid
     */
    public function decrypt($string = '', $key = null)
    {

        $iv_size = openssl_cipher_iv_length($this->_encryption_algorithm);
        $data    = base64_decode($string);

        /*
         * Generate and trim the key
         */
        $key = ($key !== null) ? $key : $this->_default_key;
        $key = hash('sha512', $key);
        $key = mb_substr($key, 0, $iv_size);

        if ($data = json_decode($data))
        {

            /*
             * Check that the hash matches
             */
            if ($data->hash !== $this->hashHmac($data->result))
            {
                throw new CorruptedDataException('Decryption error - data has been tampered with');
            }

            /*
             * Decrypt the string
             */
            $iv     = base64_decode($data->iv);
            $string = base64_decode($data->result);
            $result = openssl_decrypt($string, $this->_encryption_algorithm, $key, OPENSSL_RAW_DATA, $iv);

            /*
             * Return the result
             */
            return $result;

        }

        throw new InvalidArgumentException('Decryption error - invalid string or key');

    }


}