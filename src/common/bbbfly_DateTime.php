<?php
class bbbfly_DateTime extends DateTime
{
  const BI_CALENDAR_GREGORIAN = 1;

  protected static $biTimezone = null;

  public function __construct($datetime='now',$timezone=null){
    self::$biTimezone = new DateTimeZone('UTC');

    if(!($timezone instanceof DateTimeZone)){
      $timezone = self::$biTimezone;
    }

    parent::__construct($datetime,$timezone);
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
        $timeZone = $this->getTimezone();
        $this->setTimezone(self::$biTimezone);
        error_reporting($errorLevel);

        $year = (double)$parts[3];
        $month = (int)$parts[4];
        $day = (int)$parts[5];
        $hour = (int)$parts[6];
        $minute = (int)$parts[7];

        if($parts[1]){$year *= -1;}

        $this->setDate($year,$month,$day);
        $this->setTime($hour,$minute,0);

        $errorLevel = error_reporting(0);
        $this->setTimezone($timeZone);
        error_reporting($errorLevel);
      return true;
    }
    return false;
  }

  public function toBigInt(){
    $calendar = self::BI_CALENDAR_GREGORIAN;

    $errorLevel = error_reporting(0);
    $timeZone = $this->getTimezone();
    $this->setTimezone(self::$biTimezone);
    error_reporting($errorLevel);

    $year = $this->format('Y');
    $before = (substr($year,0,1) === '-') ? '-' : '';
    if($before){$year = substr($year,1);}
    $year = sprintf("%010d",$year);

    $bi = $before.$calendar.$year.$this->format('mdHi');

    $errorLevel = error_reporting(0);
    $this->setTimezone($timeZone);
    error_reporting($errorLevel);

    return $bi;
  }
}