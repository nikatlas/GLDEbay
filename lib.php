<?
require_once ('ebay/eBay.php');
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

class ebayAccount extends Ebay{
    public function __construct(){
		global $appID,$devID,$certID,$RuName,$serverUrl, $userToken,$compatabilityLevel, $siteID;
   	    initKeys();
		parent::__construct($appID,$devID,$certID,$RuName,$serverUrl, $userToken,$compatabilityLevel, $siteID);		
	}	
	
	public function getCategoriesRequest($cat=NULL){
        $session = new eBaySession('GetCategories',$this);
		$xml = '<?xml version="1.0" encoding="utf-8"?
				<GetCategoriesRequest xmlns="urn:ebay:apis:eBLBaseComponents">
				<RequesterCredentials>
                                <eBayAuthToken>'.$this->userToken.'</eBayAuthToken>
                </RequesterCredentials>'.(($cat == NULL) ? "<LevelLimit>1</LevelLimit>":
				  '<CategoryParent>'.$cat->id.'</CategoryParent><LevelLimit>'.($cat->level+1).'</LevelLimit>').'
				  <DetailLevel>ReturnAll</DetailLevel>
				  <Version>893</Version>
				</GetCategoriesRequest>
				';	
		$responseXml = $session->sendHttpRequest($xml);      
		//echo $xml;                  
        return $responseXml;
	}
	public function getCategories($categ=NULL){
		$xml = $this->getCategoriesRequest($categ);
		$res = new DOMDocument();
		$res->loadXML($xml);		
		$cats = $res->getElementsByTagName("Category");
		$categories = array();
		foreach($cats as $cat){
			$cata = new Category();
			$cata->loadFromXml($cat);
			if( $cata->id == $categ->id )continue;
			array_push($categories , $cata);
		}
		return ($categories);
	}
	public function getCategoriesById($id , $level){
		return $this->getCategories(new Category("",$id,"",$level));
	}
	
	public function verifyAddItem(){
        $session = new eBaySession('VerifyAddItem',$this);
		$description = "test";
		$title = "TITLE";
		$conditionId = 1000;
		$site = "0"; // US
		$sku = "x2222";
		$category = "171485";
		$price =14.0;
		$quantity = 2;
		$listingType = "FixedPriceItem";
		$location ="US";
		$dispatchTime = 7;
		$listingDuration = "Days_10";
		$xml = '<?xml version="1.0" encoding="utf-8"?>
				<VerifyAddItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
				<RequesterCredentials>
                                <eBayAuthToken>'.$this->userToken.'</eBayAuthToken>
                </RequesterCredentials>
				  <Item> 
						 <Description>'.$description.'</Description>
						 <Title>'.$title.'</Title>
						 <SKU>'.$sku.'</SKU>
						 <ConditionID>'.$conditionId.'</ConditionID>
						 <PrimaryCategory><CategoryID>'.$category.'</CategoryID></PrimaryCategory>
						 <StartPrice>'.$price.'</StartPrice>
						 <Currency>USD</Currency>
						 <Country>US</Country>
						 <Quantity>'.$quantity.'</Quantity>				    
						 <ListingType>'.$listingType.'</ListingType>					
						 <ListingDuration>'.$listingDuration.'</ListingDuration>
						 <Location>'.$location.'</Location>
						 <ShippingDetails>			 
						 </ShippingDetails>
						 <DispatchTimeMax>'.$dispatchTime.'</DispatchTimeMax>
							<PaymentMethods>PayPal</PaymentMethods>
							<PayPalEmailAddress>nikatlas@gmail.com</PayPalEmailAddress>
					</Item>
				  </VerifyAddItemRequest>
				';	
		$responseXml = $session->sendHttpRequest($xml);                        
        return $responseXml;
	}
}

class Category {
	public function __construct ($name = "" , $id = "" , $parentId = "" , $level = 1){
		$this->name = $name;
		$this->id = $id;
		$this->parentId = $parentId;	
		$this->level = $level;
	}	
	public function loadFromXml( $xml ){
		$this->name = $xml->getElementsByTagName("CategoryName")->item(0)->nodeValue;
		$this->id = $xml->getElementsByTagName("CategoryID")->item(0)->nodeValue;
		$this->parentId = $xml->getElementsByTagName("CategoryParentID")->item(0)->nodeValue;
		$this->level = $xml->getElementsByTagName("CategoryLevel")->item(0)->nodeValue;
		try{
		$this->leaf = ( sizeof($xml->getElementsByTagName("LeafCategory")) > 0 ) ? $xml->getElementsByTagName("LeafCategory")->item(0)->nodeValue : false;
		}
		catch(Exception $e){
			$this->leaf = false;	
		}
		
	}
}

?>