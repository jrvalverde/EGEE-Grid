use Crypt::CBC;
  $cipher = Crypt::CBC->new( -key    => 'my secret key',
                             -cipher => 'Blowfish'
                            );

  $ciphertext = $cipher->encrypt("This data is hush hush");
  $plaintext  = $cipher->decrypt($ciphertext);
#  print $ciphertext;
  print $plaintext;
exit;
  $cipher->start('encrypting');
  open(F,"./BIG_FILE");
  while (read(F,$buffer,1024)) {
      print $cipher->crypt($buffer);
  }
  print $cipher->finish;

  # do-it-yourself mode -- specify key, initialization vector yourself
  $key    = Crypt::CBC->random_bytes(8);  # assuming a 8-byte block cipher
  $iv     = Crypt::CBC->random_bytes(8);
  $cipher = Crypt::CBC->new(-literal_key => 1,
                            -key         => $key,
                            -iv          => $iv,
                            -header      => 'none');

  $ciphertext = $cipher->encrypt("This data is hush hush");
  $plaintext  = $cipher->decrypt($ciphertext);

  # RANDOMIV-compatible mode
  $cipher = Crypt::CBC->new(-key         => 'Super Secret!'
                            -header      => 'randomiv');
