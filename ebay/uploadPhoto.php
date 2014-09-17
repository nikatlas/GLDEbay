<?php
/*
$img = image url to local file to upload 
$token = Ebay userToken!
*/
function uploadPhotoToEps($img ,$token){
	global $appID,$devID,$certID,$RuName,$serverUrl, $userToken,$compatabilityLevel, $siteID;
    //the token representing the eBay user to assign the call with
    $userToken = $token;

    $siteID  = 0;                            // siteID needed in request - US=0, UK=3, DE=77...
    $verb    = 'UploadSiteHostedPictures';   // the call being made:
    $version = 893;                          // eBay API version
    
    $file      = $img;       // image file to read and upload
    $picNameIn = 'evoktcom'.md5(rand()%1000000).'_'.(rand()%1000);
    $handle = fopen($file,'r');         // do a binary read of image
    $multiPartImageData = fread($handle,filesize($file));
    fclose($handle);

    ///Build the request XML request which is first part of multi-part POST
    $xmlReq = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    $xmlReq .= '<' . $verb . 'Request xmlns="urn:ebay:apis:eBLBaseComponents">' . "\n";
    $xmlReq .= "<Version>$version</Version>\n";
    $xmlReq .= "<PictureName>$picNameIn</PictureName>\n";    
    $xmlReq .= "<RequesterCredentials><eBayAuthToken>$userToken</eBayAuthToken></RequesterCredentials>\n";
    $xmlReq .= '</' . $verb . 'Request>';

    $boundary = "MIME_boundary";
    $CRLF = "\r\n";
    
    // The complete POST consists of an XML request plus the binary image separated by boundaries
    $firstPart   = '';
    $firstPart  .= "--" . $boundary . $CRLF;
    $firstPart  .= 'Content-Disposition: form-data; name="XML Payload"' . $CRLF;
    $firstPart  .= 'Content-Type: text/xml;charset=utf-8' . $CRLF . $CRLF;
    $firstPart  .= $xmlReq;
    $firstPart  .= $CRLF;
    
    $secondPart .= "--" . $boundary . $CRLF;
    $secondPart .= 'Content-Disposition: form-data; name="dummy"; filename="dummy"' . $CRLF;
    $secondPart .= "Content-Transfer-Encoding: binary" . $CRLF;
    $secondPart .= "Content-Type: application/octet-stream" . $CRLF . $CRLF;
    $secondPart .= $multiPartImageData;
    $secondPart .= $CRLF;
    $secondPart .= "--" . $boundary . "--" . $CRLF;
    
    $fullPost = $firstPart . $secondPart;
    
    // Create a new eBay session (defined below) 
    $session = new eBaySessionMultipart($userToken, $devID, $appID, $certID, false, $version, $siteID, $verb, $boundary);

    $respXmlStr = $session->sendHttpRequest($fullPost);   // send multi-part request and get string XML response
    
    if(stristr($respXmlStr, 'HTTP 404') || $respXmlStr == '')
        die('<P>Error uploading Photo!');
        
    $respXmlObj = simplexml_load_string($respXmlStr);     // create SimpleXML object from string for easier parsing
                                                          // need SimpleXML library loaded for this
    /* Returned XML is of form 
      <?xml version="1.0" encoding="utf-8"?>
      <UploadSiteHostedPicturesResponse xmlns="urn:ebay:apis:eBLBaseComponents">
        <Timestamp>2007-06-19T16:53:50.370Z</Timestamp>
        <Ack>Success</Ack>
        <Version>517</Version>
        <Build>e517_core_Bundled_4784308_R1</Build>
        <PictureSystemVersion>2</PictureSystemVersion>
        <SiteHostedPictureDetails>
          <PictureName>my_pic</PictureName>
          <PictureSet>Standard</PictureSet>
          <PictureFormat>JPG</PictureFormat>
          <FullURL>http://i21.ebayimg.com/06/i/000/a5/e9/0e60_1.JPG?set_id=7</FullURL>
          <BaseURL>http://i21.ebayimg.com/06/i/000/a5/e9/0e60_</BaseURL>
          <PictureSetMember>...</PictureSetMember>
          <PictureSetMember>...</PictureSetMember>
          <PictureSetMember>...</PictureSetMember>
        </SiteHostedPictureDetails>
      </UploadSiteHostedPicturesResponse>
    */
    
    $ack        = $respXmlObj->Ack;
    $picNameOut = $respXmlObj->SiteHostedPictureDetails->PictureName;
    $picURL     = $respXmlObj->SiteHostedPictureDetails->FullURL;
    
    print "<P>Picture Upload Outcome : $ack </P>\n";
    print "<P>picNameOut = $picNameOut </P>\n";
    print "<P>picURL = $picURL</P>\n";
    print "<IMG SRC=\"$picURL\">";
	
	return $picURL;
}
?>

