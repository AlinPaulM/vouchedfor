<?php

$userInput = [
    "12th July 12:04,Jon,solicited,LB3 TYU,50words,*****",
    "12th July 12:05,Jon,unsolicited,KB3 IKU,20words,**",
    "13th July 15:04,Jon,unsolicited,CY8 IPK,150words,***",
    "15th July 10:04,Jon,solicited,BB4 IPK,40words,*****",
    "15th July 15:09,Jon,monkey",
    "29th August 10:04,Jon,solicited,LX2 IPK,70words,****",
    "2nd September 10:04,Jon,solicited,KB3 IKU,50words,****",
    "2nd September 10:04,Jon,solicited,AN9 IPK,90words,**"
];

echo "User input:\n";
var_export($userInput);
echo "\n";

function process(array $userInput) {
    if(empty($userInput)) return "ERROR: No user input.";

    foreach($userInput as $key => $str) {
        ### INITIALIZE SCORE
        if($key == 0) $score = 100;
        
        ### CHECK DATA VALIDITY
        $arr = explode(',', $str);
        if(count($arr) < 6) {
            echo "Could not read review summary data\n";
            continue;
        }
        
        // replace the string data with the exploded array
        $userInput[$key] = $arr;

        ### CORRECT THE DATE (it's missing the year - i need it for "burst" calculations for "drop 20")
        $date = explode(' ', $arr[0]);
        $userInput[$key][0] = $date[0] . ' '. $date[1] . ' ' . (date("Y") - 1) . ' ' . $date[2];
        
        ### PROCESS DATA
        // lots to say
        $arr[4] = (int) substr($arr[4], 0, -5);
        if($arr[4] > 100) $score -= 0.5;

        // burst (i assumed the entries are in chronologial order, as they seem to be in our given input)
        if($key > 0) {
            // drop 40        
            if($userInput[$key][0] == $userInput[$key-1][0] &&
            ($key == 1 || ($key > 1 && $userInput[$key-1][0] != $userInput[$key-2][0]))) {
                $score -= 40;
            }

            // drop 20
            if(strtotime($userInput[$key][0]) - strtotime($userInput[$key-1][0]) < 3600 &&
            ($key == 1 || ($key > 1 && strtotime($userInput[$key-1][0]) - strtotime($userInput[$key-2][0]) >= 3600 ))) {
                $score -= 20;
            }
        }

        // same device
        if($key > 0) {
            $devices = [];
            for($i = 0; $i < $key; ++$i) $devices[] = $userInput[$i][3];
            if(in_array($arr[3], $devices)) $score -= 30;
        }

        // all star
        $allStarsBeforeCurrentReview = 0;
        if($key > 0) {
            for($i = 0; $i < $key; ++$i) $allStarsBeforeCurrentReview += strlen($userInput[$i][5]);
        }
        $currentRating = strlen($arr[5]);
        if($currentRating == 5) {
            $avgRatingBeforeCurrentReview = ($key > 0) ? $allStarsBeforeCurrentReview / $key : $currentRating;
            if($avgRatingBeforeCurrentReview >= 3.5) $score -= 2;
            else $score -= 8;
        }

        // solicited
        if($arr[2] == 'solicited') {
            $score += 3;
            if($score > 100) $score = 100;
        }

        ### ECHO REVIEW RESULT
        $result = $arr[1] . " has a trusted review score of " . $score . "\n";
        if($score < 50)
            echo "Alert: " . $arr[1] . " has beed de-activated due to a low trusted review score\n";
        else if($score < 70)
            echo "Warning: " . $result;
        else if($score > 70)
            echo $result;
    }
}

echo "\nOutput:\n";
process($userInput);