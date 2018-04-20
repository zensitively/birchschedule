<?php

function birch_sabre_autoload($className) {
  $classPath = explode('\\', $className);
  if ($classPath[0] != 'Sabre') {
    return;
  }
  array_shift($classPath);
  $filePath = dirname(__FILE__) . '/' . implode('/', $classPath) . '.php';
  if (file_exists($filePath)) {
    require_once($filePath);
  }
}

spl_autoload_register('birch_sabre_autoload');
