<?php
  require_once(dirname(__FILE__).'/geoPHP_1.2/geoPHP.php');

  class bbbfly_geoPHP extends geoPHP
  {
    public static function geoJSONToWKT($json){
      if(!is_string($json) && !is_object($json)){return null;}

      try{
        $geom = self::load($json,'geojson');
        if($geom){return $geom->out('wkt');}
      }catch(Exception $e){}

      return null;
    }

    public static function WKTToGeoJSON($wkt){
      if(!is_string($wkt)){return null;}
      $json = null;

      try{
        $geom = self::load($wkt,'wkt');
        if($geom){$json = json_decode($geom->out('geojson'));}
      }catch(Exception $e){}

      if(!$json){return null;}

      $featues = array();
      if($json->type === 'GeometryCollection'){
        foreach($json->geometries as $geometry){
          $featues[] = self::geometryToGeoJSONFeature($geometry);
        }
      }
      else{
        $featues[] = self::geometryToGeoJSONFeature($json);
      }

      $collection = new stdClass();
      $collection->type = 'FeatureCollection';
      $collection->features = $featues;
      return $collection;
    }

    protected static function geometryToGeoJSONFeature($geometry){
      $feature = new stdClass();
      $feature->type = 'Feature';
      $feature->properties = new stdClass();
      $feature->geometry = $geometry;
      return $feature;
    }
  }