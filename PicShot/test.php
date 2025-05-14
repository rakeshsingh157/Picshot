<?php

$image_url = 'https://i.ibb.co/jmQjJxn/bf4a50ad49b5.jpg';
$api_credentials = array(
    'key' => 'acc_7300facc9d3b521',
    'secret' => 'f127d8a250041a77a10d8c1e2ad78ccc'
);

$encoded_image_url = urlencode($image_url);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://api.imagga.com/v2/tags?image_url=' . $encoded_image_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_USERPWD, $api_credentials['key'] . ':' . $api_credentials['secret']);

$response = curl_exec($ch);

if ($response === false) {
    echo "Error occurred: " . curl_error($ch);
    curl_close($ch);
    exit;
}

curl_close($ch);

$json_response = json_decode($response);

if ($json_response && isset($json_response->result->tags)) {
    echo "<h3>Tags Detected (Confidence > 50%):</h3>";
    foreach ($json_response->result->tags as $tag) {
        $confidence = $tag->confidence;

        if ($confidence > 50) {
            $tagName = isset($tag->tag->en) ? $tag->tag->en : 'Unknown';
            echo "Tag: " . htmlspecialchars($tagName) . " - Confidence: " . htmlspecialchars((string)$confidence) . "%<br>";
        }
    }
} else {
    echo "Failed to get tags or invalid response.<br>";
    echo "Response: " . htmlspecialchars($response);
}
?>
