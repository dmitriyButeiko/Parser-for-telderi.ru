<?php

$ch = curl_init("http://www.telderi.ru/ru");

require_once 'simple_html_dom.php';


class TelderiParser
{
    private $_authorized = false;
    private $_token;
    private $confirmationLink;
    private static $_instance = null;
    private $_siteUrl = "http://telderi.ru";
    private $_cookies = array();
	
    private function __construct() 
    {
        
    }  
    static public function getParser()
    {
        if(is_null(self::$_instance))
        {
             self::$_instance = new self(); 
        }
        return self::$_instance;
    }  
    private function getLoginPage()
    {
        $curl = curl_init("http://www.telderi.ru/ru/system/login");
		
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, true);
		
        return curl_exec($curl);
    }
    private function parseCookies($html)
    {
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $html, $matches);
        $cookies = array();
	    
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        
	return $cookies;
    }
    public function authorize($email,$password)
    {        
            $result = $this->getLoginPage();            
            $cookies = $this->parseCookies($result);            
            ch = curl_init("http://www.telderi.ru/ru/system/login"); 
	    
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);                                    
            $token = $cookies["YII_CSRF_TOKEN"];         
            $fields = array(
                "LoginForm[user_email]"   => $email,
                "LoginForm[password]"     => $password,
                "YII_CSRF_TOKEN"          => $token,
                "LoginForm[all_validate]" => true,
                "LoginForm[verifyCode]"   => null,
                "LoginForm[rememberMe]"   => 1
            );       
	    
            $postData = $this->makePostString($fields);
            curl_setopt($ch, CURLOPT_POST, count($postData));
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Encoding: gzip, deflate',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                'Cookie: YII_CSRF_TOKEN='.$token.'; _ym_visorc_21260713=w',
                'Host: www.telderi.ru'
            ));        
	    
            $result = curl_exec($ch); 
            $this->_cookies = $this->parseCookies($result);
            if(count($this->_cookies) < 4)
            {
                throw new Exception('Wrong login or email');
            }
            $this->_token = $token;
        }
        public function addSite($options)
	{         
            $ch = curl_init("http://www.telderi.ru/ru/default/addAuction");
        
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $this->makeCookieString($this->_cookies),
                'Host: www.telderi.ru',
                'X-Requested-With: XMLHttpRequest'
            ));
            $result = curl_exec($ch);
            curl_close($ch);
            $html   = str_get_html($result);
        
            $token = $html->find('#addforsell-form input[type="hidden"]',0)->value;
            $site_addr = $options["site_url"];
        
            $fields = array(
                "YII_CSRF_TOKEN"   => $token,
                "auction_type"     => "website",
                "ClassForValidationAddAuction[radio_b]" => "website",
                "ClassForValidationAddAuction[website]" => $site_addr,
                "ClassForValidationAddAuction[domain]"   => "",
                "ClassForValidationAddAuction[site_only]" => "",
                "guaranteed"   => true,
                "yt0"          => "Готово"
           );  
		
           $postData = $this->makePostString($fields);
         
           $ch = curl_init("http://www.telderi.ru/ru/default/addAuction");

           $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
         
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
           curl_setopt($ch, CURLOPT_POST, count($postData));
           curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
           curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
           curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
           curl_setopt($ch, CURLOPT_HEADER, true);
           curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
           ));                
          
           $result = curl_exec($ch);          
           curl_close($ch);
          
           $html = str_get_html($result);
       
       
          if($options["trafic"])
          {
              $traficLink = $html->find("div.edit_field a",1)->href;
              $this->setTrafic($traficLink, $options["trafic"]);
          }
       if($options["header"])
       {
           $headerLink = $html->find("#field_title a",0)->href; 
           $this->setHeader($headerLink, $options["header"] );    
       }
       if($options["content"])
       {
           $contentLink = $html->find("div.edit_field a",4)->href; 
           $this->setContent( $contentLink,$options["content"] );     
 
       }  
       if($options["cost"])
       {
           $costLink = $html->find("div.edit_field a",3)->href;
           $this->setCost($costLink,$options["cost"]);  
       }
       if($options["profit"])
       {
           $profitLink = $html->find("div.edit_field a",2)->href;
           $this->setProfit($profitLink, $options["profit"]);
       }     
       if($options["description"])
       {
           $descriptionLink = $html->find("div.edit_field a",0)->href;            
           $this->setDescription($descriptionLink, $options["description"]);   
       }    
       if($options["site_type"])
       {
           $type_id = $this->getTypeId($options["site_type"]);
           $typeLink = $html->find("ol.required li a",3)->href;
           $this->setType($typeLink, $type_id);
       }     
       if($options["domain_registrator"])
       {
           $domainRegistratorLink = $html->find("ol.required li a",7)->href;
           $this->setDomainRegistrator($domainRegistratorLink,$options["domain_registrator"]);
       }
       if($options["price"])
       {
           $priceLink = $html->find("ol.required li a",9)->href;
           $this->setPrice($priceLink, $options["price"]);
       }
       if($options["cms"])
       {
           $cmsLink = $html->find("ol.possibly li a",0)->href;
           $cms_id = $this->getCMSId($options["cms"]);
           $this->setCMS($cmsLink, $cms_id);
       }
       if($options["data_creation"])
       {
           $dataCreationLink = $html->find("ol.possibly li a",1)->href;
           $this->setDateCreation($dataCreationLink, $options["data_creation"]);
       }
       if($options["show_to"])
       {
           $showToLink = $html->find("ol.possibly li a",3)->href;
           $this->setShowTo($showToLink, $options["show_to"]);
       }
       $confirmLink =  $html->find("ol.required li a",8)->href;
       $this->confirmLaws($confirmLink);
}



