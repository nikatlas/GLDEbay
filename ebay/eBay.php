<?php 
include_once ('keys.php');
require_once ('ebaySession.php');
/* TODO 
	-> Change/check site ID  = 0 = US !!!!!!!
	-> IMAGES 
//*/
class Ebay {
    public function __construct( $appID,$devID,$certID,$RuName,$serverUrl, $userToken,$compatabilityLevel, $siteID)
    {
        $this->devID = $devID;
        $this->appID= $appID;
        $this->certID= $certID;
        $this->compatabilityLevel= $compatabilityLevel;
        $this->siteID= $siteID;
        $this->userToken= $userToken;
        $this->serverUrl= $serverUrl;
        $this->runame= $RuName;
		$date = new DateTime();
		$this->StartTimeTo= $date->format('Y-m-dTH:i:s').'z';
		$date->sub(new DateInterval('P1D'));
        $this->StartTimeFrom= $date->format('Y-m-dTH:i:s').'z';
        $this->EntriesPerPage= 50;
        $this->timeTail = 'T21:59:59.005Z';
		$this->itemIds = array();
    }
    function XML2Array($xmlSrting){
        $xml = simplexml_load_string($xmlSrting);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        return $array;
    }
	public function getStuff(){
			$res =  $this->ebayManagement();
			if( !$res ) return false;

			$ebayItems = array();
			for ( $i=0;$i< $res['totalPages'];$i++ ){
				$doc = new DOMDocument();
				$doc->loadXML($res['myeBaySellingXml'][$i]);
				$items = $doc->getElementsByTagName("ItemID");
				foreach( $items as $item ){
					array_push($ebayItems, $item->nodeValue);
				}	
			}
			$this->itemIds = $ebayItems;
			return true;
	}
    public function parseXml($responseXml){
        if(stristr($responseXml, 'HTTP 404') || $responseXml == '')
                die('<P>Error sending request');
        $responseDoc = new \DomDocument();
        $responseDoc->loadXML($responseXml);
        return $responseDoc;
    }
	public function getItemData($itemId){
			 $xml = $this->getItem($itemId);
			 $doc = new DOMDocument();
			 $desdoc = new DOMDocument();
			 $doc->loadXML($xml);
			 $desdoc->loadHTML($doc->getElementsByTagName("Description")->item(0)->nodeValue);
			 // GET DESCRIPTION DIV
			 $divs = $desdoc->getElementsByTagName("div");
			 foreach ( $divs as $div ){
				if( $div->attributes->getNamedItem("class")->nodeValue != "des" ) continue; 
				$des = $div;
				break;
			 }
			 $descr_str = $desdoc->saveHTML($des);
			 ////////////////////////
			 // GET STYLES 
			 $style_str = "";
			 $styles = $desdoc->getElementsByTagName("style");
			 foreach ( $styles as $style ){
				$style_str .= $desdoc->saveHTML($style);
			 }
			 ////////////////////////
			 // GET Scripts
			 $script_str = "";
			 $scripts = $desdoc->getElementsByTagName("script");
			 foreach ( $scripts as $script ){
				$script_str .= $desdoc->saveHTML($script);
			 }
			 ////////////////////////
			 $item = new Item();
			 $item->description = $script_str . " " .$style_str . " " .$descr_str;
			 $item->setSku($doc->getElementsByTagName("SKU")->item(0)->nodeValue);
			 $item->price = $doc->getElementsByTagName("CurrentPrice")->item(0)->nodeValue;
			 $item->currency = $doc->getElementsByTagName("CurrentPrice")->item(0)->attributes->getNamedItem("currencyID")->nodeValue;
			 $item->quantity = $doc->getElementsByTagName("Quantity")->item(0)->nodeValue;
 			 $item->categoryId = $doc->getElementsByTagName("CategoryID")->item(0)->nodeValue;
			 $item->categoryName = $doc->getElementsByTagName("CategoryName")->item(0)->nodeValue;
			 $pictures = $doc->getElementsByTagName("PictureURL");
			 $item->pictures = array();
			 foreach( $pictures as $pic ) {
				 array_push($item->pictures , $pic->nodeValue);
			 }
			 $item->name = $doc->getElementsByTagName("Title")->item(0)->nodeValue;
			 return $item;
	}
    protected function getSessionId($runame)
    {
            $session = new eBaySession('GetSessionID',$this);
            //Build the request Xml string
            $requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
                                <GetSessionIDRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                                    <RuName>'.$runame.'</RuName>
                                </GetSessionIDRequest>';
            //Create a new eBay session with all details pulled in from included keys.php
            $responseXml = $session->sendHttpRequest($requestXmlBody);
            return $responseXml;
    }
    protected function GetUser($userToken)
    {
            $session = new eBaySession('GetUser',$this);
            //Build the request Xml string
            $requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
                                <GetUserRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                                <RequesterCredentials>
                                <eBayAuthToken>'.$userToken.'</eBayAuthToken>
                                </RequesterCredentials>
                                </GetUserRequest>';

            //Create a new eBay session with all details pulled in from included keys.php
            $responseXml = $session->sendHttpRequest($requestXmlBody);
                        
            return $responseXml;
    }
    public function GetTokenStatus($userToken)
    {
        $session = new eBaySession('GetTokenStatus',$this);
        $requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
                            <GetTokenStatusRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                                <RequesterCredentials>
                                    <eBayAuthToken>'.$userToken.'</eBayAuthToken>
                                </RequesterCredentials>
                            </GetTokenStatusRequest>';
	//Create a new eBay session with all details pulled in from included keys.php
	$responseXml = $session->sendHttpRequest($requestXmlBody);
        
        return $responseXml;
    }
    public function fetchToken($sessionId)
    {
        $session = new eBaySession('FetchToken',$this);

        $requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
                            <FetchTokenRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                              <SessionID>'.$sessionId.'</SessionID>
                            </FetchTokenRequest>';
        //Create a new eBay session with all details pulled in from included keys.php
        $responseXml = $session->sendHttpRequest($requestXmlBody);

        return $responseXml;
    }
    public function GetSellerList($userToken,$StartTimeFrom,$StartTimeTo,$EntriesPerPage,$pageNumber)
    {
            $session = new eBaySession('GetSellerList',$this);
            //Build the request Xml string
            $requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
                                <GetSellerListRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                                <RequesterCredentials>
                                <eBayAuthToken>'.$userToken.'</eBayAuthToken>
                                </RequesterCredentials>
                                 <ErrorLanguage>en_US</ErrorLanguage>
                                  <WarningLevel>High</WarningLevel>
                                  <GranularityLevel>Fine</GranularityLevel>
                                  <StartTimeFrom>'.$StartTimeFrom.'</StartTimeFrom>
                                  <StartTimeTo>'.$StartTimeTo.'</StartTimeTo>
                                  <IncludeWatchCount>true</IncludeWatchCount>
                                  <Pagination>
                                    <EntriesPerPage>'.$EntriesPerPage.'</EntriesPerPage>
                                    <PageNumber>'.$pageNumber.'</PageNumber>    
                                  </Pagination>
                                </GetSellerListRequest>';
            
            //Create a new eBay session with all details pulled in from included keys.php
            $responseXml = $session->sendHttpRequest($requestXmlBody);
            
            return $responseXml;
    }
    public function GetMyeBaySelling($userToken,$EntriesPerPage,$pageNumber)
    {
            $session = new eBaySession('GetMyeBaySelling',$this);
            //Build the request Xml string
            $requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
                            <GetMyeBaySellingRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                              <RequesterCredentials>
                                <eBayAuthToken>'.$userToken.'</eBayAuthToken>
                              </RequesterCredentials>
                              <Version>'.$this->compatabilityLevel.'</Version>
                              <ActiveList>
                                <Sort>TimeLeft</Sort>
                                <Pagination>
                                  <EntriesPerPage>'.$EntriesPerPage.'</EntriesPerPage>
                                  <PageNumber>'.$pageNumber.'</PageNumber>  
                                </Pagination>
                              </ActiveList>
							  <OutputSelector>ItemID</OutputSelector>
							  <OutputSelector>PaginationResult</OutputSelector>
                            </GetMyeBaySellingRequest>';

            //Create a new eBay session with all details pulled in from included keys.php
            $responseXml = $session->sendHttpRequest($requestXmlBody);
            
            return $responseXml;

    }	
    public function GetItem($itemId)
    {
            $session = new eBaySession('GetItem',$this);
            //Build the request Xml string
            /*$requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
								<GetSingleItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
								  <ItemID>'.$itemId.'</ItemID>
								</GetSingleItemRequest>';
			*/
			$requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
								<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
								  <RequesterCredentials>
									<eBayAuthToken>'.$_SESSION['userToken'].'</eBayAuthToken>
								  </RequesterCredentials>
								  <Version>'.$this->compatabilityLevel.'</Version>
								  <IncludeItemSpecifics>true</IncludeItemSpecifics>
								  <IncludeTaxTable>true</IncludeTaxTable>
								  <IncludeWatchCount>true</IncludeWatchCount>
								  <ItemID>'.$itemId.'</ItemID>
								  <DetailLevel>ItemReturnDescription</DetailLevel>
								    <IncludeSelector>Description,ItemSpecifics</IncludeSelector>

								</GetItemRequest>';
			
            //Create a new eBay session with all details pulled in from included keys.php
            $responseXml = $session->sendHttpRequest($requestXmlBody);
            
            return $responseXml;

    }    
    public function ebayManagement($input=array())
    {
        $sellerList = array();
        $myeBaySelling = array();
        $getUser = array();
        $tokenStatus = "";
        $sessionId = "";
        $showLogin = true;
        $StartTimeFrom = $this->StartTimeFrom;
        $StartTimeTo = $this->StartTimeTo;
        $EntriesPerPage = $this->EntriesPerPage;
        $pageNumber = 1;
        $error = "";
        $formInput = array( 'StartTimeFrom' =>  $StartTimeFrom,
                            'StartTimeTo'   =>  $StartTimeTo,
                            'EntriesPerPage'=>  $EntriesPerPage,
                            'pageNumber'    =>  $pageNumber ) ;
        
		if ( (!isset($_SESSION['sesId']) || $_SESSION['sesId'] == "") && $this->userToken == '' ){
			$sessionIdXml = $this->getSessionId($this->runame) ;
			$sessionIdResponse = $this->parseXml($sessionIdXml);
			$sessionId = $sessionIdResponse->getElementsByTagName('SessionID')->item(0)->nodeValue;
			$_SESSION['sesId'] = $sessionId;
			echo '<a target="_new" href="https://signin.ebay.com/ws/eBayISAPI.dll?SignIn&RuName='.$this->runame.'&SessID='.$sessionId.'">Click Here To Link Your Ebay Account To Our Website</a>';
			return false;
		}
		// OPEN FETCHING
		else{
        // GET userToken
        // 
        // Check if usertoken is getting using the sessionId(passed to the ebay pop up form)
        // if success save that userToken to $this->userToken
        // else set $this->userToken to the token value stored in session
        if( !$this->userToken || $this->userToken == '' ){
			$fetchTokenXml = $this->fetchToken($_SESSION['sesId']) ;
			$fetchTokenResponse = $this->parseXml($fetchTokenXml);
			
			if($fetchTokenResponse->getElementsByTagName('Ack')->item(0)->nodeValue=='Success'){
				//echo '1.Success <br>';
				$this->userToken =  $fetchTokenResponse->getElementsByTagName('eBayAuthToken')->item(0)->nodeValue;
			} else {
				$_SESSION['sesId'] = "";
				echo 'FetchToken Fail <BR><br>';
				echo 'Refresh!';
				return false;
	
			}
		}
        if($this->userToken) {
            
            //get token Status
            $tokenStatusXml = $this->GetTokenStatus($this->userToken) ;
            $tokenStatusResponse = $this->parseXml($tokenStatusXml);
            $tokenStatus = $tokenStatusResponse->getElementsByTagName('Ack')->item(0)->nodeValue=='Success'
                            ? $tokenStatusResponse->getElementsByTagName('Status')->item(0)->nodeValue
                            : 'Inactive' ;

            $GetUserXml = $this->GetUser($this->userToken);
            $getUser = $this->XML2Array($GetUserXml);

            //  if form submitted
            if(isset($input['sellerListSubmit']) || true){
                
                //echo $input['pageNumber'];

                $StartTimeFrom = isset($input['StartTimeFrom']) && $input['StartTimeFrom']!=''
                                ? $input['StartTimeFrom']
                                :$StartTimeFrom ;
                $StartTimeTo = isset($input['StartTimeTo']) && $input['StartTimeTo']!=''
                                ? $input['StartTimeTo'] 
                                : $StartTimeTo;
                $EntriesPerPage = isset($input['EntriesPerPage']) && $input['EntriesPerPage']!=''
                                ? $input['EntriesPerPage'] 
                                : $EntriesPerPage;
                $pageNumber = isset($input['pageNumber']) && $input['pageNumber']!=''
                                ? $input['pageNumber'] 
                                : 1;
                
                $formInput = array( 'StartTimeFrom' =>  $StartTimeFrom,
                                    'StartTimeTo'   =>  $StartTimeTo,
                                    'EntriesPerPage'=>  $EntriesPerPage,
                                    'pageNumber'    =>  $pageNumber) ;
                //$sellerListXml = $this->GetSellerList($this->userToken, $StartTimeFrom, $StartTimeTo, $EntriesPerPage,$pageNumber);
                //$sellerList = $this->XML2Array($sellerListXml);
				$myeBaySellingXml = array();
				$myeBaySellingDocs = array();
				array_push($myeBaySellingXml , $this->GetMyeBaySelling($this->userToken,$EntriesPerPage,$pageNumber) );
        		$myeBaySellingDoc = new DOMDocument();
		        $myeBaySellingDoc->loadXML($myeBaySellingXml[0]);
				array_push($myeBaySellingDocs , $myeBaySellingDoc );
				$myeBaySelling = $this->XML2Array($myeBaySellingXml[0]);
				$pages = $myeBaySellingDoc->getElementsByTagName("TotalNumberOfPages")->item(0)->nodeValue;
				$totalEntries = $myeBaySellingDoc->getElementsByTagName("TotalNumberOfEntries")->item(0)->nodeValue;
				
				for( $i=2;$i<=$pages;$i++){
					array_push($myeBaySellingXml , $this->GetMyeBaySelling($this->userToken,$EntriesPerPage,$i) );
					$myeBaySellingDoc = new DOMDocument();
					$myeBaySellingDoc->loadXML($myeBaySellingXml[$i-1]);
					array_push($myeBaySellingDocs , $myeBaySellingDoc );
				}	
				
            }
            
        } else {
            echo ' no usertoken ';  
        }
        
        $_SESSION['passed4login'] = $_SESSION['sesId'];
        $_SESSION['userToken'] = $this->userToken;
        
        return array(   'sellerList'    =>  $sellerList,
                        'myeBaySelling' =>  $myeBaySelling,
						'myebaySellingDoc' => $myeBaySellingDocs,
						'totalEntries' => $totalEntries,
						'totalPages' => $pages,
						'myeBaySellingXml' => $myeBaySellingXml,
                        'tokenStatus'   =>  $tokenStatus,
                        'runame'        =>  $this->runame,
                        'sessionId'     =>  urlencode($_SESSION['passed4login']),
                        'userToken'     =>  $this->userToken,
                        'showLogin'     =>  $showLogin,
                        'formInput'     =>  $formInput,
                        'getUser'        => $getUser,
                        'error'         =>  $error
                    ) ;
		}// CLOSE OF FETCHING
    }

}
?>