<?php
// This is a modified version of the 'eBaySession' class which is used in many
// of the other PHP samples.  This has been modified to accomodate multi-part HttpRequests
class eBaySessionMultipart
{
	private $requestToken;
	private $devID;
	private $appID;
	private $certID;
	private $serverUrl;
	private $compatLevel;
	private $siteID;
	private $verb;
    private $boundary;

	public function __construct($userRequestToken, $developerID, $applicationID, $certificateID, $useTestServer,
								$compatabilityLevel, $siteToUseID, $callName, $boundary)
	{
	    $this->requestToken = $userRequestToken;
	    $this->devID = $developerID;
            $this->appID = $applicationID;
	    $this->certID = $certificateID;
	    $this->compatLevel = $compatabilityLevel;
	    $this->siteID = $siteToUseID;
	    $this->verb = $callName;
            $this->boundary = $boundary;
	    if(!$useTestServer)
		$this->serverUrl = 'https://api.ebay.com/ws/api.dll';
	    else
	        $this->serverUrl = 'https://api.sandbox.ebay.com/ws/api.dll';
	}
	
	/**	sendHttpRequest
		Sends a HTTP request to the server for this session
		Input:	$requestBody
		Output:	The HTTP Response as a String
	*/
	public function sendHttpRequest($requestBody)
	{        
        $headers = array (
            'Content-Type: multipart/form-data; boundary=' . $this->boundary,
            'Content-Length: ' . strlen($requestBody),
	    'X-EBAY-API-COMPATIBILITY-LEVEL: ' . $this->compatLevel,  // API version
			
	    'X-EBAY-API-DEV-NAME: ' . $this->devID,     //set the keys
	    'X-EBAY-API-APP-NAME: ' . $this->appID,
	    'X-EBAY-API-CERT-NAME: ' . $this->certID,

            'X-EBAY-API-CALL-NAME: ' . $this->verb,		// call to make	
	    'X-EBAY-API-SITEID: ' . $this->siteID,      // US = 0, DE = 77...
        );
	//initialize a CURL session - need CURL library enabled
	$connection = curl_init();
	curl_setopt($connection, CURLOPT_URL, $this->serverUrl);
        curl_setopt($connection, CURLOPT_TIMEOUT, 30 );
	curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($connection, CURLOPT_POST, 1);
	curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);
	curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($connection, CURLOPT_FAILONERROR, 0 );
        curl_setopt($connection, CURLOPT_FOLLOWLOCATION, 1 );
        //curl_setopt($connection, CURLOPT_HEADER, 1 );           // Uncomment these for debugging
        //curl_setopt($connection, CURLOPT_VERBOSE, true);        // Display communication with serve
        curl_setopt($connection, CURLOPT_USERAGENT, 'ebatns;xmlstyle;1.0' );
        curl_setopt($connection, CURLOPT_HTTP_VERSION, 1 );       // HTTP version must be 1.0
	$response = curl_exec($connection);
        
        if ( !$response ) {
            print "curl error " . curl_errno($connection ) . "\n";
        }
	curl_close($connection);
	return $response;
    } // function sendHttpRequest
}  // class eBaySession
  
    
?>
