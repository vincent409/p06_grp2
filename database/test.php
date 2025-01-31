<?php

    // Hardcoded encryption key
    define('ENCRYPTION_KEY', '$w@pk3Y'); // Replace with your actual key

    // AES encryption function
    function aes_encrypt($data) {
        // Hardcoded key (hashed to 32 bytes)
        $key = hash('sha256', ENCRYPTION_KEY, true); // Hash the key to ensure it's 32 bytes long

        // Generate a random IV (Initialization Vector) for CBC mode
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        // Encrypt the data
        $ciphertext = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);

        // Combine the IV and ciphertext to store together
        $iv_and_ciphertext = base64_encode($iv . $ciphertext);

        return $iv_and_ciphertext;
    }

        // AES decryption function
        function aes_decrypt($encrypted_data) {
        // Hardcoded key (hashed to 32 bytes)
        $key = hash('sha256', ENCRYPTION_KEY, true); // Hash the key to ensure it's 32 bytes long

        // Decode the base64-encoded string (contains both IV and ciphertext)
        $data = base64_decode($encrypted_data);

        // Extract the IV and ciphertext
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $iv_length);
        $ciphertext = substr($data, $iv_length);

        // Decrypt the data
        $decrypted_data = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, 0, $iv);

        return $decrypted_data;
        }
        $text = "student@example.com";
        $en = aes_encrypt($text);
        echo $en;
        
    ?>
