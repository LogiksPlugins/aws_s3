<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!function_exists("uploadPhotoToS3")) {
    include_once __DIR__."/s3.php";

    if(!defined('AWS_ACCESS_KEY')) define('AWS_ACCESS_KEY', '');
    if(!defined('AWS_SECRET_KEY')) define('AWS_SECRET_KEY', '');
    if(!defined('AWS_S3_TARGETURI')) define('AWS_S3_TARGETURI', "https://s3.us-east-2.amazonaws.com/");
    if(!defined('AWS_S3_EXPIRY')) define('AWS_S3_EXPIRY', 100);
    
    $_ENV['TEMPDIR'] = __DIR__."/tmp/";
    $_ENV['POSTDATAPARAM'] = "data";
    
    function getS3FileLink($fileName, $bucket = 'production', $folder = '', $S3Config = false) {
        if(!$S3Config) {
            $S3Config = [];
        }
        
        $S3Config = array_merge([
              		"accessKeyId"=> AWS_ACCESS_KEY, 
            	    "secretAccessKey"=> AWS_SECRET_KEY,
            	    "bucket"=>$bucket,
            	    "folder"=>$folder,
            	    //"bucket_security_policy"=> S3::ACL_PUBLIC_READ,
            	    "security_policy"=> S3::ACL_PUBLIC_READ,
            	    "targetURI"=>AWS_S3_TARGETURI,
              	], $S3Config);
              	
        S3::$useSSL = false;
        $s3 = new S3($S3Config['accessKeyId'], $S3Config['secretAccessKey'], false);
        
        $fileURI = $fileName;
        if($folder && strlen($folder)>0) {
            $fileURI = $folder."/".$fileURI;
        }
        
        return $s3->getAuthenticatedURL($bucket, $fileURI,AWS_S3_EXPIRY);
    }
    
    function getS3FileData($fileName, $bucket = 'production', $folder = '', $S3Config = false) {
        if(!$S3Config) {
            $S3Config = [];
        }
        
        $S3Config = array_merge([
              		"accessKeyId"=> AWS_ACCESS_KEY, 
            	    "secretAccessKey"=> AWS_SECRET_KEY,
            	    "bucket"=>$bucket,
            	    "folder"=>$folder,
            	    //"bucket_security_policy"=> S3::ACL_PUBLIC_READ,
            	    "security_policy"=> S3::ACL_PUBLIC_READ,
            	    "targetURI"=>AWS_S3_TARGETURI,
              	], $S3Config);
              	
        S3::$useSSL = false;
        $s3 = new S3($S3Config['accessKeyId'], $S3Config['secretAccessKey'], false);
        
        $fileURI = $fileName;
        if($folder && strlen($folder)>0) {
            $fileURI = $folder."/".$fileURI;
        }
        
        return $s3->getObject($bucket, $fileURI);
    }
    
    function uploadFileToS3($uploadFile, $finalFileName, $bucket = 'production', $folder = '', $mime = "application/pdf", $S3Config = false) {
        if(!$S3Config) {
            $S3Config = [];
        }
        
        $S3Config = array_merge([
              		"accessKeyId"=> AWS_ACCESS_KEY, 
            	    "secretAccessKey"=> AWS_SECRET_KEY,
            	    "bucket"=>$bucket,
            	    "folder"=>$folder,
            	    //"bucket_security_policy"=> S3::ACL_PUBLIC_READ,
            	    "security_policy"=> S3::ACL_PUBLIC_READ,
            	    "targetURI"=>AWS_S3_TARGETURI,
              	], $S3Config);
        //printArray($S3Config);
        S3::$useSSL = false;
        $s3 = new S3($S3Config['accessKeyId'], $S3Config['secretAccessKey'], false);

        // using v4 signature
        //$s3->setSignatureVersion('v4');
      
        // List your buckets:
        //echo "S3::listBuckets(): ".print_r($s3->listBuckets(), 1)."\n";exit();
      
        $bucketList = $s3->listBuckets();
        if(!in_array($bucket,$bucketList)) {
            if(!$s3->putBucket($bucket, $S3Config['bucket_security_policy'])) {
                exit("Sorry, Storage Initiation failed");
            }
        }
        $bucketList = $s3->listBuckets();
        //var_dump($bucketList);
        
        if($S3Config['folder'] && strlen($S3Config['folder'])>0) {
            $uploadPath="{$S3Config['folder']}/".$finalFileName;
        } else {
            $uploadPath=$finalFileName;
        }
        
        $b = $s3->putObjectFile($uploadFile, $S3Config['bucket'], $uploadPath, $S3Config['security_policy']);
    
        $photoURI='https://s3.amazonaws.com/'.$S3Config['bucket'].'/'.$uploadPath;
        
        //unlink($uploadFile);
          
        if($b) {
            return $photoURI;
        } else {
            return false;
        }
    }
    
    function uploadEncodedPhotoToS3($S3Config) {
        $tempDir = $_ENV['TEMPDIR'];
        $POSTDATAPARAM = $_ENV['POSTDATAPARAM'];
    
        if(isset($_POST[$POSTDATAPARAM])) {
          $S3Params=$S3Config[$_GET['s3key']];
    
          if(isset($_POST['fname']) && strlen($_POST['fname'])>0) {
            $fname = $_POST['fname'];
          } else {
            $fname=md5(time().rand());
          }
          
          $finalFile = save_base64_image($_POST[$POSTDATAPARAM],$fname,$tempDir);
    
          S3::$useSSL = false;
          $s3 = new S3(AWS_ACCESS_KEY, AWS_SECRET_KEY);
    
          // using v4 signature
          $s3->setSignatureVersion('v4');
          
          // List your buckets:
          //echo "S3::listBuckets(): ".print_r($s3->listBuckets(), 1)."\n";exit();
    
          $bucketList = $s3->listBuckets();
          if(!in_array($S3Params['bucket'],$bucketList)) {
            if(!$s3->putBucket($S3Params['bucket'], $S3Params['bucket_security_policy'])) {
                exit("Sorry, Storage Initiation failed");
            }
          }
          //$bucketList = $s3->listBuckets();
          //print_r($bucketList);exit();
    
          if($_POST['folder'] && strlen($_POST['folder'])>0) {
            $uploadPath="{$_POST['folder']}/".$finalFile;
          } elseif($S3Params['folder'] && strlen($S3Params['folder'])>0) {
            $uploadPath="{$S3Params['folder']}/".$finalFile;
          } else {
            $uploadPath=$finalFile;
          }
          
          $b = $s3->putObjectFile($tempDir.$finalFile, $S3Params['bucket'], $uploadPath, $S3Params['security_policy']);
    
          $photoURI='https://s3.amazonaws.com/'.$S3Params['bucket'].'/'.$uploadPath;
          
          unlink($tempDir.$finalFile);
    
          if($b) {
            return $photoURI;
          } else {
            die("Error uploading image to cloud target");
          }
        } else {
          die("Image Data Missing or Error Configuring Post Paramters");
        }
      }
      
      function save_base64_image($base64_image_string, $output_file_without_extension, $path_with_end_slash="" ) {
          //usage:  if( substr( $img_src, 0, 5 ) === "data:" ) {  $filename=save_base64_image($base64_image_string, $output_file_without_extentnion, getcwd() .     "/application/assets/pins/$user_id/"); }      
          //
          //data is like:    data:image/png;base64,asdfasdfasdf
          $splited = explode(',', substr( $base64_image_string , 5 ) , 2);
          $mime=$splited[0];
          $data=$splited[1];
    
          $mime_split_without_base64=explode(';', $mime,2);
          $mime_split=explode('/', $mime_split_without_base64[0],2);
          if(count($mime_split)==2)
          {
              $extension=$mime_split[1];
              if($extension=='jpeg') $extension='jpg';
              //if($extension=='javascript')$extension='js';
              //if($extension=='text')$extension='txt';
              $output_file_with_extension=$output_file_without_extension.'.'.$extension;
          }
          file_put_contents( $path_with_end_slash . $output_file_with_extension, base64_decode($data) );
          return $output_file_with_extension;
      }
}
?>
