<?php
class ConfigLoader {

  // look for the specific ini file and load it if possible
  public static function loadConfiguration() {
    $filename = "config/credentials.ini";

    if (!file_exists($filename))
      throw new ConfigurationNotFoundException("Credentials not found! (File '$filename' was not found.)");
    else {
      $parameters = parse_ini_file($filename);

      $configuration = array(
        'email' => $parameters['email'],
        'password' => $parameters['password']
      );
      return $configuration;
    }
  }
}
?>