private function confirmLaws($link)
{
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
        ));   
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    $html = str_get_html($result);
    
    $this->confirmationLink = $html->find("ol.steps",1)->find("li",2)->find("a",0)->text();
    
}
public function getConfirmationLink()
{
    return $this->confirmationLink;
}
private function setShowTo($link,$options)
{
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru',
                'X-Requested-With: XMLHttpRequest'
        ));   
	
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $html = str_get_html($result);
    $link = $this->_siteUrl.($html->find("#yw0",0)->action);

    $token = $html->find('#yw0 input[name="YII_CSRF_TOKEN"]',0)->value;
    
    $fields = array(
        "show_url" => $options["show_url"],
        "who_show"  => $options["who_show"],
        "whom_def_answer[]" => $options["whom_def_answer"],
        "approved_trust" => $options["approved_trust"] ? $options["approved_trust"] : 0,
        "YII_CSRF_TOKEN"                  => $token
        );   
       $postData = $this->makePostString($fields);
       $ch = curl_init($link);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_HEADER, true); 
       curl_setopt($ch, CURLOPT_POST, count($postData));
       curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
       $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru',
                'X-Requested-With: XMLHttpRequest'
        ));  
     
       $res = curl_exec($ch);
}
private function setDateCreation($link,$date_creation)
{
    $dataString = $date_creation["day"].'.'.$date_creation["month"].'.'.$date_creation["year"];
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/viewsite/934945',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru',
                'X-Requested-With: XMLHttpRequest'
    ));   
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $html = str_get_html($result);

    //$linkf = ($html->find("#yw0",0)->action);
    $token = $html->find('#yw0 input[name="YII_CSRF_TOKEN"]',0)->value;
        
    $fields = array(
        "SiteWebsites[website_age]" => $dataString,
        "YII_CSRF_TOKEN"                  => $token
        );   
       $postData = $this->makePostString($fields);
       $ch = curl_init($link);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_HEADER, true); 
       curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
       curl_setopt($ch, CURLOPT_POST, count($postData));
       curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
       $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
        ));  
     
       $res = curl_exec($ch);  
       echo $res;
}
private function setCMS($link,$cms_id)
{
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
        ));   
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $html = str_get_html($result);

    $link = ($html->find("#yw0",0)->action);

    $token = $html->find('#yw0 input[name="YII_CSRF_TOKEN"]',0)->value;
 
    
    $fields = array(
        "cms_id" => $cms_id,
        "YII_CSRF_TOKEN"                  => $token
        );   
       $postData = $this->makePostString($fields);
       $ch = curl_init($link);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_HEADER, true); 
       curl_setopt($ch, CURLOPT_POST, count($postData));
       curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
       $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
        ));  
     
       $res = curl_exec($ch);   
}
private function getCMSId($cms_name)
{
    switch($cms_name)
    {
        case "Собственная уникальная CMS": 
        return 13;
        break;
        case "Wordpress": 
        return 3;
        break;
        case "Datalife Engine (DLE)": 
        return 5;
        break;
        case "Joomla": 
        return 2;
        break;
        case "Drupal": 
        return 1;
        break;
        case "1C-Битрикс": 
        return 4;
        break;
         case "InstantCMS": 
        return 14;
        break;
         case "Ucoz": 
        return 11;
        break;
         case "UMI.CMS": 
        return 6;
        break;
         case "NetCat";
        return 7;
        break;
         case "HostCMS": 
        return 8;
        break;
     case "MODx": 
        return 9;
        break;
         case "Нет (html+css)": 
        return 10;
        break;
         case "Другое": 
        return 12;
        break;
        default:
        return false;
    }
}
private function setPrice($link,$price)
{
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
        ));   
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $html = str_get_html($result);

    $link = ($html->find("#setPriceForm",0)->action);

    $token = $html->find('#setPriceForm input[name="YII_CSRF_TOKEN"]',0)->value;
 
    $onlineReg = $domainRegistrator["online_reg"] ?  $domainRegistrator["online_reg"] : 0;
    
    
    $fields = array(
        "Auction[optimal_price]" => $price["optimal_price"],
        "Auction[blitz_price]"   => $price["blic_price"],
        "Auction[allow_less_optimum]"   => $price["allow_less_optimum"],
        "YII_CSRF_TOKEN"                  => $token
        );   
       $postData = $this->makePostString($fields);
       $ch = curl_init($link);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_HEADER, true); 
       curl_setopt($ch, CURLOPT_POST, count($postData));
       curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
       $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
        ));  
     
       $res = curl_exec($ch);   
}
private function setDomainRegistrator($link,$domainRegistrator)
{
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
        ));   
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $html = str_get_html($result);

    $link = ($html->find("#yw0",0)->action);

    $token = $html->find('#yw0 input[name="YII_CSRF_TOKEN"]',0)->value;
 
    $onlineReg = $domainRegistrator["online_reg"] ?  $domainRegistrator["online_reg"] : 0;
    
    
    $fields = array(
        "SiteDomainInfo[reg_panel]"                  => $domainRegistrator["domain_registrator"],
        "SiteDomainInfo[confirm_register_contact]"   => 1,
        "SiteDomainInfo[online_reg]"   =>  $onlineReg,
        "YII_CSRF_TOKEN"                  => $token
        );   
    $postData = $this->makePostString($fields);
    $ch = curl_init($link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_POST, count($postData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
    ));  
     
    $res = curl_exec($ch);
}
private function setType($link,$type_id)
{
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
        ));   
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $html = str_get_html($result);

    $link = ($html->find("#yw0",0)->action);

    $token = $html->find('#yw0 input[name="YII_CSRF_TOKEN"]',0)->value;
 
    $fields = array(
        "type_id"                 => $type_id,
        "YII_CSRF_TOKEN"                  => $token
        );   
    $postData = $this->makePostString($fields);
    $ch = curl_init($link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_POST, count($postData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
    ));  
   
    $res = curl_exec($ch);
}
private function getTypeId($typeName)
{
    switch ($typeName)
    {
        case "Авто":
        return 36;
        break;
        case "Бизнесо":
        return 38;
        break;
        case "Бытовая и электротехника":
        return 27;
        break;
        case "Животные":
        return 23;
        break;
        case "Законы и власть":
        return 17;
        break;
        case "Игры":
        return 1;
        break;
        case "Интернет":
        return 33;
        break;
        case "Каталоги сайтов":
        return 13;
        break;
        case "Кино и музыка":
        return 43;
        break;
        case "Компьютеры":
        return 32;
        break;
        case "Культура":
        return 31;
        break;
        case "Мебель":
        return 26;
        break;
        case "Медицина":
        return 24;
        break;
        case "Мода и красота":
        return 25;
        break;
        case "Недвижимость":
        return 39;
        break;
        case "Обучение":
        return 30;
        break;
        case "Отдых и еда":
        return 8;
        break;
        case "Политика":
        return 15;
        break;
        case "Природа":
        return 41;
        break;
        case "Производство":
        return 35;
        break;
         case "Психология":
        return 5;
        break;
         case "Работа":
        return 29;
        break;
         case "Региональные порталы":
        return 18;
        break;
         case "Реклама";
        return 40;
        break;
         case "Религия":
        return 14;
        break;
        case "СМИ":
        return 10;
        break;
         case "Связь":
        return 34;
        break;
         case "Семья":
        return 22;
        break;
         case "Социальные сети":
        return 3;
        break;
         case "Спорт":
        return 9;
        break;
         case "Справки";
        return 12;
        break; 
        case "Стройка, ремонт":
        return 19;
        break;
        case "Торговля":
        return 42;
        break;
           case "Туризм":
        return 6;
        break;
           case "Финансы":
        return 37;
        break;
           case "Хобби":
        return 7;
        break;
           case "Энциклопедии":
        return 11;
        break;
          case "Юмор":
        return 2;
        break;
        default:
        return false;
    }
}
private function setHeader($link,$text)
{
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
        ));   
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $html = str_get_html($result);

    $link = ($html->find("#yw0",0)->action);

    $token = $html->find('#yw0 input[name="YII_CSRF_TOKEN"]',0)->value;
 
    $fields = array(
        "Auction[title]"        => $text,
        "YII_CSRF_TOKEN"        => $token
    );   
    $postData = $this->makePostString($fields);
    $ch = curl_init($link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_POST, count($postData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
    ));  
     
       
    $res = curl_exec($ch);
}
private function setContent($link,$options)
{
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
        ));   
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $html = str_get_html($result);

    $link = $this->_siteUrl.($html->find("#edit_finance_form",0)->action);

    $token = $html->find('#edit_finance_form input[name="YII_CSRF_TOKEN"]',0)->value;
    
    $fields = array(
        "EditAuctionForm[design_type]" => $options["design_type"],
        "EditAuctionForm[percent_photo][unique]" => $options["percent_photo"]["unique"],
        "EditAuctionForm[percent_photo][not_unique]" => $options["percent_photo"]["not_unique"],
        "EditAuctionForm[percent_text_content][copywrite]" => $options["percent_text_content"]["copywrite"],
        "EditAuctionForm[percent_text_content][rewrite]" => $options["percent_text_content"]["rewrite"],
        "EditAuctionForm[percent_text_content][copypaste]" => $options["percent_text_content"]["copypaste"],
        "EditAuctionForm[percent_text_content][generat]" => $options["percent_text_content"]["generat"],
        "EditAuctionForm[more_content]" => $options["more_content"],
        "YII_CSRF_TOKEN"                  => $token
    ); 
	
    $postData = $this->makePostString($fields);
    $ch = curl_init($link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_POST, count($postData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
    ));  
     
       
    $res = curl_exec($ch);
}
private function setCost($link,$options)
{
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
    ));   
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $html = str_get_html($result);

    $link = $this->_siteUrl.($html->find("#yw0",0)->action);

    $token = $html->find('#yw0 input[name="YII_CSRF_TOKEN"]',0)->value;
    
    $fields = array(
         "EditAuctionForm[expenditure][de_hosting]" => $options["expenditure"]["de_hosting"],
         "EditAuctionForm[expenditure][de_coding]" => $options["expenditure"]["de_coding"],
         "EditAuctionForm[expenditure][de_content]" => $options["expenditure"]["de_content"],
         "EditAuctionForm[expenditure][de_seo]" => $options["expenditure"]["de_seo"],
         "EditAuctionForm[expenditure][de_advert]" => $options["expenditure"]["de_advert"],
         "EditAuctionForm[expenditure][de_other]" =>  $options["expenditure"]["de_other"],
         "EditAuctionForm[cost_is_true]"           => $options["expenditure"]["cost_is_true"],
         "EditAuctionForm[more_cost]"            => $options["expenditure"]["more_cost"],
          "YII_CSRF_TOKEN"                  => $token
    );   
    $postData = $this->makePostString($fields);
    $ch = curl_init($link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_POST, count($postData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
    ));  
     
       
    $res = curl_exec($ch);
    
}
private function setProfit($link, $options)
{
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
        ));   
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $html = str_get_html($result);

    $link = $this->_siteUrl.($html->find("#yw0",0)->action);

    $token = $html->find('#yw0 input[name="YII_CSRF_TOKEN"]',0)->value;
    
    $fields = array(
        "EditAuctionForm[exist_profit]" => $options["exist_profit"],
        "EditAuctionForm[agv_profit]" => $options["avg_profit"],
        "EditAuctionForm[profit_source_value][content_other]" => $options["profit_source_value"]["content_other"],
        "EditAuctionForm[profit_source_value][banners_teasers]" => $options["profit_source_value"]["banners_teasers"],
        "EditAuctionForm[profit_source_value][direct_sale]" => $options["profit_source_value"]["direct_sale"],
        "EditAuctionForm[profit_source_value][links_exchange_other]" => $options["profit_source_value"]["links_exchange_other"],
        "EditAuctionForm[profit_source_value][direct_links_sale]" =>  $options["profit_source_value"]["direct_links_sale"],
        "EditAuctionForm[profit_source_value][product_service]" => $options["profit_source_value"]["product_service"],
        "EditAuctionForm[profit_source_value][other_source]" => $options["profit_source_value"]["other_source"],
        "EditAuctionForm[more_profit]"           =>  $options["more_profit"],
        "EditAuctionForm[profit_is_true]"    =>  $options["profit_is_true"],
        "EditAuctionForm[monetization_ban]" => $options["monetization_ban"],
        "EditAuctionForm[more_monetization_ban]" => $options["more_monetization_ban"],
        "YII_CSRF_TOKEN"                  => $token
    );   
    $postData = $this->makePostString($fields);
    $ch = curl_init($link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_POST, count($postData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
    ));  
     
       
    $res = curl_exec($ch);   
}
private function setDescription($link, $options)
{
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
        ));   
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    
    curl_close($ch);
    
    $html = str_get_html($result);
    $link = $this->_siteUrl.($html->find("#edit_main_form",0)->action);
    $token = $html->find('#edit_main_form input[name="YII_CSRF_TOKEN"]',0)->value;
    $fields = array(
        "EditAuctionForm[discription]" =>  $options["description"],
        "EditAuctionForm[sanction_ya]" =>  $options["sanction_ya"],
        "EditAuctionForm[sanction_google]" => $options["sanction_google"],
        "EditAuctionForm[sanction_ya_info]" => $options["sanction_ya_info"],
        "EditAuctionForm[sanction_google_info]" => $options["sanction_google_info"],
        "EditAuctionForm[sale_reason]" =>   $options["sale_reason"],
        "EditAuctionForm[website_addons]" => $options["website_addons"],
        "EditAuctionForm[origin_tic_pr]" => $options["origin_tic_pr"],
        "EditAuctionForm[glue_tic]" => $options["glue_tic"],
        "YII_CSRF_TOKEN"                  => $token
    );   
    $postData = $this->makePostString($fields);
    $ch = curl_init($link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_POST, count($postData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
    ));  
     
       
    $res = curl_exec($ch);
}
private function setTrafic($link, $options)
{
    $ch = curl_init($link);
    $cookiesString = $this->makeCookieString($this->_cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Referer: http://www.telderi.ru/ru/system/login',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru'
    ));   
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $html = str_get_html($result);

    $link = $this->_siteUrl.($html->find("#yw0",0)->action);
    $token = $html->find('#yw0 input[name="YII_CSRF_TOKEN"]',0)->value;
    if($options["rbList1"] == "manual")
    {
         $fields = array(
        "rbList1" => $options["rbList1"],
        "EditAuctionForm[visits]" => $options["visits"],
        "EditAuctionForm[hits]" => $options["hits"],
        "EditAuctionForm[trafic_type]" => $options["trafic_type"] ,
        "EditAuctionForm[from_search_manual][yandex]" =>  $options["from_search_manual"]["yandex"],
        "EditAuctionForm[from_search_manual][google]" => $options["from_search_manual"]["google"],
        "EditAuctionForm[from_search_manual][mail]"   => $options["from_search_manual"]["mail"],
        "EditAuctionForm[from_search_manual][bookmark]" => $options["from_search_manual"]["bookmark"],
        "EditAuctionForm[from_search_manual][other]" => $options["from_search_manual"]["other"],
        "EditAuctionForm[more_trafic]"          =>  $options["more_trafic"],
        "YII_CSRF_TOKEN"                  => $token
         );   
    }
    else
    {
        
         $fields = array(
        "rbList1" => $options["rbList1"],
        "action"  => "checkLi",
        "li_pass" => $options["li_pass"],
        "YII_CSRF_TOKEN"                  => $token
    );    
    }
    $postData = $this->makePostString($fields);
    $ch = curl_init($link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_POST, count($postData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $cookiesString = ($this->makeCookieString($this->_cookies).'; YII_CSRF_TOKEN='.$token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Origin: http://www.telderi.ru',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: http://www.telderi.ru/ru/viewsite/934925',
                'Accept-Language: uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                $cookiesString,
                'Host: www.telderi.ru',
               'X-Requested-With: XMLHttpRequest'
    ));  
 
    $res = curl_exec($ch); 
}
public function _generateStrings()
{
    $str = '';
    
    for($i = 0;$i < 250;$i++)
    {
        $str .= 'aa ';
    }
    return $str;
}
private function makePostString($fields)
{
    $postData = '';
    foreach($fields as $k => $v)
    {
        $postData .= $k.'='.$v.'&';
    }
	
    $postData = rtrim($postData, '&');     
    return $postData;       
}
private function makeCookieString($fields)
{
    $cookieString = 'Cookie:';
    foreach($fields as $k => $v)
    {
        $cookieString .=' '.$k.'='.$v.';';
    }
    $cookieString = rtrim($cookieString, ';');   
	
    return $cookieString;    
} 
}
