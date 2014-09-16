<?php
    //show all errors - useful whilst developing
    error_reporting(E_ALL);

function initKeys(){
	global $appID,$devID,$certID,$RuName,$serverUrl, $userToken,$compatabilityLevel, $siteID;
    // these keys can be obtained by registering at http://developer.ebay.com
    $production         = true;   // toggle to true if going against production
    $compatabilityLevel = 893;    // eBay API version
	$siteID = 0; // 0 = US
    if ($production) {
        $devID = 'f2a7f4ed-c06f-494d-bc2e-af34346a2705';   // these prod keys are different from sandbox keys
        $appID = 'SpektraM-a390-4335-825a-25770418f312';
        $certID = '3de3717a-9916-44c2-9af2-e7ee936d7e65';
        $RuName = 'SpektraMaxima-SpektraM-a390-4-ydzzsn';
        //set the Server to use (Sandbox or Production)
        $serverUrl = 'https://api.ebay.com/ws/api.dll';      // server URL different for prod and sandbox
        //the token representing the eBay user to assign the call with
        $userToken = 'AgAAAA**AQAAAA**aAAAAA**VZwSVA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AFkIKoAZSDowmdj6x9nY+seQ**Y3UCAA**AAMAAA**flSxt4gPiIoMrwbji72ZDwkYjI7K1MMgsg0VtC/UbZ5Gw/q9VwiNd9hJK1LSkSz2nJ42n0CS3qV0rz1IS4Rso8xuwN/eOCkbXArPIDIO3FdslAyNfJxAu4eLZQpHWWoVQP7w26Bss+B2H1T1aLNd8I4ZQt8X9VFEoLfpsNSQ/mk9O0ZK5hod/T5tucHMyJbja7BkFWLg9qU269r9CaGmhfAq5NxFDnG7on6j+sNRa06EhgoWv+tHcNxzx4cCrQs++ZVuVXsKkTQv4BdDY4jEL3w7GeJCGXUiev/NYPTCtx84/DiChqMCxWvLhCTjLLHwbHajY+p5yHnX5IbnTKmI+73Amzj84gWPh+YFc5FVSPIKUMGlE6UGHdEuyeQECkdNnATakL0jy9bBngPT+w5f99VAFL41XEFeNKBk3NLpxftunsVnUklIO9CmDQThbwyPR+i3reh+2UgVE1LuclY4de8LCPDWKckOh1nWbZrIuwK5kCgjJvnF3Hzs9iJHUt7+1NFhSjgB7Cmk9+vBoCWFpPCZtdruePzkU3K/VB3s1OWMtlkCjtkeEXlrZRGTvm4fRE+Ch6WAX1Hys7uY1ZXe+nTt1egOr7Zk7MpxxZSlslzSRCX1ZsX3wrCsjsESFETZEMZjjROby4U9vTqwuEvm0mJ9HCr94k2+7doxo+/vZe6hc7IfUoy3If8rlEzckC3dhe+untDtl+QTg84N2jOqJoysiGoN0wEDGorQO4MxNKnDfL/1W2Wx3TYBquHi6ZQc';
    } else {
        // sandbox (test) environment
        $devID = 'f2a7f4ed-c06f-494d-bc2e-af34346a2705';   // these prod keys are different from sandbox keys
        $appID = 'SpektraM-22c1-4033-9474-b82d87789d76';
        $certID = 'd09dc03e-7fa4-4a81-b031-115bafec350d';
        //set the Server to use (Sandbox or Production)
        $serverUrl = 'https://api.sandbox.ebay.com/ws/api.dll';
        // the token representing the eBay user to assign the call with
        // this token is a long string - don't insert new lines - different from prod token
        $userToken = 'AgAAAA**AQAAAA**aAAAAA**kpb/Uw**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6wFk4GhDJaFpA+dj6x9nY+seQ**dAQDAA**AAMAAA**aVhA51caYktb6PXO3jBouYaVfRDU0NtshjlovBNmlr8va+flnTwhc810vYE4CnesvHAmA80QWq2CeyxjLgkwiVQr2J7mzQrs/xJ6rLbGhyZcjspoHHu3/w5Ug+KPSqwFVOMHizsXczKJr9ychecuwL4OgZkQsPHb80xEJ9t5bkKXm7dGJow3ltFswoSg+8aUUO+JQeDqPuhHhpBv7uWJH+R+fVnGFu4v+oh1lWBbrqm7mrtuiKdKxWX/gghkBBxIV1Pb8Y4UqqcUYXs0sphxOeIcymA+WUG70dC0u0/McUW+oXD/mp6c+8et+OPs7HPEP5QoH1Jl9OAJQ2OZ5f04bIW+W64ErKSJg6a68IpS3weJAYAI7wg90lePB99AHyWA6CVgdSV4f5qOgOu6ZVyzjdeeHbpxawa+GJjHBjrRSZhKytm1e9/4vNSSH+7q1UyWT78UvQ+6uNNlSc71TBidPOeDCmgH83TOk0ALcgDX2ALLvkqaQuKlu3IJu6EJmRvJ/4jhVeaaqdFsz9EFGGEYtgqbz71+3lnE9072NtKGKxoHsQNRxwCtLeSEaAFWjm2/DLBeubs5wnpWrM9+5IOiZpYJH6Hn1VAkWf3QbsA2NDNfYOp/UNreTx3Zz3GjQ/CG1HxVChlB3/nJLMxYQTq/Io4/ri7gEC2DzfiBb+uMh3VIT1xRzAwt6z5y7RPjefe4tVLInQctAwFvS64u065yRw5+AZ3kmL9EjPQGeJhanf7NmqekJDJUX1UdmeWR6/pU';
    }

}
?>