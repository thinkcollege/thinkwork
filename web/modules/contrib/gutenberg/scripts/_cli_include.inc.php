<?php

/**
 * @file
 * Gutenberg CLI script include.
 */

if (PHP_SAPI !== 'cli') {
  exit('Nothing to see here.');
}

global $_CLI_OPTIONS, $_POSITIONAL_ARGS;

// Positional arguments.
$_POSITIONAL_ARGS = [];

/**
 * Process the options.
 *
 * Use custom argument getter.
 *
 * @return array
 *   The argument options.
 */
function get_opt() {
  global $argv, $argc, $_POSITIONAL_ARGS;

  $options = [];
  for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if (strlen($arg) > 1 && $arg[0] === '-') {
      if ($arg[1] === '-') {
        if (strpos($arg, '=')) {
          /* Argument like --database=value */
          $explode = explode('=', $arg);
          $options[substr($explode[0], 2)] = $explode[1];
        }
        elseif ($i + 1 === $argc || $argv[$i + 1][0] === '-') {
          /* Argument like --flag */
          $options[substr($arg, 2)] = TRUE;
        }
        else {
          /* Argument like --database value */
          $i++;
          $options[substr($arg, 2, strlen($arg))] = $argv[$i];
        }
      }
      else {
        // Short argument.
        $options[$arg[1]] = substr($arg, 2, strlen($arg));
      }
    }
    else {
      $_POSITIONAL_ARGS[] = $arg;
    }
  }

  return $options;
}

/**
 * Help text.
 */
function help() {
  if (isset($GLOBALS['_GUTENBERG_HELP'])) {
    echo $GLOBALS['_GUTENBERG_HELP'];
  }
  else {
    fwrite(STDERR, "A help text variable (\$_GUTENBERG_HELP) was not defined. Please define one.\n");
  }
}

$_CLI_OPTIONS = get_opt();

if (isset($_CLI_OPTIONS['help']) || isset($_CLI_OPTIONS['h'])) {
  help();
  exit();
}
