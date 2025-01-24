<?php
namespace App\Component;

use App\UI\Accessory\ParameterBag;

class EncryptFacade
{
    private string $cipherAlgorithm = 'AES-256-CBC';

    public function __construct(
        private readonly ParameterBag $parameterBag,
    )
    {
    }

    /**
     * @param string $password
     * @return string
     */
    public function decrypt(string $password):string
    {
        $data = base64_decode($password);
        $ivLength = openssl_cipher_iv_length($this->cipherAlgorithm);
        $iv = substr($data, 0, $ivLength);
        $encryptedData = substr($data, $ivLength);
        return openssl_decrypt($encryptedData, $this->cipherAlgorithm, $this->parameterBag->encryptionKey, 0, $iv);
    }

    /**
     * @param string $password
     * @return string
     */
    public function encrypt(string $password):string
    {
        $ivLength = openssl_cipher_iv_length($this->cipherAlgorithm);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encryptedPassword = openssl_encrypt($password, $this->cipherAlgorithm, $this->parameterBag->encryptionKey, 0, $iv);
        return base64_encode($iv . $encryptedPassword);
    }
}