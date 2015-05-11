<?php
require_once 'config.php';

$github_event=$_SERVER['HTTP_X_GITHUB_EVENT'];
if('ping' == $github_event) {
    echo 'Ping. URL: ' . COMMENT_URL;
}else{
    try {
    $postdata=json_decode(implode("", file('php://input')));
    if($postdata) {
        $repo_name  = $postdata->repository->full_name;
        // Iterate through each commit to see if we have a related task
        foreach ($postdata->commits as $commit) {
            // Format message data
            $commitID   = $commit->id;
            $comment    = $commit->message;
            $url        = $commit->url;
            $timestamp  = $commit->timestamp;
            $author     = $commit->author->name;
            // Get any commit messages that have a # tag (points ot a resource ID in Teamwork)
            preg_match_all('/#([A-Za-z0-9_]+)/', $comment, $matches);
            // Remove the first index since it's the original
            $resourceID = array_pop($matches);

            // Format the message that will post to Teamwork
            $commentSend = strtr(COMMENT_TEMPLATE, array(
                        '{COMMENT}' => ' --> '.$comment,
                        '{URL}'     => $url,
                        '{COMMIT_NAME}' => $repo_name .'@'. substr($commitID, 0, 7),
                        '{AUTHOR}'  => $author,
                        '{DATE}'    => date(DATE_FORMAT, strtotime($timestamp)) ));
            $arr = array('comment' => 
                array(  'body' => "Reply to earlier comment", 
                        'notify' => '',
                    "isprivate"=> false,
                    "pendingFileAttachments"=> ""
                    ));
 
$json = json_encode($arr);
            if(count($resourceID) > 0) {
                //echo '1';
                // Iterate through each hash tag / resource and make a request
                foreach ($resourceID as $resource) {
                    if($resource < MIN_RESOURCE_ID) {
                        echo "Task #$resource: skipping" . PHP_EOL;
                        continue;
                    }
//-----------------------------------------------

  
 
                    $arr = array('comment' => 
                        array(  'body' => $commentSend, 
                                'notify' => '',
                                "isprivate"=> false,
                                "pendingFileAttachments"=> ""
                    ));
 
$json = json_encode($arr);
 
 
                    $channel = curl_init();
                    curl_setopt( $channel, CURLOPT_URL, COMMENT_URL);
                    curl_setopt( $channel, CURLOPT_RETURNTRANSFER, 1 );
                    curl_setopt( $channel, CURLOPT_POST, 1 );
                    curl_setopt( $channel, CURLOPT_POSTFIELDS, $json );
                    curl_setopt( $channel, CURLOPT_HTTPHEADER, array( 
                        "Authorization: BASIC ". base64_encode( $key .":xxx" ),
                        "Content-type: application/json"
                    ));
                    curl_exec ( $channel );
                    echo "Task #$resource (Code: ".curl_getinfo($channel, CURLINFO_HTTP_CODE).") ". PHP_EOL;
                    curl_close ( $channel );

//----------------------------------------------------
                    // Create the comment
                    /*
$channel = curl_init();
$teamwork_url="https://{$company}.teamwork.com/tasks/{$taskListId}/comments.json";
curl_setopt( $channel, CURLOPT_URL, $teamwork_url);
curl_setopt( $channel, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt( $channel, CURLOPT_POST, 1 );
curl_setopt( $channel, CURLOPT_POSTFIELDS, $json );
curl_setopt( $channel, CURLOPT_HTTPHEADER, array( 
    "Authorization: BASIC ". base64_encode( $key .":xxx" ),
    "Content-type: application/json"
));
 
                    $response = curl_exec($channel);
                    if($response === false)
{
    echo 'Ошибка curl: ' . curl_error($channel);
}
else
{
    echo 'Операция завершена без каких-либо ошибок';
}
                    $httpCode = curl_getinfo($channel, CURLINFO_HTTP_CODE);
                    curl_close($channel);
                    echo "Task #$resource ($httpCode): " . $teamwork_url . PHP_EOL;
                     * 
                     */
                }
            }
        }
    }

} catch (Exception $e) {
    print_r($e);
}
}
