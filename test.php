<?php
function findAndCompare($address1, $address2) {
  // aggregate inbound links for each homepage address
  $inbound1 = getInboundLinks($address1);
  $inbound2 = getInboundLinks($address2);

  // prepare to write to csv file
  $file = fopen("similarity.csv","w");

  // find most similar link in inbound2 for every link in inbound1
  foreach ($inbound1 as $href1) {
    $max_sim = NULL;
    $most_similar_href = NULL;
    $max_perc = NULL;
    
    foreach ($inbound2 as $href2) {
      // get similarity score
      $sim = similar_text($href1, $href2, $perc);

      // update max
      if ($most_similar_href == NULL || $sim > $max_sim) {
        $max_sim = $sim;
        $most_similar_href = $href2;
        $max_perc = $perc;
      }
    }

    // put each line in csv file
    $line = $href1 . "," . $most_similar_href . "," . round($max_perc, 2) . "%";
    fputcsv($file, explode(',',$line));
    set_time_limit(0);
  }

  // force download csv file
  header("Content-Type: application/octet-stream");
  header("Content-Transfer-Encoding: Binary");
  header("Content-disposition: attachment; filename=\"similarity.csv\""); 
  readfile("similarity.csv");
}

function getInboundLinks($homepageurl) {
  $inbound_links = array();

  // get hrefs on homepage
  $homepage_hrefs = array();
  getLinksFromPage($homepageurl, $homepage_hrefs);
  set_time_limit(0);
  $homepage_hrefs = array_unique($homepage_hrefs);
  set_time_limit(0);

  // get hrefs in hrefs (depth 1)
  $depth1_hrefs = array();
  foreach ($homepage_hrefs as $href) {
    getLinksFromPage($href, $depth1_hrefs);
  }
  set_time_limit(0);
  $depth1_hrefs = array_unique($depth1_hrefs);
  set_time_limit(0);

  // aggregate inbound links
  foreach ($depth1_hrefs as $href) {
    if (strstr($href, $homepageurl)) {
      $inbound_links[] = $href;
    }
  }
  set_time_limit(0);
  return $inbound_links;
}

// parses a webpage's html file and adds all <a> tag hrefs to a list
function getLinksFromPage($url, &$arr) {
  // get DOM representation of webpage
  $dom = new DOMDocument("1.0");
  @$dom->loadHTMLFile($url);

  // loop through each <a> tag in the dom and add it to an array
  foreach($dom->getElementsByTagName('a') as $link) {
    // $links[] = array('url' => $link->getAttribute('href'), 'text' => $link->nodeValue);
    $href = $link->getAttribute('href');
    if (strlen($href) > 1) {
      $arr[] = $href;
    } 
  }
}

if( isset($_POST['submit']) )
{
  // get form params
  $address1 = htmlentities($_POST['address1']);
  $address2 = htmlentities($_POST['address2']);

  // make function call
  $result = findAndCompare($address1, $address2);
}
?>
