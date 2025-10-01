<?php
// Amazon Affiliate Configuration
define('AFFILIATE_TAG_IN', 'haerriz06-21');  // India
define('AFFILIATE_TAG_US', 'haerriz06-20');  // US (you'll need to get this)
define('AFFILIATE_TAG_UK', 'haerriz06-21');  // UK (you'll need to get this)

function generateAffiliateUrl($asin, $market = 'IN') {
    $domains = [
        'IN' => 'amazon.in',
        'US' => 'amazon.com', 
        'UK' => 'amazon.co.uk'
    ];
    
    $tags = [
        'IN' => AFFILIATE_TAG_IN,
        'US' => AFFILIATE_TAG_US,
        'UK' => AFFILIATE_TAG_UK
    ];
    
    $domain = $domains[$market] ?? 'amazon.in';
    $tag = $tags[$market] ?? AFFILIATE_TAG_IN;
    
    return "https://{$domain}/dp/{$asin}?tag={$tag}";
}
?>