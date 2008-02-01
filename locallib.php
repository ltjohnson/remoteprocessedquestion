<?php
/**
 * Library routines used by the ltjprocessed question type
 *
 * @copyright &copy; 2007 Leif Johnson
 * @author leif.t.johnson@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ltjprocessed
 */
function installed_server_choices() {
  return get_records_menu('question_ltjprocessed_servers', '', '', 'servername ASC', 'id, servername');
  }

function get_post_url_contents($url, $urlvars) {
  $crl = curl_init();
  curl_setopt($crl, CURLOPT_URL, $url);
  curl_setopt($crl, CURLOPT_HEADER, 0);
  curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($crl, CURLOPT_POST, 1);
  curl_setopt($crl, CURLOPT_POSTFIELDS, $urlvars);
  $ret = curl_exec($crl);
  curl_close($crl);
  return $ret;
}

function ltj_implode($arr) {
  $ret = array();
  ksort($arr);
  foreach($arr as $key => $value) {
    $ret[] = $key . '-' . str_replace(',', '\,', $value);
  }
  return implode(',', $ret);
}

function ltj_explode($str) {
  if ($str == '') {
    return array();
  }
  // split string on non-backslashed commas.
  $sp = preg_split('/(?<!\\\\)\,/', $str);
  $arr = array();
  foreach($sp as $sc) {
    list($key, $val) = explode('-', $sc, 2);
    $arr[$key] = str_replace('\,', ',', $val);
  }
  return $arr;
}

/* helper function for sorting */
function cmp_id($a, $b) {
  if ($a->id == $b->id) {
    return 0;
  }
  return ($a->id < $b->id) ? -1 : 1;
}

