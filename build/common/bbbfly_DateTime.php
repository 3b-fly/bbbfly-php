<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/

class bbbfly_DateTime extends DateTime
{
  const BI_CALENDAR_GREGORIAN = 1;

  public function __construct($time='now'){
    parent::__construct($time,new DateTimeZone('UTC'));
  }

  public function setBigInt($biString){
    if(!is_string($biString)){return false;}

    $pattern = array(
      'bc' => '(-?)',
      'cal' => '([1-8]{1})',
      'year' => '([0-9]{10})',
      'month' => '([0-9]{2})',
      'day' => '([0-9]{2})',
      'hour' => '([0-9]{2})',
      'minute' => '([0-9]{2})'
    );
    $pattern = '~^'.implode('',$pattern).'$~';

    $parts = array();
    if(!preg_match($pattern,$biString,$parts)){return false;}

    switch($parts[2]){
      case self::BI_CALENDAR_GREGORIAN:
        $errorLevel = error_reporting(0);
        $this->setTimezone('UTC');
        error_reporting($errorLevel);

        $year = (double)$parts[3];
        $month = (int)$parts[4];
        $day = (int)$parts[5];
        $hour = (int)$parts[6];
        $minute = (int)$parts[7];

        if($parts[1]){$year *= -1;}

        $this->setDate($year,$month,$day);
        $this->setTime($hour,$minute,0);
      return true;
    }
    return false;
  }

  public function toBigInt(){
    $calendar = self::BI_CALENDAR_GREGORIAN;

    $year = $this->format('Y');
    $before = (substr($year,0,1) === '-') ? '-' : '';
    if($before){$year = substr($year,1);}
    $year = sprintf("%010d",$year);

    return $before.$calendar.$year.$this->format('mdHi');
  }
}