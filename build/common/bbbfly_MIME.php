<?php
/*!
 * @author Jan Nejedly support@3b-fly.eu
 * @copyright Jan Nejedly
 * @version 1.0.0
 * @license see license in 'LICENSE' file
*/
?>
<?php
class bbbfly_MIME
{
  const BIN = 'application/octet-stream';

  const TEXT = 'text/plain';
  const HTML = 'text/html';
  const CSS = 'text/css';
  const JAVASCRIPT = 'application/javascript';

  const BMP = 'image/bmp';
  const PNG = 'image/png';
  const JPG = 'image/jpeg';
  const GIF = 'image/gif';
  const TIF = 'image/tiff';
  const SVG = 'image/svg+xml';
  const ICO = 'image/vnd.microsoft.icon';

  const PDF = 'application/pdf';
  const MSWORD = 'application/msword';
  const EXCEL = 'application/vnd.ms-excel';
  const WORD_DOCUMENT = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
  const WORD_TEMPLATE = 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
  const EXCEL_SHEET = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
  const EXCEL_TEMPLATE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.template';

  const JSON = 'application/json';
  const XML = 'application/xml';

  const ZIP = 'application/zip';
  const RAR = 'application/x-rar-compressed';
  const EXE = 'application/x-msdownload';
  const MSI = 'application/x-msdownload';

  const AVI = 'video/avi';
  const MP3 = 'video/mpeg';
  const MP4 = 'video/mpeg';
  const FLV = 'video/x-flv';
  const MOV = 'video/quicktime';
  const MKV = 'video/x-matroska';

  protected static function map(){
    return array(
      'txt' => self::TEXT,
      'htm' => self::HTML,
      'html' => self::HTML,
      'css' => self::CSS,
      'js' => self::JAVASCRIPT,
      'bmp' => self::BMP,
      'png' => self::PNG,
      'jpe' => self::JPG,
      'jpeg' => self::JPG,
      'jpg' => self::JPG,
      'gif' => self::GIF,
      'tiff' => self::TIF,
      'tif' => self::TIF,
      'svg' => self::SVG,
      'ico' => self::ICO,
      'pdf' => self::PDF,
      'doc' => self::MSWORD,
      'dot' => self::MSWORD,
      'xls' => self::EXCEL,
      'xlt' => self::EXCEL,
      'xla' => self::EXCEL,
      'docx' => self::WORD_DOCUMENT,
      'dotx' => self::WORD_TEMPLATE,
      'xlsx' => self::EXCEL_SHEET,
      'xltx' => self::EXCEL_TEMPLATE,
      'ini' => self::TEXT,
      'json' => self::JSON,
      'xml' => self::XML,
      'zip' => self::XML,
      'rar' => self::RAR,
      'exe' => self::EXE,
      'msi' => self::MSI,
      'avi' => self::AVI,
      'mp3' => self::MP3,
      'mp4' => self::MP4,
      'flv' => self::FLV,
      'mov' => self::MOV,
      'mkv' => self::MKV
    );
  }

  public static function supportedExtensions(){
    return array_keys(self::map());
  }

  public static function fromFileName($fileName){
    if(!is_string($fileName)){return null;}
    return self::fromExtension(pathinfo($fileName,PATHINFO_EXTENSION));
  }

  public static function fromExtension($ext){
    $extensions = self::map();
    return $extensions[$ext] ? $extensions[$ext] : self::BIN;
  }
}

