<?php

namespace App\traits;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

trait Encryptable
{
    public function getAttribute($key)
    {
        if (in_array($key, $this->encryptable)) {
            if (parent::getAttribute($key)) {
                try {
                    $decrypted = Crypt::decryptString(parent::getAttribute($key));
                } catch (DecryptException $exception) {
                    $decrypted = parent::getAttribute($key);
                }
                return $decrypted;
            }
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptable)) {
            $value = Crypt::encryptString($value);
        }

        return parent::setAttribute($key, $value);
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->encryptable)) {
                try {
                    $decrypted = Crypt::decryptString($value);
                } catch (DecryptException $exception) {
                    $decrypted = $value;
                }
                $attributes[$key] = $decrypted;
            }
        }

        return $attributes;
    }
}