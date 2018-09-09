<?php
class bbbfly_MIME
{
  const BIN = 'application/octet-stream';

  const TEXT = 'text/plain';

  const HTML = 'text/html';
  const CSS = 'text/css';
  const JAVASCRIPT = 'application/javascript';

  const PDF = 'application/pdf';
  const MSWORD = 'application/msword';
  const WORD_DOCUMENT = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
  const WORD_DOCUMENT_TEMPLATE = 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
  const OPEN_TEXT = 'application/vnd.oasis.opendocument.text';
  const OPEN_TEXT_TEMPLATE = 'application/vnd.oasis.opendocument.text-template';

  const EXCEL = 'application/vnd.ms-excel';
  const EXCEL_SHEET = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
  const EXCEL_SHEET_TEMPLATE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.template';
  const OPEN_SHEET = 'application/vnd.oasis.opendocument.spreadsheet';
  const OPEN_SHEET_TEMPLATE = 'application/vnd.oasis.opendocument.spreadsheet-template';

  const MSPOWERPOINT = 'application/mspowerpoint';
  const OPEN_PRESENTATION = 'application/vnd.oasis.opendocument.presentation';
  const OPEN_PRESENTATION_TEMPLATE = 'application/vnd.oasis.opendocument.presentation-template';

  const JSON = 'application/json';
  const XML = 'application/xml';

  const BMP = 'image/bmp';
  const PNG = 'image/png';
  const JPG = 'image/jpeg';
  const GIF = 'image/gif';
  const TIF = 'image/tiff';
  const SVG = 'image/svg+xml';
  const ICO = 'image/vnd.microsoft.icon';

  const RAW = 'image/x-dcraw';
  const WAV = 'audio/x-wav';
  const WAVE = 'audio/wave';
  const WMA = 'audio/x-ms-wma';

  const AVI = 'video/avi';
  const MP3 = 'video/mpeg';
  const MP4 = 'video/mpeg';
  const FLV = 'video/x-flv';
  const MOV = 'video/quicktime';
  const MKV = 'video/x-matroska';

  const ZIP = 'application/zip';
  const RAR = 'application/x-rar-compressed';
  const EXE = 'application/x-msdownload';
  const MSI = 'application/x-msdownload';

  protected static function map(){
    return array(
      //plain
      'txt' => self::TEXT,
      'htm' => self::HTML,
      'html' => self::HTML,
      'css' => self::CSS,
      'js' => self::JAVASCRIPT,
      //document
      'pdf' => self::PDF,
      'doc' => self::MSWORD,
      'dot' => self::MSWORD,
      'docx' => self::WORD_DOCUMENT,
      'dotx' => self::WORD_DOCUMENT_TEMPLATE,
      'odt' => self::OPEN_TEXT,
      'ott' => self::OPEN_TEXT_TEMPLATE,
      //spreadsheet
      'xls' => self::EXCEL,
      'xlt' => self::EXCEL,
      'xla' => self::EXCEL,
      'xlsx' => self::EXCEL_SHEET,
      'xltx' => self::EXCEL_SHEET_TEMPLATE,
      'ods' => self::OPEN_SHEET,
      'ots' => self::OPEN_SHEET_TEMPLATE,
      //presentation
      'pps' => self::MSPOWERPOINT,
      'ppt' => self::MSPOWERPOINT,
      'odp' => self::OPEN_PRESENTATION,
      'otp' => self::OPEN_PRESENTATION_TEMPLATE,
      //data
      'ini' => self::TEXT,
      'json' => self::JSON,
      'xml' => self::XML,
      //image
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
      //sound
      'raw' => self::RAW,
      'wav' => self::WAV,
      'wave' => self::WAVE,
      'wma' => self::WMA,
      //video
      'avi' => self::AVI,
      'mp3' => self::MP3,
      'mp4' => self::MP4,
      'flv' => self::FLV,
      'mov' => self::MOV,
      'mkv' => self::MKV,
      //archive
      'zip' => self::XML,
      'rar' => self::RAR,
      'exe' => self::EXE,
      'msi' => self::MSI
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

