<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Google\Cloud\Translate\TranslateClient;
//use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route;
//use Illuminate\Support\Facades\Route;
//use Illuminate\Routing\Controller;
use View;


use App\Quotation;
use App\Odb as OdbName;
use App\Ftdb as FtdbName;
use App\Tdb as TdbName;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

  public function upload(){


        $target_dir = "uploads/";
        $uploaded_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        $uploadOk = 1;
        $fileType = strtolower(pathinfo($uploaded_file,PATHINFO_EXTENSION));

        if($fileType != "json") {
           echo "Sorry, only Json files are allowed.";
           $uploadOk = 0;
        }
        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
           echo "Sorry, your file was not uploaded.";
        // if everything is ok, try to upload file
        } else {
           $uploaded_file = $target_dir . basename("sample.json");
           if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $uploaded_file)) {

               Controller:: process($uploaded_file);
               Controller::transfareData();
               Controller::downloadJson();
               return redirect()->route('done');
           } else {
               echo "Sorry, there was an error uploading your file.";
           }
        }

    }//Function

  public function process ($dir){

      $jsonFile = file_get_contents($dir);
      $jsonArray =  json_decode($jsonFile, true);

      $numberOfEntries=count($jsonArray);

      for ($i = 0 ; $i<$numberOfEntries ; $i++){

          $name = $jsonArray[$i]['names'];
          $hits = $jsonArray[$i]['hits'];

          $stringLen=strlen($name);
          $currPosName=2;
          $currPosHit=1;

          while ($currPosName!=1){

            $nextPosName=strpos($name, "\"" ,$currPosName);

            $nextPosHit=strpos($hits, "," ,$currPosHit);

            $currName=substr($name,$currPosName,$nextPosName-$currPosName);
            $currHit=(int)substr($hits,$currPosHit,$nextPosHit-$currPosHit);

            Controller::processForTranslation($currName);

            $entry = new OdbName;//New Entry to odb table
            $entry->name =$currName;
            $entry->hits =$currHit;
            $entry->idNumber = $i+1;
            $entry->save();

            $currPosName=strpos($name, "\"" ,$nextPosName+1)+1;
            $currPosHit=$nextPosHit+1;

          }

       }

    }

  public function processForTranslation($string){

      $sArray=explode(" ",$string);
      $sCharArray=array();
      $sCharArray= array('~','`','!','@','#','$','%','^','&','*','(',')','-','_','=','+','{','}','[',']','|' ,'/',':',';','"','\'','<','>', ',' , '.' , '?','\\' );
      $arrayLen=count($sArray);

      for ($i = 0 ; $i <$arrayLen ; $i++){
          $word=$sArray[$i];
          $sCharTotal=0;
          if (! FtdbName::find($word)){
            $entry=new FtdbName;
            $entry->word=$word;

            if (!Is_Numeric($word)){

                  //Check if it is an E-mail
                  if (strpos($word,"@") && strpos($word,".com")){$entry->translation=$word;}
                  else{
                    //Check if it contains only special character
                    for ($i= 0 ; $i <$arrayLen ; $i++){$sCharTotal+=substr_count($word,$sCharArray[$i]);}
                    if ($sCharTotal==$arrayLen){$entry->translation=$word;}

                    // if any thing else add to the database
                    else {$entry->translation=Controller::translate($word);}//Else
                  }//else

            }//If !numeric Data

            else {$entry->translation=$word;}
            $entry->save();

        }//If !Found

      }//For

  }//Function

  public function translate ($wordToTanslate){

        $arTran=Controller::connectToApi($wordToTanslate,'en','ar');
        $enTran=Controller::connectToApi($wordToTanslate,'ar','en');

        if ($enTran!=$wordToTanslate){$toDatabaseEntry=$enTran;}
        else {$toDatabaseEntry=$arTran;}

        return $toDatabaseEntry;

     }//Function

  public function connectToApi($word , $translateFrom , $translateTo){

    /////////////////////Please Paste The APIKey Here //////////////////////////
    /////////////////////Bookmark///////////////////////////////////////////////
     $apiKey = '';
     /////////////////////////////////////////////////////////////////////////////

     $url='https://www.googleapis.com/language/translate/v2?key=' . $apiKey . '&q='. rawurlencode($word) . '&source='.$translateFrom . '&target='.$translateTo;
     $handle = curl_init($url);
     curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
     $response = curl_exec($handle);
     $responseDecoded = json_decode($response, true);
     curl_close($handle);
     return $responseDecoded['data']['translations'][0]['translatedText'];

   }//Function

  public function transfareData(){

    $names = OdbName::all();
    $len = count($names);

    for ($i = 0 ; $i < $len ; $i++){

        $curName=$names[$i]->name;
        $curHit=$names[$i]->hits;
        $curId=$names[$i]->idNumber;

        $isFound=TdbName::find($curName);

        if (!$isFound){

          $separatedName=explode(" ",$curName);
          $translationComplete='';

          foreach ($separatedName as $word){

              $match = FtdbName::find($word);
              $translation=$match['translation'];//->translation;
              $translationComplete=$translationComplete . ' ' . $translation;

          }

          $entry = new TdbName;
          $entry -> name= $curName;
          $entry -> translation = $translationComplete;
          $entry -> hits=$curHit+1;
          $entry -> idNumber = $curId;
          $entry -> save();

        }//If ! found
        else {

            $isFound->hits ++;
            $isFound-> save();


        }//Else

    }//For


  }//function

  public function printResult (){

    echo "<form  action=\"/\" method=\"get\" enctype=\"multipart/form-data\">";
      echo "<input type=\"submit\" value=\"Home!\" name=\"home\">";
    echo "</form>";

    echo "<button type=\"button\" name=\"button\" style=\"margin:1%;\" ><a href=\"translation.json\" download>Download Json file </button>";

    $result =TdbName::all();

    $th="<th style=\" border: 1px solid black;text-align:center;\">";
    $th2="<th style=\" border: 3px solid black;text-align:center;\">";

    echo "<table style=\"border: 1px solid black;text-align:center;width: 100%;\">";
    echo " <tr>".$th2."Name</th>".$th2."Hits</th>".$th2."Translation</th>".$th2."IdNumber</th></tr>";

      foreach ($result as $entry) {

          echo "<tr>".$th . $entry->name . "</th>" .$th. $entry->hits. "</th>".$th. $entry->translation . "</th>".$th . $entry->idNumber . "</th></tr>";

      }//foreach

      echo "</table>";

   }//function

   public function downloadJson(){

     $jsonEncoded =json_encode(TdbName::all());
     $fileLocation='translation.json';
     $jsonFile = fopen($fileLocation, "w") or die("Unable to open file!");
     fwrite($jsonFile, $jsonEncoded);
     fclose($jsonFile);

   }//Function

   public function reset (){

      DB::table('OdbName')->truncate();
      DB::table('FtdbName')->truncate();
      DB::table('TdbName')->truncate();
      return redirect()->route('home');

   }

  }//Class
